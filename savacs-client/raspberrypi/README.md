# Raspberry Pi Setup

## Setup Raspbian Streth
1. Download disk image from https://www.raspberrypi.org/downloads/raspbian/
1. Write image to Micro SD. ( minimum 8GB, recom 16GB+ )
1. Create empty `ssh` file in `/boot` of MicroSD.
1. Start-up Raspbian.

## Setup Wi-Fi <small>(in Off-line Raspberry Pi)</small>
1. Edit `./conf/etc/wpa_supplicant/wpa_supplicant.conf` for your environment.
	* if you use multiple APs, duplicate the network entry.
1. Copy edited `./conf/etc/wpa_supplicant/wpa_supplicant.conf` and `./conf/etc/network/interfaces` to your USB RAM.
1. Run this command

```sh
sudo mount /dev/sda /mnt
sudo cp /mnt/interfaces /etc/network/interfaces
sudo cp /mnt/wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf
sudo chmod 600 /etc/wpa_supplicant/wpa_supplicant.conf
sudo umount /mnt
```

## Setup

### Install git
```sh
sudo apt update
sudo apt install git
```

### Clone this repo
```sh
git clone https://github.com/yanorei32/savacs
```

### Install Wi-Fi reconnector
```sh
cd ~/savacs/savacs-client/raspberrypi/wifi/
sudo ./install.sh
```

### Install basic configrations
```sh
cd ~/savacs/savacs-client/raspberrypi/conf/
sudo ./install-basics.sh
```

* **IMPORTANT:** This script will destroy the files.
	* `/boot/config.txt`
	* `/etc/apt/sources.list`
	* `/etc/default/keyboard`
	* `/etc/ssh/sshd_config`
	* `~pi/.xinitrc`
	* `~pi/motion.conf`

### Reboot

```sh
sudo reboot # for boot configure
```

### Update/Upgrade apt
```sh
sudo apt update
sudo apt upgrade
```

### Install Vim with plugins
```sh
sudo apt install vim
sudo apt purge vim-tiny

cd

git clone https://github.com/yanorei32/dotfiles
./dotfiles/install.sh

vim # and run :PlugInstall, :q in Vim
```


