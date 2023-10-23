<?php

require 'bootstrap.php';

$configuration = new Configuration(__DIR__ . '/config/config.json', __DIR__ . '/config/default.json');
if (false === $config = $configuration->load()) {
    echo 'Please check system configuration.';
    exit;
}

$log = new Log($config['logfile'], $config['verbose']);

$monitor = new Monitor($configuration);

$serialDeviceConfiguration = new SerialDeviceConfiguration($serialDevice = $config['serialDevice']);
$serialDeviceConfiguration->configure();

$serial = new Serial();
$serial->deviceSet($serialDevice);
$serial->deviceOpen();

$buffer = new StringBuffer();
$queue = new Queue($config['queue']);
$dump = new Dump($config['dumpfile']);
$storage = new KeyValueStorage($config['storagePath']);

$pluginContext = new PluginContext($configuration, $storage, $buffer, $serial, $monitor, $queue, $log, $dump);
$pluginHandler = new PluginHandler($pluginContext);

$pluginCommandQueue = new PluginCommandQueue();
$pluginSerialProcessor = new PluginSerialProcessor();
$pluginTelegramProcessor = new PluginTelegramProcessor();

$pluginAveragePricePellet = new PluginAveragePricePellet();

$pluginCounterBoilerStart = new PluginCounterBoilerStart();
$pluginCounterBoilerStart->setInterval($config['pluginCounterBoilerStart.interval']);

$pluginTemperatureDifferenceHotWater = new PluginTemperatureDifferenceHotWater();
$pluginTemperatureDifferenceHotWater->setInterval($config['pluginTemperatureDifferenceHotWater.interval']);

$pluginTemperatureDifferenceBufferTop = new PluginTemperatureDifferenceBufferTop();
$pluginTemperatureDifferenceBufferTop->setInterval($config['pluginTemperatureDifferenceBufferTop.interval']);

$pluginOperationTimeBoiler = new PluginOperationTimeBoiler();
$pluginOperationTimeBoiler->setInterval($config['pluginOperationTimeBoiler.interval']);
$pluginOperationTimeBoiler->setPeriodLength($config['pluginOperationTimeBoiler.periodLength']);
$pluginOperationTimeBoiler->setToleranceTemperatureFlowBoiler($config['pluginOperationTimeBoiler.toleranceTemperatureFlowBoiler']);

$pluginMonitoringKeepAlive = new PluginMonitoringKeepAlive();

$pluginMQTTPublisher = new PluginMQTTPublisher($config['pluginMQTTPublisher.mqttBroker']);

$pluginHandler->register($pluginMonitoringKeepAlive);
$pluginHandler->register($pluginCommandQueue);
$pluginHandler->register($pluginSerialProcessor);
$pluginHandler->register($pluginTelegramProcessor);
$pluginHandler->register($pluginCounterBoilerStart);
$pluginHandler->register($pluginTemperatureDifferenceHotWater);
$pluginHandler->register($pluginTemperatureDifferenceBufferTop);
$pluginHandler->register($pluginOperationTimeBoiler);
$pluginHandler->register($pluginAveragePricePellet);
$pluginHandler->register($pluginMQTTPublisher);

while (true) {
    $config = $configuration->load();

    $log->setVerbose($config['verbose']);

    $config['dump'] ? $dump->enable() : $dump->disable();

    sleep(1);

    $config['pluginMQTTPublisher'] ? $pluginHandler->enable($pluginMQTTPublisher) : $pluginHandler->disable($pluginMQTTPublisher);
    $config['pluginMonitoringKeepAlive'] ? $pluginHandler->enable($pluginMonitoringKeepAlive) : $pluginHandler->disable($pluginMonitoringKeepAlive);
    $config['pluginCounterBoilerStart'] ? $pluginHandler->enable($pluginCounterBoilerStart) : $pluginHandler->disable($pluginCounterBoilerStart);
    $config['pluginTemperatureDifferenceHotWater'] ? $pluginHandler->enable($pluginTemperatureDifferenceHotWater) : $pluginHandler->disable($pluginTemperatureDifferenceHotWater);
    $config['pluginTemperatureDifferenceBufferTop'] ? $pluginHandler->enable($pluginTemperatureDifferenceBufferTop) : $pluginHandler->disable($pluginTemperatureDifferenceBufferTop);
    $config['pluginOperationTimeBoiler'] ? $pluginHandler->enable($pluginOperationTimeBoiler) : $pluginHandler->disable($pluginOperationTimeBoiler);
    $config['heatingSource'] === 'pellet' ? $pluginHandler->enable($pluginAveragePricePellet) : $pluginHandler->disable($pluginAveragePricePellet);

    $pluginHandler->run();
}
