<?php

class PluginOperationTimeBoiler extends PluginAbstract
{
    const TOLERANCE_TEMPERATURE_FLOW_BOILER = 2.5;

    use IntervalAwareTrait;

    protected function getIntervalDefault(): int
    {
        return 60;
    }

    public function run(PluginContext $context)
    {
        static $timestampNextEvaluation, $timestampTemperatureMin, $timestampTemperatureMax, $temperatureMin, $temperatureMax;

        if (null === $timestampNextEvaluation) {
            $timestampNextEvaluation = time();
            return;
        }

        if (time() < $timestampNextEvaluation) {
            return;
        }

        $timestampNextEvaluation = time() + $this->getInterval();

        $temperatureFlowBoilerCurrent = $context->getMonitor()->getTemperatureFlowBoiler();

        if (!$temperatureFlowBoilerCurrent) {
            return;
        }

        if (empty($temperatureMin) || $temperatureFlowBoilerCurrent <= $temperatureMin) {
            $temperatureMin = $temperatureFlowBoilerCurrent;
            $timestampTemperatureMin = time();
        }

        if (empty($temperatureMax) || $temperatureFlowBoilerCurrent >= $temperatureMax) {
            $temperatureMax = $temperatureFlowBoilerCurrent;
            $timestampTemperatureMax = time();
        }

        if ($temperatureFlowBoilerCurrent + PluginOperationTimeBoiler::TOLERANCE_TEMPERATURE_FLOW_BOILER < $temperatureMax) {
            $operationTimeMinutes = ($timestampTemperatureMax - $timestampTemperatureMin) / 60;

            $context->getMonitor()->set('operationTimeMinutesBoiler', (int)$operationTimeMinutes);

            $temperatureMin = $temperatureMax = null;
        }
    }
}