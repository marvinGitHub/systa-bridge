<?php

class PluginOperationTimeBoiler extends PluginAbstract
{
    use IntervalAwareTrait;

    const STORAGE_KEY_TEMPERATURE_FLOW_BOILER_INITIAL = 'PluginOperationTimeBoiler.temperatureFlowBoilerInitial';
    const STORAGE_KEY_TIMESTAMP_START = 'PluginOperationTimeBoiler.timestampStart';
    const STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION = 'PluginOperationTimeBoiler.timestampNextEvaluation';

    protected function getIntervalDefault(): int
    {
        return 60;
    }

    private function init(PluginContext $context)
    {
        $context->getStorage()->set(PluginOperationTimeBoiler::STORAGE_KEY_TIMESTAMP_START, time());
        $context->getStorage()->set(PluginOperationTimeBoiler::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION, time());
        $context->getStorage()->set(PluginOperationTimeBoiler::STORAGE_KEY_TEMPERATURE_FLOW_BOILER_INITIAL, $context->getMonitor()->getTemperatureFlowBoiler());
    }

    public function run(PluginContext $context)
    {
        $timestampNextEvaluation = $context->getStorage()->get(PluginOperationTimeBoiler::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION);

        if (null === $timestampNextEvaluation) {
            $this->init($context);
            return;
        }

        if (time() < $timestampNextEvaluation) {
            return;
        }

        $context->getStorage()->set(PluginOperationTimeBoiler::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION, time() + $this->getInterval());

        $temperatureFlowBoilerInitial = $context->getStorage()->get(PluginOperationTimeBoiler::STORAGE_KEY_TEMPERATURE_FLOW_BOILER_INITIAL);
        $temperatureFlowBoilerCurrent = $context->getMonitor()->getTemperatureFlowBoiler();

        if ($temperatureFlowBoilerCurrent > $temperatureFlowBoilerInitial) {
            $operationTimeMinutes = (time() - $context->getStorage()->get(PluginOperationTimeBoiler::STORAGE_KEY_TIMESTAMP_START)) / 60;
            
            $context->getMonitor()->set('operationTimeMinutesBoiler', (int)$operationTimeMinutes);
            $this->init();
        }
    }
}