#!/bin/sh
PROC=`ps | grep consume.php | grep -v grep`
if [ "$PROC" = "" ]
then
   php -q /root/systa-bridge/consume.php >> /tmp/consumer.log
fi
