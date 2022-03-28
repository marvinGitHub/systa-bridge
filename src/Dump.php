<?php

class Dump {

    private $pathname;

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
        return file_put_contents($this->pathname, $data, FILE_APPEND);
    }

    public function clear()
    {
        if (!file_exists($this->pathname)) {
            return;
        }
        return unlink($this->pathname);
    }
}
