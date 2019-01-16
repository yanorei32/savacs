# Raspberry Pi Setup

## Setup Raspbian Streth
1. Download disk image from https://www.raspberrypi.org/downloads/raspbian/
1. Write image to Micro SD. ( minimum 8GB, recom 16GB )
1. Create empty `ssh` file in `/boot` of MicroSD.
1. Start-up Raspbian.

## Setup Wi-Fi
1. Edit `./conf/etc/wpa_supplicant/wpa_supplicant.conf` for your environment.
	* if you use multiple APs, duplicate the network entry.
1. Copy edited `./conf/etc/wpa_supplicant/wpa_supplicant.conf` and `./conf/etc/network/interfaces` to your USB RAM.
1. Run this command

```sh
sudo mount /dev/sda /mnt
sudo cp /mnt/interfaces /etc/network/interfaces
sudo cp /mnt/wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf
sudo chmod 600 /etc/wpa_supplicant/wpa_supplicant.conf
```

## Setup git

```sh
sudo apt update
sudo apt install git
```

## Clone a repo

```sh
git clone https://github.com/yanorei32/savacs
```

## Setup apt

```sh
cd ~/savacs/savacs-client/raspberrypi/conf
sudo cp ./etc/apt/sources.list /etc/apt/
sudo apt update
sudo apt upgrade
```

## Setup Vim

```sh
sudo apt install vim
sudo apt purge vim-tiny
cd
git clone https://github.com/yanorei32/dotfiles
./dotfiles/install.sh
vim
# Run :PlugInstall in Vim
```


