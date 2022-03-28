SystaBridge
==============================================================================

Requirements:

- Paradigma Heating with SystaComfort installed
- SystaService-Interface USB-Version ArticleNo. 09-7334
- SystaService USB Cable ArticleNo. 09-....
- PHP 7.2
- TP-Link TL WR810N v1

Installation
------------

Betriebsbereites SystaService-Interface mit USB an Rechner anschlie�en, dana
taucht unter /dev/ das passende tty-Interface dazu auf, beispielsweise ttyUSB0
oder ttyUSB1, jenachdem, welchen Port man am Rechner erwischt hat.

Zuerst muss das ttyUSBx-Interface noch richtig parametrisiert werden, damit
die Telegramme unverf�lscht weiterverarbeitet werden k�ne

bash> stty -F /dev/ttyUSB0 raw -onlcr -echo

Kontrolle:

bash> stty -F /dev/ttyUSB0

speed 9600 baud; line = 0;
min = 1; time = 0;
-brkint -icrnl -imaxbel
-opost -onlcr
-isig -icanon -echo



  
- cron/usb.php anpassen, Zeile:

  $serial->deviceSet("/dev/ttyUSB0");
  
  hier das passemde Interface eintragen (ttyUSB0, ttyUSB1 etc.)

-

- CRON
cp /root/systa-bridge/cron/systa-bridge /usr/lib/micrond.d/systa-bridge
cp /root/systa-bridge/cron/webserver/webserver /usr/lib/micrond.d/webserver
cp /root/systa-bridge/cron/logrotate/logrotate /usr/lib/micrond.d/logrotate

/etc/rc.local
/etc/init.d/micrond 

ln -s /usr/bin/php-cli /usr/bin/ph

php7-cli
Php7-mod-json
micrond
logrotate
htop
nano
coreutils-stty
kmod-usb-serial
kmod-usb-serial-fdti

- logrotate /etc/logrotate.conf
/tmp/cron.log {
        size 1024k
        create 770 root root
        su root root
        rotate 2
}

/tmp/webserver.log {
        size 1024k
        create 770 root root
        su root root
        rotate 2
}


/tmp/dump.txt {
        size 1024k
        create 770 root root
        su root root
        rotate 2
}



AllowWANWeb
AllowWANSSH
