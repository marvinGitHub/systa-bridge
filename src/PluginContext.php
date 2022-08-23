<?php

class PluginContext
{
    private $storage;
    private $monitor;
    private $queue;
    private $log;
    private $buffer;
    private $serial;
    private $dump;

    public function __construct(KeyValueStorage $storage, StringBuffer $buffer, Serial $serial, Monitor $monitor, Queue $queue, Log $log, Dump $dump)
    {
        $this->storage = $storage;
        $this->buffer = $buffer;
        $this->serial = $serial;
        $this->monitor = $monitor;
        $this->queue = $queue;
        $this->log = $log;
        $this->dump = $dump;
    }

    public function getStorage(): KeyValueStorage
    {
        return $this->storage;
    }

    public function getBuffer(): StringBuffer
    {
        return $this->buffer;
    }

    public function getSerial(): Serial
    {
        return $this->serial;
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

    public function getDump(): Dump
    {
        return $this->dump;
    }
}