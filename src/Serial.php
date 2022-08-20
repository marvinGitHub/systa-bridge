<?php

class Serial
{
    const OPERATING_SYSTEM_LINUX = 0;

    const SERIAL_DEVICE_NOTSET = 0;
    const SERIAL_DEVICE_SET = 1;
    const SERIAL_DEVICE_OPENED = 2;

    public $device = null;
    public $handle = null;
    public $state;
    public $buffer = "";
    public $operatingSystem;

    /**
     * This var says if buffer should be flushed by sendMessage (true) or manually (false)
     *
     * @var bool
     */
    public $autoflush = true;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->state = static::SERIAL_DEVICE_NOTSET;

        setlocale(LC_ALL, "en_US");

        if (substr(php_uname(), 0, 5) === "Linux") {
            $this->operatingSystem = static::OPERATING_SYSTEM_LINUX;
            if ($this->execute("stty --version") === 0) {
                register_shutdown_function([$this, "deviceClose"]);
            } else {
                trigger_error("No stty available, unable to run.", E_USER_ERROR);
            }
        } else {
            trigger_error("Host OS is not linux, unable tu run.", E_USER_ERROR);
            exit();
        }
    }

    /**
     * Device set public function : used to set the device name/address.
     * -> linux : use the device address, like /dev/ttyS0
     *
     * @param string $device the name of the device to be used
     * @return bool
     */
    public function deviceSet($device)
    {
        if ($this->state !== static::SERIAL_DEVICE_OPENED) {
            if ($this->operatingSystem === static::OPERATING_SYSTEM_LINUX) {
                if ($this->execute("stty -F " . $device) === 0) {
                    $this->device = $device;
                    $this->state = static::SERIAL_DEVICE_SET;
                    return true;
                }
            }

            trigger_error("Specified serial port is not valid", E_USER_WARNING);
        } else {
            trigger_error("You must close your device before to set an other one", E_USER_WARNING);
        }
        return false;
    }

    /**
     * Opens the device for reading and/or writing.
     *
     * @param string $mode Opening mode : same parameter as fopen()
     * @return bool
     */
    public function deviceOpen($mode = "r+b")
    {
        if ($this->state === static::SERIAL_DEVICE_OPENED) {
            trigger_error("The device is already opened", E_USER_NOTICE);
            return true;
        }

        if ($this->state === static::SERIAL_DEVICE_NOTSET) {
            trigger_error("The device must be set before to be open", E_USER_WARNING);
            return false;
        }

        if (!preg_match("@^[raw]\+?b?$@", $mode)) {
            trigger_error("Invalid opening mode : " . $mode . ". Use fopen() modes.", E_USER_WARNING);
            return false;
        }

        $this->handle = @fopen($this->device, $mode);

        if ($this->handle !== false) {
            stream_set_blocking($this->handle, 0);
            $this->state = static::SERIAL_DEVICE_OPENED;
            return true;
        }

        $this->handle = null;
        trigger_error("Unable to open the device", E_USER_WARNING);
        return false;
    }

    /**
     * Closes the device
     *
     * @return bool
     */
    public function deviceClose()
    {
        if ($this->state !== static::SERIAL_DEVICE_OPENED) {
            return true;
        }

        if (fclose($this->handle)) {
            $this->handle = null;
            $this->state = static::SERIAL_DEVICE_SET;
            return true;
        }

        trigger_error("Unable to close the device", E_USER_ERROR);
        return false;
    }

    /**
     * Sends a string to the device
     *
     * @param string $str string to be sent to the device
     * @param float $waitForReply time to wait for the reply (in microseconds)
     */
    public function sendMessage($str, $waitForReply = 100)
    {
        $this->buffer .= $str;

        if ($this->autoflush === true) $this->flush();

        usleep((int)$waitForReply);
    }

    /**
     * Reads the port until no new data are available, then return the content.
     *
     * @pararm int $count number of characters to be read (will stop before
     *  if less characters are in the buffer)
     * @return string
     */
    public function readPort($count = 0)
    {
        if ($this->state !== static::SERIAL_DEVICE_OPENED) {
            trigger_error("Device must be opened to read it", E_USER_WARNING);
            return false;
        }

        if ($this->operatingSystem === static::OPERATING_SYSTEM_LINUX) {
            $content = "";
            $i = 0;

            if ($count !== 0) {
                do {
                    if ($i > $count) $content .= fread($this->handle, ($count - $i));
                    else $content .= fread($this->handle, 128);
                } while (($i += 128) === strlen($content));
            } else {
                do {
                    $content .= fread($this->handle, 128);
                } while (($i += 128) === strlen($content));
            }

            return $content;
        }

        return false;
    }

    /**
     * Flushes the output buffer
     *
     * @return bool
     */
    public function flush()
    {
        if (!$this->deviceIsOpened()) return false;

        if (fwrite($this->handle, $this->buffer) !== false) {
            $this->buffer = "";
            return true;
        } else {
            $this->buffer = "";
            trigger_error("Error while sending message", E_USER_WARNING);
            return false;
        }
    }

    public function deviceIsOpened()
    {
        if ($this->state !== static::SERIAL_DEVICE_OPENED) {
            trigger_error("Device must be opened", E_USER_WARNING);
            return false;
        }

        return true;
    }

    public function execute($cmd, &$out = null)
    {
        $desc = [
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $proc = proc_open($cmd, $desc, $pipes);

        $ret = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $retVal = proc_close($proc);

        if (func_num_args() == 2) $out = [$ret, $err];
        return $retVal;
    }
}