<?php

class PluginOperationTimeBoiler extends PluginAbstract
{
    use IntervalAwareTrait;

    private $temperatures = [];

    const STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION = 'PluginOperationTimeBoiler.timestampNextEvaluation';

    protected function getIntervalDefault(): int
    {
        return 60;
    }

    private function init(PluginContext $context)
    {
        $this->temperatures = [];

        $context->getStorage()->set(PluginOperationTimeBoiler::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION, time());
    }

    private function getTemperatureMax(): int
    {
        return end($this->temperatures) ?? 0;
    }

    public function run(PluginContext $context)
    {
        $storage = $context->getStorage();

        $timestampNextEvaluation = $storage->get(PluginOperationTimeBoiler::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION);

        if (null === $timestampNextEvaluation) {
            $this->init($context);
            return;
        }

        if (time() < $timestampNextEvaluation) {
            return;
        }

        $storage->set(PluginOperationTimeBoiler::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION, time() + $this->getInterval());

        $temperatureFlowBoilerCurrent = $context->getMonitor()->getTemperatureFlowBoiler();

        if (!$temperatureFlowBoilerCurrent) {
            return;
        }

        $this->temperatures[$temperatureFlowBoilerCurrent] = time();
        ksort($this->temperatures);

        if ($temperatureFlowBoilerCurrent >= $this->getTemperatureMax()) {
            return; // peak not reached yet
        }

        $start = array_shift($this->temperatures);
        if (empty($start)) {
            return;
        }

        $end = array_pop($this->temperatures);
        if (empty($end)) {
            return;
        }

        $operationTimeMinutes = ($end - $start) / 60;

        $context->getMonitor()->set('operationTimeMinutesBoiler', (int)$operationTimeMinutes);

        $this->init($context);
    }
}