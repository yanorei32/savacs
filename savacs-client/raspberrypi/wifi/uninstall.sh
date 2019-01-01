#!/bin/bash

if [ ${EUID:-${UID}} -ne 0 ]; then
	echo "Please run in root user."
	exit 1
fi

rm /usr/local/sbin/wifi_reconn.sh

grep -v /usr/local/sbin/wifi_reconn.sh \
	/var/spool/cron/root \
	> /var/spool/cron/root

