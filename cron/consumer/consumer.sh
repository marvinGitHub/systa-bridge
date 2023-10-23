#!/bin/sh
PROC=`ps | grep consume.php | grep -v grep`
if [ "$PROC" = "" ]
then
   php /root/systa-bridge/consume.php >> /tmp/consumer.log 2>&1
fi
