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

### Initialization
```sh
# Purge editors
sudo apt purge nano ed

# Purge mDNS
sudo apt purge \
	avahi-daemon \
	samba-common \
	cifs-utils

# Purge network filesystem client
sudo apt purge nfs-common

sudo apt autoremove --purge

# Fix timezone
sudo dpkg-reconfigure tzdata
# Enter your timezone
```

### Install your authorized_keys
Install your `~/.ssh/authorized_keys`

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

**IMPORTANT:** This script will destroy the files.
* `/boot/config.txt`
* `/etc/apt/sources.list`
* `/etc/default/keyboard`
* `/etc/ssh/sshd_config`
	* If your LAN is not `192.168.0.1/24`, password authentication will be impossible.
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

### Install / Configure X Server
```sh
sudo apt install \
	xinit \
	xserver-xorg-core

sudo dpkg-reconfigure xserver-xorg-legacy
# Select anybody
```

### Install pip
```sh
curl -kL https://bootstrap.pypa.io/get-pip.py | sudo python2
```

### Build/Install Python libs
```sh
# deps
sudo apt install \
	python2.7-dev \
	libgtk2.0-dev \
	libgirepository1.0-dev \
	cmake \
	libjpeg-dev

# build / install PyGObject
sudo pip install PyGObject

# build / install numpy
sudo pip install numpy

# build / install OpenCV
wget https://github.com/opencv/opencv/archive/4.0.1.zip
unzip 4.0.1.zip
mkdir opencv-4.0.1/build
cd opencv-4.0.1/build
~/savacs/savacs-client/raspberrypi/setup/opencv-4.0.1-cmake.sh
time make -j4 # In my raspberry pi, user 34m26.136s.
sudo make install
```


