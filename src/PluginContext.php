<?php

class PluginContext
{
    private $storage;
    private $monitor;
    private $queue;
    private $log;

    public function __construct(KeyValueStorage $storage, Monitor $monitor = null, Queue $queue = null, Log $log = null)
    {
        $this->storage = $storage;
        $this->monitor = $monitor;
        $this->queue = $queue;
        $this->log = $log;
    }

    public function getStorage(): KeyValueStorage
    {
        return $this->storage;
    }

    public function getMonitor(): Monitor
    {
        return $this->monitor;
    }

    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function getLog(): Log
    {
        return $this->log;
    }
}