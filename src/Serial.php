<?php
define("SERIAL_DEVICE_NOTSET", 0);
define("SERIAL_DEVICE_SET", 1);
define("SERIAL_DEVICE_OPENED", 2);

class Serial
{
    public $_device = null;
    public $_dHandle = null;
    public $_dState = SERIAL_DEVICE_NOTSET;
    public $_buffer = "";
    public $_os = "";

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
        setlocale(LC_ALL, "en_US");

        $sysname = php_uname();

        if (substr($sysname, 0, 5) === "Linux") {
            $this->_os = "linux";
            if ($this->_exec("stty --version") === 0) {
                register_shutdown_function([$this, "deviceClose"]);
            } else {
                trigger_error("No stty available, unable to run.", E_USER_ERROR);
            }
        } else {
            trigger_error("Host OS is not linux,unable tu run.", E_USER_ERROR);
            exit();
        }
    }

    /**
     * Device set function : used to set the device name/address.
     * -> linux : use the device address, like /dev/ttyS0
     *
     * @param string $device the name of the device to be used
     * @return bool
     */
    function deviceSet($device)
    {
        if ($this->_dState !== SERIAL_DEVICE_OPENED) {
            if ($this->_os === "linux") {
                if (preg_match("@^COM(\d+):?$@i", $device, $matches)) {
                    $device = "/dev/ttyS" . ($matches[1] - 1);
                }

                if ($this->_exec("stty -F " . $device) === 0) {
                    $this->_device = $device;
                    $this->_dState = SERIAL_DEVICE_SET;
                    return true;
                }
            }

            trigger_error("Specified serial port is not valid", E_USER_WARNING);
            return false;
        } else {
            trigger_error("You must close your device before to set an other one", E_USER_WARNING);
            return false;
        }
    }

    /**
     * Opens the device for reading and/or writing.
     *
     * @param string $mode Opening mode : same parameter as fopen()
     * @return bool
     */
    function deviceOpen($mode = "r+b")
    {
        if ($this->_dState === SERIAL_DEVICE_OPENED) {
            trigger_error("The device is already opened", E_USER_NOTICE);
            return true;
        }

        if ($this->_dState === SERIAL_DEVICE_NOTSET) {
            trigger_error("The device must be set before to be open", E_USER_WARNING);
            return false;
        }

        if (!preg_match("@^[raw]\+?b?$@", $mode)) {
            trigger_error("Invalid opening mode : " . $mode . ". Use fopen() modes.", E_USER_WARNING);
            return false;
        }

        $this->_dHandle = @fopen($this->_device, $mode);

        if ($this->_dHandle !== false) {
            stream_set_blocking($this->_dHandle, 0);
            $this->_dState = SERIAL_DEVICE_OPENED;
            return true;
        }

        $this->_dHandle = null;
        trigger_error("Unable to open the device", E_USER_WARNING);
        return false;
    }

    /**
     * Closes the device
     *
     * @return bool
     */
    function deviceClose()
    {
        if ($this->_dState !== SERIAL_DEVICE_OPENED) {
            return true;
        }

        if (fclose($this->_dHandle)) {
            $this->_dHandle = null;
            $this->_dState = SERIAL_DEVICE_SET;
            return true;
        }

        trigger_error("Unable to close the device", E_USER_ERROR);
        return false;
    }

    /**
     * Sends a string to the device
     *
     * @param string $str string to be sent to the device
     * @param float $waitForReply time to wait for the reply (in seconds)
     */
    function sendMessage($str, $waitForReply = 0.1)
    {
        $this->_buffer .= $str;

        if ($this->autoflush === true) $this->flush();

        usleep((int) ($waitForReply * 1000000));
    }

    /**
     * Reads the port until no new datas are availible, then return the content.
     *
     * @pararm int $count number of characters to be read (will stop before
     *  if less characters are in the buffer)
     * @return string
     */
    function readPort($count = 0)
    {
        if ($this->_dState !== SERIAL_DEVICE_OPENED) {
            trigger_error("Device must be opened to read it", E_USER_WARNING);
            return false;
        }

        if ($this->_os === "linux") {
            $content = "";
            $i = 0;

            if ($count !== 0) {
                do {
                    if ($i > $count) $content .= fread($this->_dHandle, ($count - $i));
                    else $content .= fread($this->_dHandle, 128);
                } while (($i += 128) === strlen($content));
            } else {
                do {
                    $content .= fread($this->_dHandle, 128);
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
    function flush()
    {
        if (!$this->_ckOpened()) return false;

        if (fwrite($this->_dHandle, $this->_buffer) !== false) {
            $this->_buffer = "";
            return true;
        } else {
            $this->_buffer = "";
            trigger_error("Error while sending message", E_USER_WARNING);
            return false;
        }
    }

    function _ckOpened()
    {
        if ($this->_dState !== SERIAL_DEVICE_OPENED) {
            trigger_error("Device must be opened", E_USER_WARNING);
            return false;
        }

        return true;
    }

    function _exec($cmd, &$out = null)
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