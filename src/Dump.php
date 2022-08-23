<?php

class Dump
{
    private $pathname;
    private $enabled = true;

    public function __construct(string $pathname)
    {
        $this->pathname = $pathname;
    }

    public function load()
    {
        if (!file_exists($this->pathname)) {
            return false;
        }
        return file_get_contents($this->pathname);
    }

    public function write(string $data)
    {
        if (!$this->enabled) {
            return;
        }

        return file_put_contents($this->pathname, $data, FILE_APPEND);
    }

    public function clear()
    {
        if (!file_exists($this->pathname)) {
            return;
        }
        return unlink($this->pathname);
    }

    public function enable()
    {
        $this->enabled = true;
    }

    public function disable()
    {
        $this->enabled = false;
    }
}
