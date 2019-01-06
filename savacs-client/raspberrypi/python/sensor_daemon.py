# -*- coding: utf-8 -*-
from ConfigParser import ParsingError, MissingSectionHeaderError, NoSectionError, NoOptionError
from photostand_config import PhotostandConfig, FailedToReadSerialNumber
from sys import exit
from os import unlink
from datetime import datetime
import numpy as np
import serial
import socket
import re
import requests
import json
import threading
import coloredlogs, logging
from traceback import format_exc

class LastSensorValue:
    def __init__(self, logger):
        self._logger = logger

        self._last_update_time      = None
        self._brightness            = 0.0
        self._temperature           = 0.0
        self._infrared_distance     = 0.0
        self._pyroelectric          = 0.0
        self._ultrasonic_distance   = 0.0

        self._values_lock = threading.Lock()

    def set_values(self, brightness, temperature, infrared_distance, pyroelectric, ultrasonic_distance):
        with self._values_lock:
            self._brightness            = brightness
            self._temperature           = temperature
            self._infrared_distance     = infrared_distance
            self._pyroelectric          = pyroelectric
            self._ultrasonic_distance   = ultrasonic_distance
            self._last_update_time      = datetime.now()

    def get_brightness_value(self):
        if self._last_update_time is None:
            return False

        with self._values_lock:
            return self._brightness

    def get_temperature_value(self):
        if self._last_update_time is None:
            return False

        with self._values_lock:
            return self._temperature

    def get_infrared_distance_value(self):
        if self._last_update_time is None:
            return False

        with self._values_lock:
            return self._infrared_distance

    def get_pyroelectric_value(self):
        if self._last_update_time is None:
            return False

        with self._values_lock:
            return self._pyroelectric

    def get_ultrasonic_distance_value(self):
        if self._last_update_time is None:
            return False

        with self._values_lock:
            return self._ultrasonic_distance

    def get_last_update_time(self):
        if self._last_update_time is None:
            return False

        with self._values_lock:
            return self._last_update_time

class SensorRawValueConverter:
    def __init__(self):
        self._PIC_VOLTAGE = 5.0 # V

        PIC_ADC_RESOLUTION_BIT = 10 # bit
        self._PIC_ADC_RESOLUTION = 2 ** PIC_ADC_RESOLUTION_BIT

        # for cds lux calc
        self._CDS_PULL_DOWN_KOHM = 33.0 # K

        PIC_CLOCK = 8e6 # Hz
        PIC_TMR_SOURCE = PIC_CLOCK / 4 # Fosc / 4
        PIC_PRESCALER = 128 # 1:n

        self._PIC_TMR_INC_DELAY = 1.0 / (PIC_TMR_SOURCE / PIC_PRESCALER) # sec

        self._SPEED_OF_SOUND = 340 * 100 # cm/sec

    def _get_voltage_by_adc_val(self, val):
        voltage = (self._PIC_VOLTAGE / self._PIC_ADC_RESOLUTION) * val

        return voltage

    def brightness_raw_value_to_lux(self, val):
        # get voltage
        voltage = self._get_voltage_by_adc_val(val)

        # get kohm
        cds_kohm = (self._PIC_VOLTAGE * self._CDS_PULL_DOWN_KOHM) / voltage - self._CDS_PULL_DOWN_KOHM

        # calc lux
        lux = (150 / cds_kohm) ** 1.39

        return lux

    def temperature_raw_value_to_celsius(self, val):
        # calc celsius
        celsius = val / 10.0

        return celsius

    def infrared_distance_raw_value_to_centimetear(self, val):
        # get value
        voltage = self._get_voltage_by_adc_val(val)

        # calc centimetear
        centimetear = (14 / voltage) ** 1.25

        return centimetear

    def ultrasonic_distance_raw_value_to_centimetear(self, val):
        # get ping (sec)
        ping = val * self._PIC_TMR_INC_DELAY

        # calc centimetear
        centimetear = (ping / 2) * self._SPEED_OF_SOUND

        return centimetear

class SerialPortWatcher:
    def __init__(self, logger, photostand_config, last_sensor_value):
        self._logger    = logger
        self._psc       = photostand_config
        self._lsv       = last_sensor_value

        # create instance
        self._serial_port = serial.Serial()

        # set options
        self._serial_port.port               = \
            self._psc.get_sensor_daemon_serial_port_device_name()
        self._serial_port.baudrate           = 9600
        self._serial_port.bytesize           = serial.EIGHTBITS
        self._serial_port.parity             = serial.PARITY_NONE
        self._serial_port.stopbits           = serial.STOPBITS_ONE
        self._serial_port.timeout            = None
        self._serial_port.xonxoff            = False
        self._serial_port.rtscts             = False
        self._serial_port.dsrdtr             = False
        self._serial_port.write_timeout      = None
        self._serial_port.inter_byte_timeout = None

        # create regex pattern
        self._sample_regex_pattern = re.compile(r'\A\d+,\d+,\d+,[0-1],\d+\Z')

        self._SENSOR_INDEX = {
            'brightness'            : 0,
            'temperature'           : 1,
            'infrared_distance'     : 2,
            'pyroelectric'          : 3,
            'ultrasonic_distance'   : 4,
        }

        self._SENSOR_INDEX_FOR_DIFF_CALC = {
            'brightness'          : 0,
            'infrared_distance'   : 1,
            'ultrasonic_distance' : 2,
        }

        self._SENSOR_DIFF_THRETHOLD = np.array([
            2.0, # brightness
            1.2, # infrared_distance
            1.5, # ultrasonic_distance
        ])

        self._SENSOR_PYROELECTRIC_THRESHOLD = 0.8

        self._SENSOR_CDS_NOISE_LEVEL = 0.001

        self._SEND_TO_SERVER_TRIGGER_DICT = {
            'sensor_data'   : 1,
            'interval'      : 0,
        }

        self._SEND_INTERVAL = 10 * 60 # sec

        # n sample / data (avg)
        self._MEAN_SAMPLE_COUNT = 10

        self._last_interval_send_dt = datetime.fromtimestamp(0)

        self._srvc = SensorRawValueConverter()

    def _skip_first_line(self):
        self._serial_port.readline()

    def _get_raw_data_by_serial(self):
        counter = 0
        sample_lines = ''

        while counter < self._MEAN_SAMPLE_COUNT:
            line = self._serial_port.readline()

            # delete \r \n
            line = line[:-2]

            if not self._sample_regex_pattern.match(line):
                self._logger.info('Fail line')
                continue

            sample_lines += (line + ',')
            counter += 1

        return np.reshape(
            np.array(
                [int(x) for x in sample_lines[:-1].split(',')]
            ),(
                self._MEAN_SAMPLE_COUNT,
                len(self._SENSOR_INDEX),
            ),
        ).mean(
            axis = 0,
            dtype = np.float,
        )

    def _convert_data(self, raw_data):
        converted_data = np.array([
            self._srvc.brightness_raw_value_to_lux(
                raw_data[self._SENSOR_INDEX['brightness']]
            ),
            self._srvc.temperature_raw_value_to_celsius(
                raw_data[self._SENSOR_INDEX['temperature']]
            ),
            self._srvc.infrared_distance_raw_value_to_centimetear(
                raw_data[self._SENSOR_INDEX['infrared_distance']]
            ),
            raw_data[self._SENSOR_INDEX['pyroelectric']],
            self._srvc.ultrasonic_distance_raw_value_to_centimetear(
                raw_data[self._SENSOR_INDEX['ultrasonic_distance']]
            ),
        ])

        return converted_data

    def _check_sensor_value(self, prev_value, converted_data):
        new_value = converted_data[np.array([
            self._SENSOR_INDEX['brightness'],
            self._SENSOR_INDEX['infrared_distance'],
            self._SENSOR_INDEX['ultrasonic_distance']
        ])]

        new_value[
            self._SENSOR_INDEX_FOR_DIFF_CALC['brightness']
        ] += self._SENSOR_CDS_NOISE_LEVEL

        if prev_value is None:
            prev_value = -np.ones(len(new_value))

        send_flag = (
            any((prev_value / new_value) > self._SENSOR_DIFF_THRETHOLD) or \
            any((new_value / prev_value) > self._SENSOR_DIFF_THRETHOLD) or \
            converted_data[
                self._SENSOR_INDEX['pyroelectric']
            ] > self._SENSOR_PYROELECTRIC_THRESHOLD
        )

        return new_value, send_flag

    def _send_to_server(self, trigger, data):
        self._logger.info('Try to send value. fire event by {}.'.format(trigger))

        post_data = {
            'password'              : self._psc.get_password(),
            'cpuSerialNumber'       : self._psc.get_cpu_serial(),
            'cdsLux'                : data[self._SENSOR_INDEX['brightness']],
            'temperatureCelsius'    : data[self._SENSOR_INDEX['temperature']],
            'infraredCentimetear'   : data[self._SENSOR_INDEX['infrared_distance']],
            'ultrasonicCentimetear' : data[self._SENSOR_INDEX['ultrasonic_distance']],
            'pyroelectric'          : data[self._SENSOR_INDEX['pyroelectric']],
            'eventType'             : trigger
        }

        try:
            r = requests.post(
                self._psc.get_server_uri_base() + "/api/upload_sensor_data.php",
                data = post_data,
                timeout = self._psc.get_server_timeout()
            )

            r.raise_for_status()

        except requests.exceptions.HTTPError as errh:
            self._logger.error('HTTP Erorr: ' + str(errh) + ', Contents:' + r.text)
            return False

        except requests.exceptions.ConnectionError as errc:
            self._logger.error('Error Connecting: ' + str(errc))
            return False

        except requests.exceptions.Timeout as errt:
            self._logger.error(
                'Timeout Error ({} secounds): '.format(
                    self._psc.get_server_timeout()
                ) + str(errt)
            )

            return False

        except requests.exceptions.RequestException as err:
            self._logger.error('OOps: Something Else:' + str(err))
            return False

        self._logger.info('Send success.')
        return True

    def main(self):
        # open serial port
        self._serial_port.open()
        self._skip_first_line()

        # init
        prev_converted_data_for_diff_calc = None

        while True:
            # get data
            raw_data = self._get_raw_data_by_serial()

            # convert
            converted_data = self._convert_data(raw_data)

            self._logger.debug('Recv new data.')
            self._logger.debug(
                '  brightness: {:.2f} lux'.format(
                    converted_data[self._SENSOR_INDEX[
                        'brightness'
                    ]],
                )
            )

            self._logger.debug(
                '  temperature: {:.2f} C'.format(
                    converted_data[self._SENSOR_INDEX[
                        'temperature'
                    ]],
                )
            )

            self._logger.debug(
                '  distance: infr: {:.2f} cm, utsc: {:.2f} cm'.format(
                    converted_data[self._SENSOR_INDEX[
                        'infrared_distance'
                    ]],
                    converted_data[self._SENSOR_INDEX[
                        'ultrasonic_distance'
                    ]],
                )
            )

            self._logger.debug(
                '  pyroelectric: {:.1f}'.format(
                    converted_data[self._SENSOR_INDEX[
                        'pyroelectric'
                    ]],
                )
            )

            # update thread shared datas
            self._lsv.set_values(
                brightness = converted_data[
                    self._SENSOR_INDEX['brightness']],
                temperature = converted_data[
                    self._SENSOR_INDEX['temperature']],
                infrared_distance = converted_data[
                    self._SENSOR_INDEX['infrared_distance']],
                pyroelectric = converted_data[
                    self._SENSOR_INDEX['pyroelectric']],
                ultrasonic_distance = converted_data[
                    self._SENSOR_INDEX['ultrasonic_distance']],
            )

            # update prev data and get flag
            prev_converted_data_for_diff_calc, send_flag_by_sensor_value = \
                self._check_sensor_value(
                    prev_converted_data_for_diff_calc,
                    converted_data,
                )

            # send data
            if send_flag_by_sensor_value:
                send_success = self._send_to_server(
                    self._SEND_TO_SERVER_TRIGGER_DICT['sensor_data'],
                    converted_data,
                )

                continue

            now_dt = datetime.now()
            if (now_dt - self._last_interval_send_dt).total_seconds() > self._SEND_INTERVAL:
                send_success = self._send_to_server(
                    self._SEND_TO_SERVER_TRIGGER_DICT['interval'],
                    converted_data,
                )

                if send_success:
                    self._last_interval_send_dt = now_dt

                continue

class AfUnixServerThread(threading.Thread):
    def __init__(self, logger, photostand_config, last_sensor_value):
        super(AfUnixServerThread, self).__init__()
        self.daemon = True

        self._psc       = photostand_config
        self._logger    = logger
        self._lsv       = last_sensor_value

        self._request_regex = re.compile(r'\Aget *: *[A-Za-z0-9_]+\Z')

        self._stop_event = threading.Event()

        self._getter_define = {
            'temperature':          self._lsv.get_temperature_value,
            'brightness':           self._lsv.get_brightness_value,
            'infrared_distance':    self._lsv.get_infrared_distance_value,
            'pyroelectric':         self._lsv.get_pyroelectric_value,
            'ultrasonic_distance':  self._lsv.get_ultrasonic_distance_value,
        }

    def stop(self):
        self._logger.info(
            "Stop"
        )
        self._stop_event.set()

    def _cleanup(self):
        try:
            unlink(self._psc.get_sensor_daemon_socket_file_name())

        except OSError:
            pass

    def run(self):
        self._cleanup()
        s = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)

        s.bind(self._psc.get_sensor_daemon_socket_file_name())
        s.settimeout(0.1)
        s.listen(1)

        while not self._stop_event.is_set():
            try:
                conn, _ = s.accept()
                request = conn.recv(1024)

                # Is sensor data found
                if self._lsv.get_last_update_time() is False:
                    conn.send(json.dumps({
                        'status': 'error',
                        'status_description': 'No DATA',
                    }))

                    continue

                # Is valid request?
                if self._request_regex.match(request) is None:
                    conn.send(json.dumps({
                        'status': 'error',
                        'status_description': 'Not valid request',
                    }))

                    self._logger.warning('Recieve request ({}). but not valid.'.format(request))
                    continue

                request_key = request.split(':')[1]
                request_key = request_key.strip()

                # Check is active sensor
                if request_key not in self._getter_define:
                    conn.send(json.dumps({
                        'status': 'error',
                        'status_description': 'Key not found from dictionary',
                    }))

                    self._logger.warning('Recieve request ({}). but not found resources.'.format(request))
                    continue

                # Send data
                conn.send(json.dumps({
                    'status': 'success',
                    'status_description': 'success',
                    'value': self._getter_define[request_key](),
                    'last_update_time': '{0:%Y-%m-%d %H:%M:%S.%f}'.format(
                        self._lsv.get_last_update_time()
                    )
                }))

            except socket.timeout:
                pass

        conn.close()
        self._cleanup()

FileNotFoundError = IOError

def main():
    # logger initialize
    logger = logging.getLogger(__name__)
    handler = logging.StreamHandler()
    handler.setLevel(logging.INFO)
    handler.setFormatter(
        # logging.Formatter("[%(asctime)s] [%(threadName)s] %(message)s")
        coloredlogs.ColoredFormatter(fmt="[%(asctime)s] [%(threadName)s] %(message)s")
    )
    logger.setLevel(logging.DEBUG)
    logger.addHandler(handler)
    logger.propagate = False

    # initialize photostand configure
    photostand_config = PhotostandConfig()

    try:
        photostand_config.read()

    except (FailedToReadSerialNumber):
        logger.critical('Failed to read cpu serial number.')
        exit(1)

    except (
            FileNotFoundError,
            ParsingError,
            MissingSectionHeaderError,
            NoSectionError,
            NoOptionError,
            ValueError
        ):

        logger.critical(
            'Failed to read photostand config.\n' + format_exc()
        )

        exit(1)

    if not photostand_config.get_sensor_is_active():
        logger.info(
            'Sensor daemon disabled by photostand_config.'
        )

        exit(0)

    # initialize last sensor variable
    last_sensor_value = LastSensorValue(logger)

    # initialize socket server thread
    server_thread = AfUnixServerThread(
        logger,
        photostand_config,
        last_sensor_value
    )

    server_thread.start()

    try:
        # Initialize serial port watcher
        serial_port_watcher = SerialPortWatcher(
            logger,
            photostand_config,
            last_sensor_value,
        )

        serial_port_watcher.main()

    except KeyboardInterrupt:
        server_thread.stop()

if __name__ == '__main__':
    main()


