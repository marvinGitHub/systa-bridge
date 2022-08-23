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

$queue = new Queue($config['queue']);
$dump = new Dump($config['dumpfile']);
$storage = new KeyValueStorage($config['storagePath']);

$pluginHandler = new PluginHandler(new PluginContext($storage, $monitor, $queue, $log));

$pluginCounterBoilerStart = new PluginCounterBoilerStart();
$pluginCounterBoilerStart->setInterval($config['intervalCounterBoilerStart']);

$pluginTemperatureDifferenceHotWater = new PluginTemperatureDifferenceHotWater();
$pluginTemperatureDifferenceHotWater->setInterval($config['intervalTemperatureDifferenceHotWater']);

$pluginTemperatureDifferenceBufferTop = new PluginTemperatureDifferenceBufferTop();
$pluginTemperatureDifferenceBufferTop->setInterval($config['intervalTemperatureDifferenceBufferTop']);

$pluginMonitoringKeepAlive = new PluginMonitoringKeepAlive();

$pluginHandler->register($pluginCounterBoilerStart);
$pluginHandler->register($pluginTemperatureDifferenceHotWater);
$pluginHandler->register($pluginTemperatureDifferenceBufferTop);
$pluginHandler->register($pluginMonitoringKeepAlive);

function dump(string $data)
{
    global $config;
    global $dump;
    if ($config['dump']) {
        $dump->write($data);
    }
}

function sendSystaCommand($command)
{
    global $serial;
    global $log;
    $serial->sendMessage(hex2bin($command));
    $log->append(sprintf('Command %s sent to device', $command));
    dump($command);
}

function validateChecksum(string $telegram): bool
{
    global $log;
    $checksum = SystaBridge::checksum(substr($telegram, 0, strlen($telegram) - 2));
    $expected = substr($telegram, strlen($telegram) - 2);

    if ($checksum != $expected) {
        $log->append(sprintf('Checksum mismatch. telegram: %s expected: %s computed: %s', $telegram, $expected, $checksum));
        return false;
    }

    return true;
}

function determineTelegram(string $value): ?string
{
    $matches = null;
    $isTelegram =
        1 === preg_match('/(fc200c01[\da-f]{62})/', $value, $matches) ||
        1 === preg_match('/(fc220c02[\da-f]{66})/', $value, $matches) ||
        1 === preg_match('/(fd170c03[\da-f]{60})/', $value, $matches) ||
        1 === preg_match('/(fd05aa0c[\da-f]{8})/', $value, $matches) ||
        1 === preg_match('/(fd140c03[\da-f]{38})/', $value, $matches) ||
        1 === preg_match('/(fd2f0c0301[\da-f]{90})/', $value, $matches) ||
        1 === preg_match('/(fd2f0c0300[\da-f]{90})/', $value, $matches) ||
        1 === preg_match(sprintf('/(%s)/', SystaBridge::COMMAND_START_MONITORING_V1), $value, $matches) ||
        1 === preg_match(sprintf('/(%s)/', SystaBridge::COMMAND_START_MONITORING_V2), $value, $matches);

    if (!$isTelegram) {
        return null;
    }

    return $matches[1];
}

$buffer = '';

while (true) {
    $config = $configuration->load();

    sleep(1);

    if ($command = $queue->next()) {
        sendSystaCommand($command);
        dump(PHP_EOL);
    }

    $dataSerial = $serial->readPort();

    for ($i = 0; $i < strlen($dataSerial); $i++) {

        $c = ord($dataSerial{$i});

        $hex = Helper::getFixed(dechex($c));
        $buffer .= $hex;

        dump($hex);

        if (strlen($buffer) === $config['bufferLimit']) {
            $log->append('Buffer: limit reached');
            $log->append(sprintf('Buffer: %s', $buffer));
            exit;
        }
    }

    dump(PHP_EOL);

    if (null !== $telegram = determineTelegram($buffer)) {
        if (validateChecksum($telegram)) {
            $monitor->process($telegram);
        }
        $buffer = str_replace($telegram, '', $buffer);
    }

    $config['pluginMonitoringKeepAlive'] ? $pluginHandler->enable($pluginMonitoringKeepAlive) : $pluginHandler->disable($pluginMonitoringKeepAlive);
    $config['pluginCounterBoilerStart'] ? $pluginHandler->enable($pluginCounterBoilerStart) : $pluginHandler->disable($pluginCounterBoilerStart);
    $config['pluginTemperatureDifferenceHotWater'] ? $pluginHandler->enable($pluginTemperatureDifferenceHotWater) : $pluginHandler->disable($pluginTemperatureDifferenceHotWater);
    $config['pluginTemperatureDifferenceBufferTop'] ? $pluginHandler->enable($pluginTemperatureDifferenceBufferTop) : $pluginHandler->disable($pluginTemperatureDifferenceBufferTop);

    $pluginHandler->run();
}
