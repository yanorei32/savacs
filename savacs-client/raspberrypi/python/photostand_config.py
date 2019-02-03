import configparser
import errno
import os

class FailedToReadSerialNumber(Exception):
    pass

class PhotostandConfig(object):
    _CONFIG_FILENAME = '/etc/photostand.ini'

    def __init__(self):
        pass

    def get_sensor_is_active(self):
        return self._sensor_is_active

    def get_sensor_daemon_socket_file_name(self):
        return self._sensor_daemon_socket_file_name

    def get_sensor_daemon_serial_port_device_name(self):
        return self._sensor_daemon_serial_port_device_name

    def get_server_uri_base(self):
        return self._server_base_uri

    def get_server_timeout(self):
        return self._server_timeout

    def get_cpu_serial(self):
        return self._cpu_serial

    def get_password(self):
        return self._password

    def get_default_font(self):
        return self._default_font

    def get_json_reload_interval(self):
        return self._json_reload_interval

    def get_download_selfy_image_file_name(self):
        return self._download_selfy_image_file_name

    def get_download_record_voice_file_name(self):
        return self._download_record_voice_file_name

    def get_capture_selfy_image_file_name(self):
        return self._capture_selfy_image_file_name

    def get_capture_record_voice_file_name(self):
        return self._capture_record_voice_file_name

    def _read_cpuinfo(self):
        with open('/proc/cpuinfo', 'r') as f:
            for line in f:
                if line[0:6] == 'Serial':
                    self._cpu_serial = line[10:26]
                    return

        raise FailedToReadSerialNumber

    def read(self):
        """
        Get photostand config by ini file

        Raises
        ----
        FailedToReadSerialNumber
        FileNotFoundError
            file not found

        ConfigParser.ParsingError
        ConfigParser.MissingSectionHeaderError
            syntax error

        ConfigParser.NoSectionError
        ConfigParser.NoOptionError
            undefined critical data

        ValueError
        """

        # MEMO: if file not exists, ConfigParser will return [].
        if os.path.isfile(self._CONFIG_FILENAME) is False:
            raise FileNotFoundError(
                errno.ENOENT,
                os.strerror(errno.ENOENT),
                self._CONFIG_FILENAME
            )

        config = configparser.RawConfigParser()

        config.read(self._CONFIG_FILENAME)

        self._server_base_uri = '{}://{}:{}{}'.format(
            config.get('server', 'protocol'),
            config.get('server', 'hostname'),
            config.get('server', 'port'),
            config.get('server', 'prefix'),
        )
        self._password = config.get('server', 'password')
        self._server_timeout = config.getint('server', 'timeout')
        self._json_reload_interval = config.getint(
            'server', 'json_reload_interval'
        )

        self._default_font = config.get('ui', 'font')

        self._sensor_is_active = config.getboolean(
            'infobar',
            'sensor_is_active'
        )

        if self._sensor_is_active:
            self._sensor_daemon_socket_file_name = config.get(
                'infobar',
                'socket_file_name'
            )

            self._sensor_daemon_serial_port_device_name = config.get(
                'infobar',
                'serial_port_device_name'
            )
        else:
            self._sensor_daemon_socket_file_name = \
                self._sensor_daemon_serial_port_device_name = \
                None

        self._download_record_voice_file_name = config.get(
            'download',
            'record_voice_file_name'
        )

        self._download_selfy_image_file_name = config.get(
            'download',
            'selfy_image_file_name'
        )

        self._capture_record_voice_file_name = config.get(
            'capture',
            'record_voice_file_name'
        )

        self._capture_selfy_image_file_name = config.get(
            'capture',
            'selfy_image_file_name'
        )

        self._use_dummy_cpu_serial = config.getboolean(
            'debug',
            'use_dummy_cpu_serial'
        )

        if self._use_dummy_cpu_serial:
            self._cpu_serial = config.get('debug', 'dummy_cpu_serial')
        else:
            self._read_cpuinfo()


