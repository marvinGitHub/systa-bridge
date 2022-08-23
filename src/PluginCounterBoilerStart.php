<?php

class PluginCounterBoilerStart extends PluginAbstract
{
    use IntervalAwareTrait;

    const STORAGE_KEY_COUNTER_INITIAL = 'PluginCounterBoilerStart.counterInitial';
    const STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION = 'PluginCounterBoilerStart.timestampNextEvaluation';

    protected function getIntervalDefault(): int
    {
        return 86400;
    }

    private function init(PluginContext $context)
    {
        $context->getStorage()->set(PluginCounterBoilerStart::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION, time() + $this->getInterval());
        $context->getStorage()->set(PluginCounterBoilerStart::STORAGE_KEY_COUNTER_INITIAL, $context->getMonitor()->getCounterBoilerStart());
    }

    public function run(PluginContext $context)
    {
        $timestampNextEvaluation = $context->getStorage()->get(PluginCounterBoilerStart::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION);

        if (null === $timestampNextEvaluation) {
            $this->init($context);
            return;
        }

        if (time() >= $timestampNextEvaluation) {
            $counterBoilerStartInitial = $context->getStorage()->get(PluginCounterBoilerStart::STORAGE_KEY_COUNTER_INITIAL);
            $counterBoilerStartCurrent = $context->getMonitor()->getCounterBoilerStart();

            $context->getMonitor()->set(sprintf('counterBoilerStartInterval%uSeconds', $this->getInterval()), $counterBoilerStartCurrent - $counterBoilerStartInitial);

            $this->init($context);
        }
    }
}