<?php

class PluginTemperatureDifferenceBufferTop extends PluginAbstract
{
    use IntervalAwareTrait;

    const STORAGE_KEY_TEMPERATURE_INITIAL = 'PluginTemperatureDifferenceBufferTop.temperatureInitial';
    const STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION = 'PluginTemperatureDifferenceBufferTop.timestampNextEvaluation';

    const INTERVAL_DEFAULT = 3600;

    private function init(PluginContext $context)
    {
        $this->setInterval(PluginTemperatureDifferenceBufferTop::INTERVAL_DEFAULT);

        $temperatureBufferTop = $context->getMonitor()->getTemperatureBufferTop();

        if (!$temperatureBufferTop) {
            return;
        }

        $context->getStorage()->set(PluginTemperatureDifferenceBufferTop::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION, time() + $this->getInterval());
        $context->getStorage()->set(PluginTemperatureDifferenceBufferTop::STORAGE_KEY_TEMPERATURE_INITIAL, $temperatureBufferTop);
    }

    public function run(PluginContext $context)
    {
        $timestampNextEvaluation = $context->getStorage()->get(PluginTemperatureDifferenceBufferTop::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION);

        if (null === $timestampNextEvaluation) {
            $this->init($context);
            return;
        }

        if (time() >= $timestampNextEvaluation) {
            $temperatureInitial = $context->getStorage()->get(PluginTemperatureDifferenceBufferTop::STORAGE_KEY_TEMPERATURE_INITIAL);
            $temperatureCurrent = $context->getMonitor()->getTemperatureBufferTop();

            $temperatureDifference = abs($temperatureCurrent - $temperatureInitial);

            $context->getMonitor()->set(sprintf('temperatureDifferenceBufferTop%uSeconds', $this->getInterval()), sprintf('%s%.1f', $temperatureCurrent >= $temperatureInitial ? '+' : '-', $temperatureDifference));

            $this->init($context);
        }
    }
}