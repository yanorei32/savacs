#include <xc.h>

#define _XTAL_FREQ 16000000

#pragma config WDTE		= OFF
#pragma config PWRTE	= OFF
#pragma config CP		= OFF
#pragma config BOREN	= OFF
#pragma config LPBOR	= OFF
#pragma config BORV		= HI
#pragma config LVP		= OFF
#pragma config MCLRE	= OFF
#pragma config WRT		= OFF
#pragma config FOSC		= INTOSC

inline void apply2pwm(__uint24 valx4){
	unsigned short val;
	val = (valx4 >> 3) - 1;
	PWM1DCL = (val << 6) & 0b11000000;
	PWM1DCH = val >> 2;
	__delay_ms(20);
}

int main(){
	// 16 MHz
	OSCCONbits.IRCF = 0b111;

	// 0: output / 1: input
	TRISA = 0b000;

	// all wpu enable (RA3 is input)
	OPTION_REGbits.nWPUEN = 0;
	WPUA = 0b1000;

	// 0: digital / 1: analog
	ANSELA = 0b000;

	// disable CWG
	CWG1CON0bits.G1EN = 0;

	// 9 bit PWM
	PR2 = 256 - 1;

	// PWM Module Enable
	PWM1CONbits.PWM1EN = 1;

	// PWM Output Enable
	PWM1CONbits.PWM1OE = 1;

	// init LATA
	LATA = 0;

	// init timer
	TMR2 = 0;
	T2CONbits.TMR2ON = 1;

	__uint24 valx4 = 0b1000;

	for(;;){
riseup:
		for(; valx4 < (1024 << 3); valx4 = (valx4 * 0b1001) >> 3){
			if(PORTAbits.RA3 == 0) goto risedown;
			apply2pwm(valx4);
		}
		valx4 = 1024 << 3;

		while(PORTAbits.RA3 != 0);

risedown:
		for(; valx4 > 0b1000; valx4 = (valx4 * 0b0111) >> 3) {
			if(PORTAbits.RA3 == 1) goto riseup;
			apply2pwm(valx4);
		}
		valx4 = 0b1000;

		while(PORTAbits.RA3 != 1);
	}

	return 0;
}

