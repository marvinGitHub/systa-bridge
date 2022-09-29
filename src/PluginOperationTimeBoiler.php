<?php

class PluginOperationTimeBoiler extends PluginAbstract
{
    const TOLERANCE_TEMPERATURE_FLOW_BOILER = 3;
    const PERIOD_LENGTH_DEFAULT = 3600 * 24;

    private $timestampNextEvaluation;
    private $temperatureMin;
    private $temperatureMax;
    private $timestampTemperatureMin;
    private $timestampTemperatureMax;
    private $operationTimes = [];
    private $periodLength = PluginOperationTimeBoiler::PERIOD_LENGTH_DEFAULT;
    private $tolerance = PluginOperationTimeBoiler::TOLERANCE_TEMPERATURE_FLOW_BOILER;

    use IntervalAwareTrait;

    /**
     * Get period length in seconds
     *
     * @return int
     */
    public function getPeriodLength(): int
    {
        return $this->periodLength;
    }

    public function setPeriodLength(int $seconds)
    {
        $this->periodLength = $seconds;
    }

    protected function getIntervalDefault(): int
    {
        return 60;
    }

    public function getToleranceTemperatureFlowBoiler(): int
    {
        return $this->tolerance;
    }

    public function setToleranceTemperatureFlowBoiler(int $tolerance)
    {
        $this->tolerance = $tolerance;
    }

    public function run(PluginContext $context)
    {
        if (time() < (int)$this->timestampNextEvaluation) {
            return;
        }

        $this->timestampNextEvaluation = time() + $this->getInterval();

        $context->getMonitor()->set('operationTimeMinutesPeriod', $this->getOperationTimeMinutesPeriod());

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

        if ($temperatureCurrent + $this->getToleranceTemperatureFlowBoiler() >= $this->temperatureMax) {
            return;
        }

        $operationTimeMinutes = ($this->timestampTemperatureMax - $this->timestampTemperatureMin) / 60;

        if (0 >= $operationTimeMinutes) {
            return;
        }

        $this->operationTimes[] = [$this->timestampTemperatureMin, $this->timestampTemperatureMax];

        $context->getMonitor()->set('timestampOperationTimeStart', $this->timestampTemperatureMin);
        $context->getMonitor()->set('timestampOperationTimeEnd', $this->timestampTemperatureMax);
        $context->getMonitor()->set('operationTimeMinutesBoiler', (int)$operationTimeMinutes);

        $this->temperatureMin = null;
        $this->temperatureMax = null;
        $this->timestampTemperatureMin = null;
        $this->timestampTemperatureMax = null;
    }

    private function getOperationTimeMinutesPeriod(): int
    {
        $operationTimeSecondsPeriod = 0;

        $periodEnd = time();
        $periodStart = time() - $this->getPeriodLength();

        foreach ($this->operationTimes as $i => $operationTime) {
            $intersectionSeconds = Helper::getPeriodIntersectionSeconds(
                $periodStart,
                $periodEnd,
                $operationTime[0],
                $operationTime[1]
            );
            if (!$intersectionSeconds) {
                unset($this->operationTimes[$i]);
            }
            $operationTimeSecondsPeriod += $intersectionSeconds;
        }

        return (int)($operationTimeSecondsPeriod / 60);
    }
}