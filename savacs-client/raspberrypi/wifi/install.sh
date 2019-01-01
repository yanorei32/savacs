#!/bin/bash

if [ ${EUID:-${UID}} -ne 0 ]; then
	echo "Please run in root user."
	exit 1
fi

cp src/wifi_reconn.sh /usr/local/sbin/

chmod +x /usr/local/sbin/wifi_reconn.sh

echo "*/5 *  * * *  /usr/local/sbin/wifi_reconn.sh" \
	| tee -a /var/spool/cron/root


