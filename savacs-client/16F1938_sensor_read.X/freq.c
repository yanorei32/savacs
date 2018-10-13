#include <xc.h>
#include "freq.h"

void setFreq(){
	// Base Clock Set
	OSCCONbits.IRCF = 0b1110;

	switch(_XTAL_FREQ){
		case 32000000:
			// Software PLL Enable bit
			OSCCONbits.SPLLEN = 1;

			OSCCONbits.SCS = 0b00;

			break;
		case 8000000:
			// Software PLL Enable bit
			OSCCONbits.SPLLEN = 0;

			OSCCONbits.SCS = 0b10;

			break;
	}
}

