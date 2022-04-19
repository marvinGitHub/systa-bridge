<?php

class SerialDeviceConfiguration
{
    private $serialDeviceName;

    public function __construct(string $serialDeviceName)
    {
        $this->serialDeviceName = $serialDeviceName;
    }

    public function findSerialDevices()
    {
        return glob('/dev/ttyUSB*');
    }

    public function serialDeviceAttached()
    {
        return file_exists($this->serialDeviceName);
    }

    public function load()
    {
        if (!$this->serialDeviceAttached()) {
            return false;
        }

        $output = null;

        if (false === exec(sprintf('stty -F %s', $this->serialDeviceName), $output)) {
            return false;
        }

        return implode(PHP_EOL, $output);
    }

    public function getExpectedConfiguration()
    {
        return <<<TXT
speed 9600 baud; line = 0;
min = 1; time = 0;
-brkint -icrnl -imaxbel
-opost -onlcr
-isig -icanon -echo
TXT;
    }

    public function alreadyConfigured()
    {
        return $this->load() === $this->getExpectedConfiguration();
    }

    public function configure()
    {
        if (!$this->serialDeviceAttached()) {
            return false;
        }

        if ($this->alreadyConfigured()) {
            return;
        }

        return exec(sprintf('stty -F %s 9600 raw -onlcr -echo', $this->serialDeviceName));
    }
}
