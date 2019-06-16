import RPi.GPIO as GPIO

class LEDFlashManager(object):
    def __init__(self, gpio):
        self._gpio = gpio

        GPIO.setmode(GPIO.BCM)

        GPIO.setwarnings(False)

        GPIO.setup(
            gpio,
            GPIO.OUT,
            initial=GPIO.LOW
        )

    def turn_on(self):
        GPIO.output(self._gpio, GPIO.HIGH)

    def turn_off(self):
        GPIO.output(self._gpio, GPIO.LOW)


