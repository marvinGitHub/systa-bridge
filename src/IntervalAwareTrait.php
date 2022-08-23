<?php

trait IntervalAwareTrait
{
    private $interval;

    abstract protected function getIntervalDefault(): int;

    public function getInterval(): ?int
    {
        if (null === $this->interval) {
            return $this->getIntervalDefault();
        }

        return $this->interval;
    }

    public function setInterval(int $interval)
    {
        $this->interval = $interval;
    }
}