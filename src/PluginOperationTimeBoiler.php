<?php

class PluginOperationTimeBoiler extends PluginAbstract
{
    use IntervalAwareTrait;

    const STORAGE_KEY_TEMPERATURE_FLOW_BOILER_MIN = 'PluginOperationTimeBoiler.temperatureFlowBoilerMin';
    const STORAGE_KEY_TEMPERATURE_FLOW_BOILER_MAX = 'PluginOperationTimeBoiler.temperatureFlowBoilerMax';
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
        $context->getStorage()->set(PluginOperationTimeBoiler::STORAGE_KEY_TEMPERATURE_FLOW_BOILER_MAX, $context->getMonitor()->getTemperatureFlowBoiler());
        $context->getStorage()->set(PluginOperationTimeBoiler::STORAGE_KEY_TEMPERATURE_FLOW_BOILER_MIN, $context->getMonitor()->getTemperatureFlowBoiler());
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

        if ($temperatureFlowBoilerCurrent < $storage->get(PluginOperationTimeBoiler::STORAGE_KEY_TEMPERATURE_FLOW_BOILER_MIN)) {
            $storage->set(PluginOperationTimeBoiler::STORAGE_KEY_TEMPERATURE_FLOW_BOILER_MIN, $temperatureFlowBoilerCurrent);
            $storage->set(PluginOperationTimeBoiler::STORAGE_KEY_TIMESTAMP_START, time());
        } elseif ($temperatureFlowBoilerCurrent > $storage->get(PluginOperationTimeBoiler::STORAGE_KEY_TEMPERATURE_FLOW_BOILER_MAX)) {
            $storage->set(PluginOperationTimeBoiler::STORAGE_KEY_TEMPERATURE_FLOW_BOILER_MAX, $temperatureFlowBoilerCurrent);
            $operationTimeMinutes = (time() - $storage->get(PluginOperationTimeBoiler::STORAGE_KEY_TIMESTAMP_START)) / 60;
            $context->getMonitor()->set('operationTimeMinutesBoiler', (int)$operationTimeMinutes);
        }
    }
}