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

    public function serialDeviceAttached(): bool
    {
        return file_exists($this->serialDeviceName);
    }

    public function load()
    {
        if (!$this->serialDeviceAttached()) {
            return false;
        }

        $output = null;

        if (false === exec(sprintf('stty -F %s', escapeshellarg($this->serialDeviceName)), $output)) {
            return false;
        }

        return implode(PHP_EOL, $output);
    }

    public function getExpectedConfiguration(): string
    {
        return <<<TXT
speed 9600 baud; line = 0;
min = 1; time = 0;
-brkint -icrnl -imaxbel
-opost -onlcr
-isig -icanon -echo
TXT;
    }

    public function alreadyConfigured(): bool
    {
        return $this->load() === $this->getExpectedConfiguration();
    }

    public function configure()
    {
        if (!$this->serialDeviceAttached()) {
            return false;
        }

        if ($this->alreadyConfigured()) {
            return true;
        }

        return exec(sprintf('stty -F %s 9600 raw -onlcr -echo', escapeshellarg($this->serialDeviceName)));
    }
}
