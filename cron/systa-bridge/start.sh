#!/bin/sh
PROC=`ps | grep cron.php | grep -v grep`
if [ "$PROC" = "" ]
then
   php -q /root/systa-bridge/cron.php >> /tmp/cron.log
fi
