#!/bin/sh

# Cloudflare Public DNS Server
SERVER=1.1.1.1

ping -c2 ${SERVER} > /dev/null

if [ $? != 0 ]; then
    # Restart the wireless interface
    ifdown --force wlan0
    ifup wlan0
fi


