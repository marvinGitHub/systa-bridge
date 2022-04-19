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

function validateChecksum(string $telegram)
{
    global $log;
    $checksum = SystaBridge::checksum(substr($telegram, 0, strlen($telegram) - 2));
    $expected = substr($telegram, strlen($telegram) - 2);

    if ($checksum != $expected) {
        $log->append(sprintf('Checksum mismatch. telegram: %s expected: %s actual: %s', $telegram, $expected, $checksum));
        return false;
    }

    return true;
}

$keepAliveCounter = null;
$buffer = '';

while (true) {
    $config = $configuration->load();

    sleep(1);

    if ($command = $queue->next()) {
        sendSystaCommand($command);
        dump(PHP_EOL);
    }

    if ($config['monitoring']) {
        $currentMinute = date('i');
        if ($currentMinute != $keepAliveCounter) {
            // request service interface to collect monitoring data every 5-8 seconds for 3 minutes, this command needs to be repeated every minute
            sendSystaCommand(SystaBridge::COMMAND_START_MONITORING_V2);
            dump(PHP_EOL);
            $keepAliveCounter = $currentMinute;
            $log->append('Keep alive packet sent.');
        }
    }

    $incomingDataFromSerial = $serial->readPort();

    for ($i = 0; $i < strlen($incomingDataFromSerial); $i++) {

        $c = ord($incomingDataFromSerial{$i});

        $translated = SystaBridge::getFixed(dechex($c), 2, "0", STR_PAD_LEFT);
        $buffer .= $translated;

        dump($translated);

        if (strlen($buffer) === $config['bufferLimit']) {
            $log->append('Buffer: limit reached');
            $log->append(sprintf('Buffer: %s', $buffer));
            exit;
        }
    }

    dump(PHP_EOL);

    $matches = null;
    if (1 === preg_match('/(fc200c01[0-9a-f]{62})/', $buffer, $matches)) {
        if (validateChecksum($telegram = $matches[1])) {
            $monitor->process($telegram);
        }
        $buffer = str_replace($telegram, '', $buffer);
    }

    $matches = null;
    if (1 === preg_match('/(fc220c02[0-9a-f]{66})/', $buffer, $matches)) {
        if (validateChecksum($telegram = $matches[1])) {
            $monitor->process($telegram);
        }
        $buffer = str_replace($telegram, '', $buffer);
    }

    $matches = null;
    if (1 === preg_match('/(fd170c03[0-9a-f]{60})/', $buffer, $matches)) {
        if (validateChecksum($telegram = $matches[1])) {
            $monitor->process($telegram);
        }
        $buffer = str_replace($telegram, '', $buffer);
    }

    // remove keep alive response from buffer
    if (false !== strpos($buffer, SystaBridge::COMMAND_START_MONITORING_V1)) {
        $buffer = str_replace(SystaBridge::COMMAND_START_MONITORING_V1, '', $buffer);
    }

    if (false !== strpos($buffer, SystaBridge::COMMAND_START_MONITORING_V2)) {
        $buffer = str_replace(SystaBridge::COMMAND_START_MONITORING_V2, '', $buffer);
    }
}
