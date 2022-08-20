<?php

class PluginCounterBoilerStart extends PluginAbstract
{
    use IntervalAwareTrait;

    const STORAGE_KEY_COUNTER_INITIAL = 'PluginCounterBoilerStart.counterInitial';
    const STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION = 'PluginCounterBoilerStart.timestampNextEvaludation';

    const INTERVAL_DEFAULT = 86400;

    public function reset()
    {
        $this->getStorage()->clear(PluginCounterBoilerStart::STORAGE_KEY_COUNTER_INITIAL);
        $this->getStorage()->clear(PluginCounterBoilerStart::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION);
    }

    protected function init()
    {
        $this->setInterval(PluginCounterBoilerStart::INTERVAL_DEFAULT);
        $this->getStorage()->set(PluginCounterBoilerStart::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION, time() + $this->getInterval());
        $this->getStorage()->set(PluginCounterBoilerStart::STORAGE_KEY_COUNTER_INITIAL, $this->getMonitor()->getCounterBoilerStart());
    }

    public function run()
    {
        $timestampNextEvaluation = $this->getStorage()->get(PluginCounterBoilerStart::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION);

        if (null === $timestampNextEvaluation) {
            $this->init();
            return;
        }

        if (time() >= $timestampNextEvaluation) {
            $counterBoilerStartInitial = $this->getStorage()->get(PluginCounterBoilerStart::STORAGE_KEY_COUNTER_INITIAL);
            $counterBoilerStartCurrent = $this->getMonitor()->getCounterBoilerStart();
            $this->getMonitor()->set(sprintf('counterBoilerStartInterval%uSeconds', $this->getInterval()), $counterBoilerStartCurrent - $counterBoilerStartInitial);

            $this->reset();
            $this->init();
        }
    }
}