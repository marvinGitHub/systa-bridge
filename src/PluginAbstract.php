<?php

abstract class PluginAbstract
{
    private $storage;
    private $monitor;
    private $queue;
    private $log;

    final public function __construct(KeyValueStorage $storage, Monitor $monitor = null, Queue $queue = null, Log $log = null)
    {
        $this->storage = $storage;
        $this->monitor = $monitor;
        $this->queue = $queue;
        $this->log = $log;
        $this->init();
    }

    protected function getStorage(): KeyValueStorage
    {
        return $this->storage;
    }

    protected function getMonitor(): Monitor
    {
        return $this->monitor;
    }

    protected function getQueue(): Queue
    {
        return $this->queue;
    }

    protected function getLog(): Log
    {
        return $this->log;
    }

    abstract public function reset();

    abstract protected function init();

    abstract public function run();
}