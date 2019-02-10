#!/bin/bash

if [ ${EUID:-${UID}} -ne 0 ]; then
	echo "Please run in root user."
	exit 1
fi

echo "install /etc/modprobe.d/v4l2loopback.conf"
cp \
	$(cd $(dirname $0) && pwd)/etc/modprobe.d/v4l2loopback.conf \
	/etc/modprobe.d/v4l2loopback.conf

echo "install /etc/modules"
cp \
	$(cd $(dirname $0) && pwd)/etc/modules \
	/etc/modules

echo "install rc.local"
cp \
	$(cd $(dirname $0) && pwd)/etc/rc.local \
	/etc/rc.local

echo "Done."

