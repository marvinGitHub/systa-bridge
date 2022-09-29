<?php

class PluginAveragePricePellet extends PluginAbstract
{
    const STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION = 'PluginAveragePricePellet.timestampNextEvaluation';

    use IntervalAwareTrait;

    protected function getIntervalDefault(): int
    {
        return 3600;
    }

    public function run(PluginContext $context)
    {
        $timestampNextEvaluation = $context->getStorage()->get(PluginAveragePricePellet::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION);

        if (null === $timestampNextEvaluation) {
            $context->getStorage()->set(PluginAveragePricePellet::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION, time());
            return;
        }

        if (time() < $timestampNextEvaluation) {
            return;
        }

        $context->getStorage()->set(PluginAveragePricePellet::STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION, time() + $this->getInterval());

        $client = new HttpClient();
        $response = $client->postFormUrlEncodedAcceptJson('http://www.heizpellets24.de/ChartHandler.ashx', [
            'ProductId' => 1,
            'CountryId' => 1,
            'chartMode' => 3,
            'defaultRange' => false
        ]);

        if (!$response) {
            $context->getLog()->append(sprintf('%s: no price data received', static::class));
            return;
        }

        $priceAveragePerTon = array_pop($response)['value'];
        $priceAveragePerKW = ($priceAveragePerTon / 1000) / 4.8;

        $context->getMonitor()->set('pelletPriceAveragePerTon', round($priceAveragePerTon, 2));
        $context->getMonitor()->set('pelletPriceAveragePerKW', round($priceAveragePerKW, 2));
        $context->getMonitor()->save();
    }
}