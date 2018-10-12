/**************************************
	UART Communication Functions
	  by T. Yano (March 2013)
***************************************/
#include <xc.h>
#include "freq.h"
#include "uart.h"

/* Receive Buffer */
static volatile char uart_rx_buf[UART_BUF_SIZ];
static volatile unsigned char uart_rx_w;
static unsigned char uart_rx_r;

/* Send Buffer */
static volatile char uart_tx_buf[UART_BUF_SIZ];
static unsigned char uart_tx_w;
static volatile unsigned char uart_tx_r;

/* Setting up and Enable UART */
void uart_on(void)
{
	TXSTAbits.SYNC = 0; /* Asynchronous mode */
	/* Baud Rate Setting */
	TXSTAbits.BRGH = 1;
	BAUDCONbits.BRG16 = 1;
	SPBRG = ((_XTAL_FREQ/UART_BAUD_RATE+2)/4 - 1) & 0xFF;
	SPBRGH = ((_XTAL_FREQ/UART_BAUD_RATE+2)/4 - 1)>>8;

	uart_tx_w = uart_tx_r = 0;
	uart_rx_w = uart_rx_r = 0;

	RCSTAbits.SPEN = 1; /* UART Enable */
	TXSTAbits.TXEN = 1; /* Tx Enable */
	RCSTAbits.CREN = 1; /* Rx Enable */
	PIE1bits.RCIE = 1; /* Rx Interrupt Enbale */
}

/* Disable UART */
void uart_off(void)
{
	PIE1bits.RCIE = 0; /* Rx Interrupt Disable */
	PIE1bits.TXIE = 0; /* Tx Interrupt Disable */
	RCSTAbits.CREN = 0; /* Rx Disable */
	TXSTAbits.TXEN = 0; /* Tx Disable */
	RCSTAbits.SPEN = 0; /* UART Disable */
}

/* Put 1 char to Tx Buffer */
void putch(char c)
{
	unsigned char uart_tx_w_next;
	uart_tx_w_next = uart_tx_w + 1U;
	if (uart_tx_w_next == UART_BUF_SIZ) uart_tx_w_next = 0;
	while (uart_tx_w_next == uart_tx_r); /* Wait while buffer is full */
	uart_tx_buf[uart_tx_w] = c;
	uart_tx_w = uart_tx_w_next;
	PIE1bits.TXIE = 1; /* Tx Interrupt Enable */
}

/* Get 1 char from Rx Buffer */
char getch(void)
{
	char c;
	while (uart_rx_w == uart_rx_r); /* Wait while buffer is empty */
	c = uart_rx_buf[uart_rx_r++];
	if (uart_rx_r == UART_BUF_SIZ) uart_rx_r = 0;
	return c;
}

/* Get 1 char with echo back */
char getche(void)
{
	char c;
	c = getch();
	putch(c);
	return c;
}

/* Return Tx Buffer Depth not in use */
unsigned char uart_tx_buf_room(void)
{
	signed char r = uart_tx_r - uart_tx_w - 1;
	if (r<0) r+=UART_BUF_SIZ;
	return (unsigned char)r;
}

/* Return Rx Buffer Depth filled */
unsigned char uart_rx_buf_num(void)
{
	signed char r = uart_rx_w - uart_rx_r;
	if (r<0) r+=UART_BUF_SIZ;
	return (unsigned char)r;
}

/* UART Interrupt Service Routine */
/* Must be Called from Main ISR */
void uart_isr(void)
{
	/* UART Tx Interrupt */
	if (PIR1bits.TXIF && PIE1bits.TXIE) {
		TXREG = uart_tx_buf[uart_tx_r++];
		if (uart_tx_r == UART_BUF_SIZ) uart_tx_r = 0;
		if (uart_tx_r == uart_tx_w) PIE1bits.TXIE = 0; /* No Next Data */
	}
	/* UART Rx Interrupt */
	if (PIR1bits.RCIF) {
		uart_rx_buf[uart_rx_w++] = RCREG;
		if (uart_rx_w == UART_BUF_SIZ) uart_rx_w = 0;
		if (RCSTAbits.OERR) { /* Overrun Error */
			/* Reset Overrun Error */
			RCSTAbits.CREN = 0;
			RCSTAbits.CREN = 1;
		}
	}
}

/* String Write to UART */
void strout(const char *str)
{
	while(*str) putch(*str++);
}

