<?php

require 'src/autoload.php';

ini_set('serialize_precision', 10);

$configuration = new Configuration(__DIR__ . '/config/config.json', __DIR__ . '/config/default.json');
if (false === $config = $configuration->load()) {
    echo 'Please check system configuration.';
    exit;
}

$log = new Log($config['logfile']);

$monitor = new Monitor($config['monitor']);

$serialDeviceConfiguration = new SerialDeviceConfiguration($serialDevice = $config['serialDevice']);
$serialDeviceConfiguration->configure();

$serial = new Serial();
$serial->deviceSet($serialDevice);
$serial->deviceOpen();

$buffer = new StringBuffer();
$queue = new Queue($config['queue']);
$dump = new Dump($config['dumpfile']);
$storage = new KeyValueStorage($config['storagePath']);

$pluginHandler = new PluginHandler(new PluginContext($storage, $buffer, $serial, $monitor, $queue, $log, $dump));

$pluginCommandQueue = new PluginCommandQueue();
$pluginSerialProcessor = new PluginSerialProcessor();
$pluginTelegramProcessor = new PluginTelegramProcessor();

$pluginCounterBoilerStart = new PluginCounterBoilerStart();
$pluginCounterBoilerStart->setInterval($config['intervalCounterBoilerStart']);

$pluginTemperatureDifferenceHotWater = new PluginTemperatureDifferenceHotWater();
$pluginTemperatureDifferenceHotWater->setInterval($config['intervalTemperatureDifferenceHotWater']);

$pluginTemperatureDifferenceBufferTop = new PluginTemperatureDifferenceBufferTop();
$pluginTemperatureDifferenceBufferTop->setInterval($config['intervalTemperatureDifferenceBufferTop']);

$pluginMonitoringKeepAlive = new PluginMonitoringKeepAlive();

$pluginHandler->register($pluginMonitoringKeepAlive);
$pluginHandler->register($pluginCommandQueue);
$pluginHandler->register($pluginSerialProcessor);
$pluginHandler->register($pluginTelegramProcessor);
$pluginHandler->register($pluginCounterBoilerStart);
$pluginHandler->register($pluginTemperatureDifferenceHotWater);
$pluginHandler->register($pluginTemperatureDifferenceBufferTop);

while (true) {
    $config = $configuration->load();

    $config['dump'] ? $dump->enable() : $dump->disable();

    sleep(1);

    $config['pluginMonitoringKeepAlive'] ? $pluginHandler->enable($pluginMonitoringKeepAlive) : $pluginHandler->disable($pluginMonitoringKeepAlive);
    $config['pluginCounterBoilerStart'] ? $pluginHandler->enable($pluginCounterBoilerStart) : $pluginHandler->disable($pluginCounterBoilerStart);
    $config['pluginTemperatureDifferenceHotWater'] ? $pluginHandler->enable($pluginTemperatureDifferenceHotWater) : $pluginHandler->disable($pluginTemperatureDifferenceHotWater);
    $config['pluginTemperatureDifferenceBufferTop'] ? $pluginHandler->enable($pluginTemperatureDifferenceBufferTop) : $pluginHandler->disable($pluginTemperatureDifferenceBufferTop);

    $pluginHandler->run();
}
