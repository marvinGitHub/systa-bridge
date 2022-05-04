# SystaBridge

## Requirements

- Paradigma SystaComfort Controller Generation 1 ArticleNo. 09-7301 (only works in combination with LON Module
  ArticleNo. 09-7325)
- Paradigma SystaService-Interface USB-Version ArticleNo. 09-7334
- Paradigma SystaService USB Cable ArticleNo. 09-7337
- TP-Link TL-WR810N v1 running OpenWrt 19.07.10

## Supported Firmware Versions

- Systa Comfort Generation 1 Rev. 1.32.1 (build date 29.09.2008)

## Build Instructions

The OpenWRT installation package can be build using the below make target. The final package will be stored
as ```dist/systa-bridge.ipk```

```bash
make build-opkg
```

## Installation

Connect the TL-WR810N v1 to the internet and refresh the package list inside the software section. Afterwards upload and
install ./dist/systa-bridge.ipk.
Once done please reboot the device. The webserver should then respond after a few minutes on 0.0.0.0:8400

## Configuration

```bash
bash> stty -F /dev/ttyUSB0 raw -onlcr -echo
bash> stty -F /dev/ttyUSB0
```

**Expected Output**

```bash
speed 9600 baud; line = 0;
min = 1; time = 0;
-brkint -icrnl -imaxbel
-opost -onlcr
-isig -icanon -echo
```