# -*- coding: utf-8 -*-
from configparser import ParsingError, MissingSectionHeaderError, NoSectionError, NoOptionError
from photostand_config import PhotostandConfig, FailedToReadSerialNumber
import sys
import coloredlogs, logging
import os
import requests

FileNotFoundError = IOError

def readMotionImage(filePath):
    fileName = os.path.basename(filePath)
    raw = open(filePath, 'rb')

    return {
        'motionImage' : (fileName, raw, 'image/jpeg')
    }

def main():
    # logger initialize
    logger = logging.getLogger(__name__)
    handler = logging.StreamHandler()
    handler.setLevel(logging.INFO)
    handler.setFormatter(
        coloredlogs.ColoredFormatter(fmt="[%(asctime)s] %(message)s")
    )
    logger.setLevel(logging.DEBUG)
    logger.addHandler(handler)
    logger.propagate = False

    # initialize photostand configure
    psc = PhotostandConfig()

    try:
        psc.read()

    except (FailedToReadSerialNumber):
        logger.critical('Failed to read cpu serial number.')
        sys.exit(1)

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

        sys.exit(1)

    logger.info('Try to send motion image')

    filePath = sys.argv[1]

    # file post
    post_data = {
        'password'          : psc.get_password(),
        'cpuSerialNumber'   : psc.get_cpu_serial(),
        'noiseLevel'        : sys.argv[7],
        'changedPixel'      : sys.argv[6],
        'areaCenterX'       : sys.argv[4],
        'areaCenterY'       : sys.argv[5],
        'areaWidth'         : sys.argv[2],
        'areaHeight'        : sys.argv[3]
    }

    try:
        r = requests.post(
            psc.get_server_uri_base() + "/api/upload_motion_image.php",
            data = post_data,
            files = readMotionImage(filePath),
            timeout = psc.get_server_timeout()
        )

        r.raise_for_status()

    except requests.exceptions.HTTPError as errh:
        logger.error('HTTP Erorr: ' + str(errh) + ', Contents:' + r.text)
        sys.exit(1)

    except requests.exceptions.ConnectionError as errc:
        logger.error('Error Connecting: ' + str(errc))
        sys.exit(1)

    except requests.exceptions.Timeout as errt:
        logger.error('Timeout Error ({} secounds): '.format(self._HTTP_TIMEOUT) + str(errt))
        sys.exit(1)

    except requests.exceptions.RequestException as err:
        logger.error('OOps: Something Else:' + str(err))
        sys.exit(1)

    os.unlink(filePath)

    logger.info('Send success.')

if __name__ == '__main__':
    main()


