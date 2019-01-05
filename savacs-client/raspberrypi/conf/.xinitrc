xrdb /etc/X11/Xresources/x11-common

# screensaver
xset s off

# display power management signaling
xset dpms 0 0 0

python \
  ~pi/savacs/savacs-client/raspberrypi/python/ui.py

