<?php

class PluginAveragePricePellet extends PluginAbstract
{
    const STORAGE_KEY_TIMESTAMP_NEXT_EVALUATION = 'PluginAveragePricePellet.timestampNextEvaluation';
    const KG_PER_BAG = 15;
    const KWH_PER_KG = 4.8;

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
            $context->getLog()->print('info', sprintf('%s: no price data received', static::class));
            return;
        }

        $configuration = $context->getConfiguration()->load();
        $averageConsumption = $configuration['pluginAveragePricePellet.averageConsumption'];

        $priceAveragePerTon = array_pop($response)['value'];
        $priceAveragePerBag = ($priceAveragePerTon / 1000) * PluginAveragePricePellet::KG_PER_BAG;
        $priceAveragePerKWh = ($priceAveragePerTon / 1000) / PluginAveragePricePellet::KWH_PER_KG;
        $costsAveragePerMonth = $priceAveragePerKWh * ($averageConsumption / 12);

        $context->getMonitor()->set('pelletPriceAveragePerTon', round($priceAveragePerTon, 2));
        $context->getMonitor()->set('pelletPriceAveragePerKWh', round($priceAveragePerKWh, 2));
        $context->getMonitor()->set('pelletPriceAveragePerBag', round($priceAveragePerBag, 2));
        $context->getMonitor()->set('pelletCostsAveragePerMonth', round($costsAveragePerMonth, 2));
        $context->getMonitor()->set('pelletAverageConsumption', $averageConsumption);
        $context->getMonitor()->save();
    }
}