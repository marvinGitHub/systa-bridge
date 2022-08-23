<?php

class PluginTemperatureDifferenceHotWater extends PluginAbstract
{
    use IntervalAwareTrait;

    const STORAGE_KEY_TEMPERATURE_INITIAL = 'PluginTemperatureDifferenceHotWater.temperatureInitial';
    const STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION = 'PluginTemperatureDifferenceHotWater.timestampNextEvaluation';

    protected function getIntervalDefault(): int
    {
        return 3600;
    }

    private function init(PluginContext $context)
    {
        $temperatureHotWater = $context->getMonitor()->getTemperatureHotWater();

        if (!$temperatureHotWater) {
            return;
        }

        $context->getStorage()->set(PluginTemperatureDifferenceHotWater::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION, time() + $this->getInterval());
        $context->getStorage()->set(PluginTemperatureDifferenceHotWater::STORAGE_KEY_TEMPERATURE_INITIAL, $temperatureHotWater);
    }

    public function run(PluginContext $context)
    {
        $timestampNextEvaluation = $context->getStorage()->get(PluginTemperatureDifferenceHotWater::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION);

        if (null === $timestampNextEvaluation) {
            $this->init($context);
            return;
        }

        if (time() >= $timestampNextEvaluation) {
            $temperatureInitial = $context->getStorage()->get(PluginTemperatureDifferenceHotWater::STORAGE_KEY_TEMPERATURE_INITIAL);
            $temperatureCurrent = $context->getMonitor()->getTemperatureHotWater();

            $temperatureDifference = abs($temperatureCurrent - $temperatureInitial);

            $context->getMonitor()->set(sprintf('temperatureDifferenceHotWater%uSeconds', $this->getInterval()), sprintf('%s%.1f', $temperatureCurrent >= $temperatureInitial ? '+' : '-', $temperatureDifference));

            $this->init($context);
        }
    }
}