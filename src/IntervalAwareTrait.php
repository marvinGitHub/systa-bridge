<?php

trait IntervalAwareTrait
{
    private $interval;

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function setInterval(int $interval)
    {
        $this->interval = $interval;
    }
}