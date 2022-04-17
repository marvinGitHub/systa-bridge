#!/bin/sh

find . -name .DS_Store | xargs rm

[ -f ./build/data ] && rm -rf ./build/data

mkdir -p ./build/{control,data}
mkdir -p ./build/data/root/systa-bridge/
mkdir -p ./build/data/etc/logrotate.conf.d/
mkdir -p ./build/data/usr/lib/micron.d/

cp -r ./cron ./build/data/root/systa-bridge/
cp -r ./src ./build/data/root/systa-bridge/
cp -r ./config ./build/data/root/systa-bridge/

cp ./cron.php ./build/data/root/systa-bridge/
cp ./server.php ./build/data/root/systa-bridge/
cp ./index.php ./build/data/root/systa-bridge/

cp ./config/logrotate.conf ./build/data/etc/logrotate.conf.d/systa-bridge

cp ./cron/logrotate/logrotate ./build/data/usr/lib/micron.d/
cp ./cron/systa-bridge/systa-bridge ./build/data/usr/lib/micron.d/
cp ./cron/webserver/webserver ./build/data/usr/lib/micron.d/

rm ./build/data/root/systa-bridge/config/logrotate.conf

cd ./build

echo 2.0 > debian-binary

cd ./control && gtar --numeric-owner -cvzf ../control.tar.gz ./* && cd ..
cd ./data && gtar --numeric-owner -cvzf ../data.tar.gz ./* && cd ..

gtar -cvzf "package.tar" "./control.tar.gz" "./data.tar.gz" "./debian-binary"

mv "package.tar" "systa-bridge.ipk"

rm "control.tar.gz"
rm "data.tar.gz"

mv "systa-bridge.ipk" "../dist/"

cd ..
rm -rf ./build/data