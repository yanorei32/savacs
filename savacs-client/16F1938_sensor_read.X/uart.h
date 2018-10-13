/**************************************
	UART Communication Functions
	  by T. Yano (March 2013)
***************************************/

#define UART_BUF_SIZ 16
//#define UART_BUF_SIZ 32
#define UART_BAUD_RATE 9600

#define uart_is_on() (RCSTAbits.SPEN)

void uart_on(void);
void uart_off(void);

void putch(char c);
char getch(void);
char getche(void);

unsigned char uart_tx_buf_room(void);
unsigned char uart_rx_buf_num(void);

void uart_isr(void);
void strout(const char *str);

