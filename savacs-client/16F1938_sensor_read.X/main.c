#include <xc.h>
#include <stdio.h>
#include "freq.h"
#include "uart.h"

// CONFIG1 / CONFIG2

// Clock Out Enable
#pragma config CLKOUTEN	= OFF

// Watchdog Timer Enable
#pragma config WDTE		= OFF

// Power-up Timer Enable ( 72ms delay after power on )
#pragma config PWRTE	= ON

// Flash Program Memory Code Protection
#pragma config CP		= OFF

// Brown-out Reset Enable
#pragma config BOREN	= OFF

// Fail-Safe Clock Monitor Enable
#pragma config FCMEN	= OFF

// MCLR Pin Function Select (ON: MCLR, OFF: digital input)
#pragma config MCLRE	= OFF

// Data Memory Code Protection
#pragma config CPD		= OFF

// Internal/External Switchover
#pragma config IESO		= OFF

// Oscillator Selection
#pragma config FOSC		= INTOSC

// Stack Overflow/Underflow Reset Enable
#pragma config STVREN	= ON

/*
// Brown-out Reset Voltage Selection
#pragma config BORV		= XXX
*/

// Low-Voltage Programming Enable
#pragma config LVP		= OFF

// Voltage Regulator Capacitor Enable
#pragma config VCAPEN	= OFF

// Flash Memory Self-Write Protection
#pragma config WRT		= OFF

// x4 PLL Enable (OFF: x4 PLL Set by OSCCONbits, ON: Force x4 PLL)
#pragma config PLLEN	= OFF

// ASCII Table
static const char ASCII_TABLE[] = {
	'0', '1', '2', '3', '4', '5', '6', '7',
	'8', '9', 'A', 'B', 'C', 'D', 'E', 'F'
};

inline void init(){
	// oscillator config
	setFreq();

	// RA0 / RA1 / RA2 : Input
	TRISA = 0b00000111;

	// RB0 / RB2 : Input
	TRISB = 0b00000101;

	// A/D Connect to AN0
	ADCON0bits.CHS = 0b00000;

	// A/D Conv On
	ADCON0bits.ADON = 1;

	// bit is right just fit (10bit -> ADRSEH 2bit + ADRSEL 8bit)
	ADCON1bits.ADFM = 1;

	// A/D Clock is FOSC/2
#if _XTAL_FREQ == 32000000
	ADCON1bits.ADCS = 0b010;
#elif _XTAL_FREQ == 8000000
	ADCON1bits.ADCS = 0b001;
#endif

	// Global Interrupt Enable
	INTCONbits.GIE = 1;

	// Peripheral Interrupt Enable
	INTCONbits.PEIE = 1;

	// UART Port Init
	TRISCbits.TRISC6 = 1;
	TRISCbits.TRISC7 = 1;

	// AN0 / AN1 / AN2 : Analog
	ANSELAbits.ANSELA = 0b000111;
	ANSELBbits.ANSELB = 0;

	// A/D VRef- is Vss
	ADCON1bits.ADNREF = 0;

	// FVR Enable
	FVRCONbits.FVREN = 1;

	// VRef
	FVRCONbits.ADFVR = 0b01;

	// Timer0 Source Select
	OPTION_REGbits.TMR0CS = 0;

	// Use Prescaler
	OPTION_REGbits.PSA = 0;

	// Prescaler 1:128
	OPTION_REGbits.PS = 0b110;
}

void __interrupt() isr(){
	uart_isr();
}

unsigned short inline analogRead(char channelSelect, char analogVddRefSelect){
	// A/D Connect to select port
	ADCON0bits.CHS = channelSelect;

	// A/D VRef+ is Vdd
	ADCON1bits.ADPREF = analogVddRefSelect;

	// stabilizes delay
	//__delay_us(10);
	__delay_us(100);
	//__delay_us(50);

	// read flag
	ADCON0bits.GO = 1;

	// read wait
	while(ADCON0bits.GO);

	// return value
	return (unsigned short) ADRESH << 8 | ADRESL;
}



int main(){
	unsigned short cache;
	char buf[16];
	init();
	uart_on();

	for(;;){
		// Brightness
		cache = analogRead(0b00000, 0b00);
		sprintf(buf, "%d,", cache);
		strout(buf);

		// Temperature
		cache = analogRead(0b00001, 0b11);
		sprintf(buf, "%d,", cache);
		strout(buf);

		// Infrared
		cache = analogRead(0b00010, 0b00);
		sprintf(buf, "%d,", cache);
		strout(buf);

		// Pyroelectric
		cache = PORTBbits.RB2;
		sprintf(buf, "%d,", cache);
		strout(buf);

		//------------------
		// Ultrasonic
		//------------------

		// Trigger
		LATBbits.LATB1 = 1;
		__delay_us(10);
		LATBbits.LATB1 = 0;

		// Timer clear
		TMR0 = 0;

		// delay 40k acoustic burst
		while(!PORTBbits.RB0);

		// delay response and update cache value
		while((cache = TMR0) <= 192 && PORTBbits.RB0);

		cache=TMR0;

		// print
		sprintf(buf, "%d", cache);
		strout(buf);

		// new line
		strout("\r\n");

		// delay 60ms (for ultrasonic sensor)
		__delay_ms(60);
	}

	uart_off();

    return 0;
}

