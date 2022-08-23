<?php

class PluginMonitoringKeepAlive extends PluginAbstract
{
    const STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION = 'PluginMonitoringKeepAlive.timestampNextEvaluation';

    use IntervalAwareTrait;

    protected function getIntervalDefault(): int
    {
        return 60;
    }

    private function init(PluginContext $context)
    {
        $context->getStorage()->set(PluginMonitoringKeepAlive::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION, time() + $this->getInterval());
    }

    public function run(PluginContext $context)
    {
        $timestampNextEvaluation = $context->getStorage()->get(PluginMonitoringKeepAlive::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION);

        if (null === $timestampNextEvaluation) {
            $this->init($context);
            return;
        }

        if (time() >= $timestampNextEvaluation) {
            $context->getQueue()->queue(SystaBridge::COMMAND_START_MONITORING_V2);
            $context->getLog()->append('Keep alive packet command has been added to queue.');

            $this->init($context);
        }
    }
}