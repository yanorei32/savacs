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

# Purge mDNS packages
sudo apt purge \
	avahi-daemon \
	samba-common \
	cifs-utils

# Purge network filesystem client
sudo apt purge \
	nfs-common

sudo apt autoremove --purge

# Fix timezone
sudo dpkg-reconfigure tzdata
# Enter your timezone
```

### Reboot
```
sudo reboot
```

### Update all packages
```
sudo apt update
sudo apt upgrade -y
sudo reboot
```

### Install git
```sh
sudo apt install git
```

### Install your authorized_keys
Install your `~/.ssh/authorized_keys`

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
sudo apt update
```

**IMPORTANT:** This script will destroy the files.
* `/boot/config.txt`
* `/etc/apt/sources.list`
* `/etc/default/keyboard`
* `/etc/ssh/sshd_config`
	* If your LAN is not `192.168.0.1/24`, password authentication will be impossible.
* `~pi/.xinitrc`
* `~pi/motion.conf`

### Install Vim with plugins
```sh
sudo apt install vim
sudo apt purge vim-tiny

cd

git clone https://github.com/yanorei32/dotfiles
./dotfiles/install.sh

vim # and run :PlugInstall, :q in Vim
```
z
### Create RAM Disk
```
sudo -E vim /etc/fstab
```

and append this line

```/etc/fstab
tmpfs /ramdisk tmpfs defaults,size=256m 0 0
```

### Reboot

```sh
sudo reboot # for boot configure
```

### Install / Configure X Server
```sh
sudo apt install \
	xinit \
	xserver-xorg-core \
	x11-xserver-utils \
	fonts-vlgothic

sudo dpkg-reconfigure xserver-xorg-legacy
# Select anybody
```

### Install pip
```sh
curl -kL https://bootstrap.pypa.io/get-pip.py | sudo python3
```

### Build v4l2loopback
```sh
# deps
sudo apt install \
	raspberrypi-kernel-headers

# clone repo
git clone https://github.com/umlaeute/v4l2loopback

# build
cd v4l2loopback
make -j4
sudo make install

sudo depmod
# reboot
```

### Build/Install Python libs / deps
```sh
# deps
sudo apt install \
	ffmpeg \
	python3-dev \
	libgtk-3-dev \
	libgirepository1.0-dev \
	cmake \
	libjpeg-dev

# install PyGObject
sudo pip3 install PyGObject

# install numpy
sudo pip3 install numpy

# build / install OpenCV
wget https://github.com/opencv/opencv/archive/4.0.1.zip
unzip 4.0.1.zip
mkdir opencv-4.0.1/build
cd opencv-4.0.1/build
# remove CMakeCache.txt if rebuild.
~/savacs/savacs-client/raspberrypi/setup/opencv-4.0.1-cmake.sh
time make -j4 # In my raspberry pi, user 34m26.136s.
sudo make install

# install other libs
sudo pip3 install requests
sudo pip3 install coloredlogs
sudo pip3 install pyserial
```

### Insatall motion
```sh
sudo apt install motion
```
### Write your /etc/photostand.conf
