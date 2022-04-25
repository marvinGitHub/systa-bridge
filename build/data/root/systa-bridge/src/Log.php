<?php

class Log
{
    private $pathname;

    public function __construct(string $pathname)
    {
        $this->pathname = $pathname;
        $this->init();
    }

    public function init()
    {
        if (!file_exists($this->pathname)) {
            touch($this->pathname);
            $this->append('SystaBridge System Log');
        }
    }

    public function append(string $content)
    {
        return file_put_contents($this->pathname, sprintf('[%u] %s%s', time(), $content, PHP_EOL), FILE_APPEND);
    }

    public function clear()
    {
        unlink($this->pathname);
        $this->init();
    }

    public function load()
    {
        return file_get_contents($this->pathname);
    }
}