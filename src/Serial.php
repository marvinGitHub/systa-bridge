<?php

class Serial
{
    const SERIAL_DEVICE_NOTSET = 0;
    const SERIAL_DEVICE_SET = 1;
    const SERIAL_DEVICE_OPENED = 2;

    public $device;
    public $handle;
    public $state;
    public $buffer = '';

    /**
     * This var says if buffer should be flushed by sendMessage (true) or manually (false)
     *
     * @var bool
     */
    public $autoflush = true;

    /**
     * Constructor
     *
     * @throws RuntimeException
     */
    public function __construct()
    {
        $this->state = static::SERIAL_DEVICE_NOTSET;

        if (Helper::execute('stty --version') === 0) {
            register_shutdown_function([$this, 'deviceClose']);
        } else {
            throw new RuntimeException('No stty available, unable to run.');
        }
    }

    /**
     * Device set public function : used to set the device name/address.
     * -> linux : use the device address, like /dev/ttyS0
     *
     * @param string $device the name of the device to be used
     * @return bool
     * @throws RuntimeException
     */
    public function deviceSet(string $device): bool
    {
        if ($this->state !== static::SERIAL_DEVICE_OPENED) {
            if (Helper::execute('stty -F ' . $device) === 0) {
                $this->device = $device;
                $this->state = static::SERIAL_DEVICE_SET;
                return true;
            }

            throw new RuntimeException('Specified serial port is not valid');
        }

        throw new RuntimeException('You must close your device before to set an other one');
    }

    /**
     * Opens the device for reading and/or writing.
     *
     * @param string $mode Opening mode : same parameter as fopen()
     * @return bool
     * @throws RuntimeException
     */
    public function deviceOpen(string $mode = 'r+b'): bool
    {
        if ($this->state === static::SERIAL_DEVICE_OPENED) {
            throw new RuntimeException('The device is already opened');
        }

        if ($this->state === static::SERIAL_DEVICE_NOTSET) {
            throw new RuntimeException('The device must be set before to be open');
        }

        if (!preg_match('@^[raw]\+?b?$@', $mode)) {
            throw new RuntimeException(sprintf('Invalid opening mode : %s. Use fopen() modes.', $mode));
        }

        $this->handle = @fopen($this->device, $mode);

        if ($this->handle !== false) {
            stream_set_blocking($this->handle, 0);
            $this->state = static::SERIAL_DEVICE_OPENED;
            return true;
        }

        $this->handle = null;

        throw new RuntimeException('Unable to open the device');
    }

    /**
     * Closes the device
     *
     * @return bool
     * @throws RuntimeException
     */
    public function deviceClose(): bool
    {
        if ($this->state !== static::SERIAL_DEVICE_OPENED) {
            return true;
        }

        if (fclose($this->handle)) {
            $this->handle = null;
            $this->state = static::SERIAL_DEVICE_SET;
            return true;
        }

        throw new RuntimeException('Unable to close the device');
    }

    /**
     * Sends a string to the device
     *
     * @param string $str string to be sent to the device
     * @param int $waitForReply time to wait for the reply (in microseconds)
     */
    public function sendMessage(string $str, int $waitForReply = 100)
    {
        $this->buffer .= $str;

        if ($this->autoflush === true) $this->flush();

        usleep($waitForReply);
    }

    /**
     * Reads the port until no new data are available, then return the content.
     *
     * @param int $count number of characters to be read (will stop before
     *  if less characters are in the buffer)
     * @param int $length
     * @return string
     */
    public function readPort(int $count = 0, int $length = 128): string
    {
        if ($this->state !== static::SERIAL_DEVICE_OPENED) {
            throw new RuntimeException('Device must be opened to read it');
        }

        $content = '';
        $i = 0;

        if ($count !== 0) {
            do {
                if ($i > $count) $content .= fread($this->handle, ($count - $i));
                else $content .= fread($this->handle, $length);
            } while (($i += $length) === strlen($content));
        } else {
            do {
                $content .= fread($this->handle, $length);
            } while (($i += $length) === strlen($content));
        }

        return $content;
    }

    /**
     * Flushes the output buffer
     *
     * @return bool
     */
    public function flush(): bool
    {
        if ($this->state !== static::SERIAL_DEVICE_OPENED) return false;

        $bufferWritten = fwrite($this->handle, $this->buffer);
        $this->buffer = '';

        if (!$bufferWritten) {
            throw new RuntimeException('Error while sending message');
        }

        return true;
    }

    public function getState(): int
    {
        return $this->state;
    }
}