<?php

class PluginOperationTimeBoiler extends PluginAbstract
{
    const TOLERANCE_TEMPERATURE_FLOW_BOILER = 3;

    private $timestampNextEvaluation;
    private $temperatureMin;
    private $temperatureMax;
    private $timestampTemperatureMin;
    private $timestampTemperatureMax;

    use IntervalAwareTrait;

    protected function getIntervalDefault(): int
    {
        return 60;
    }

    public function run(PluginContext $context)
    {
        if (time() < (int)$this->timestampNextEvaluation) {
            return;
        }

        $this->timestampNextEvaluation = time() + $this->getInterval();

        $temperatureCurrent = $context->getMonitor()->getTemperatureFlowBoiler();

        if (!$temperatureCurrent) {
            return;
        }

        if (empty($this->temperatureMin) || $temperatureCurrent <= $this->temperatureMin) {
            $this->temperatureMin = $temperatureCurrent;
            $this->timestampTemperatureMin = time();
            return;
        }

        if (empty($this->temperatureMax) || $temperatureCurrent >= $this->temperatureMax) {
            $this->temperatureMax = $temperatureCurrent;
            $this->timestampTemperatureMax = time();
            return;
        }

        if ($temperatureCurrent + PluginOperationTimeBoiler::TOLERANCE_TEMPERATURE_FLOW_BOILER >= $this->temperatureMax) {
            return;
        }

        $operationTimeMinutes = ($this->timestampTemperatureMax - $this->timestampTemperatureMin) / 60;

        $context->getMonitor()->set('timestampOperationTimeStart', $this->timestampTemperatureMin);
        $context->getMonitor()->set('timestampOperationTimeEnd', $this->timestampTemperatureMax);
        $context->getMonitor()->set('operationTimeMinutesBoiler', (int)$operationTimeMinutes);

        $this->temperatureMin = null;
        $this->temperatureMax = null;
        $this->timestampTemperatureMin = null;
        $this->timestampTemperatureMax = null;
    }
}