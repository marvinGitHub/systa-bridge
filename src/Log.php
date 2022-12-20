<?php

class Log
{
    const LOG = 'log';
    const DEBUG = 'debug';
    const ERROR = 'error';

    const INFO = 'info';

    private $pathname;

    private $verbose = false;

    public function __construct(string $pathname, bool $verbose = false)
    {
        $this->pathname = $pathname;
        $this->verbose = $verbose;
        $this->init();
    }

    public function setVerbose(bool $verbose)
    {
        $this->verbose = $verbose;
    }

    public function init()
    {
        if (!file_exists($this->pathname)) {
            touch($this->pathname);
            $this->print(Log::LOG, 'SystaBridge System Log');
        }
    }

    public function print(string $type, string $content)
    {
        $doLog = $type === Log::LOG || $type === Log::ERROR || $type === Log::INFO || $type === Log::DEBUG && $this->verbose === true;

        if (!$doLog) {
            return false;
        }

        switch ($type) {
            case Log::LOG:
                $message = sprintf('%s%s', $content, PHP_EOL);
                break;
            default:
                $message = sprintf('[%u] (%s) %s%s', time(), $type, $content, PHP_EOL);
                break;
        }

        return file_put_contents($this->pathname, $message, FILE_APPEND);
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