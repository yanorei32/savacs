#!/bin/bash

if [ ${EUID:-${UID}} -ne 0 ]; then
	echo "Please run in root user."
	exit 1
fi

echo "install /boot/config.txt ..."
cp \
	$(cd $(dirname $0) && pwd)/boot/config.txt \
	/boot/config.txt

echo "install /etc/apt/sources.list ..."
cp \
	$(cd $(dirname $0) && pwd)/etc/apt/sources.list \
	/etc/apt/sources.list

echo "install /etc/default/keyboard ..."
cp \
	$(cd $(dirname $0) && pwd)/etc/default/keyboard \
	/etc/default/keyboard

echo "install /etc/ssh/sshd_config ..."
cp \
	$(cd $(dirname $0) && pwd)/etc/ssh/sshd_config \
	/etc/ssh/sshd_config

echo "install ~pi/.xinitrc ..."
ln -s \
	$(cd $(dirname $0) && pwd)/home/pi/.xinitrc \
	~pi/.xinitrc

echo "update permission ~pi/.xinitrc ..."
chown pi:pi ~pi/.xinitrc

echo "install ~pi/motion.conf ..."
ln -s \
	$(cd $(dirname $0) && pwd)/home/pi/motion.conf \
	~pi/motion.conf

echo "update permission ~pi/motion.conf ..."
chown pi:pi ~pi/motion.conf

echo "Done."

