<?php

class Queue
{

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

    public function queue($command)
    {
        return file_put_contents($this->pathname, sprintf('%s%s', $command, PHP_EOL), FILE_APPEND);
    }

    public function clear()
    {
        return file_put_contents($this->pathname, '');
    }

    public function next()
    {
        if (false === $queue = $this->load()) {
            return false;
        }

        $commands = explode(PHP_EOL, $queue);
        $next = array_shift($commands);

        $this->clear();
        foreach ($commands as $command) {
            $this->queue($command);
        }
        return $next;
    }
}
