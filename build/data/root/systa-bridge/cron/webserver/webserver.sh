#!/bin/sh
PROC=`ps | grep "0.0.0.0:8400" | grep -v grep`
if [ "$PROC" = "" ]
then
   cd /root/systa-bridge && php -S 0.0.0.0:8400 & 
fi
