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

function getFixed($string, $length, $padchar = " ", $type = STR_PAD_RIGHT)
{
    if (strlen($string) > $length) {
        return substr($string, 0, $length);
    } else {
        return str_pad($string, $length, $padchar, $type);
    }
}

function sendSystaCommand($command)
{
    global $serial;
    global $log;
    $serial->sendMessage(hex2bin($command));
    $log->append(sprintf('Command %s sent to device', $command));
    sleep(5);
}


$index = 0;
$typ = 0;
$header = "";
$message = [];
$keepAliveCounter = null;
$buffer = '';

while (true) {
    $config = $configuration->load();

    sleep(1);

    if ($command = $queue->next()) {
        sendSystaCommand($command);
    }

    $incomingDataFromSerial = $serial->readPort();

    if ($config['monitoring']) {
        $currentMinute = date('i');
        if ($currentMinute != $keepAliveCounter) {
            // request service interface to collect monitoring data every 5-8 seconds for 3 minutes, this command needs to be repeated every minute
            sendSystaCommand(SystaBridge::COMMAND_START_MONITORING_V2);
            $keepAliveCounter = $currentMinute;
            $log->append('Keep alive packet sent.');
        }
    }

    for ($i = 0; $i < strlen($incomingDataFromSerial); $i++) {

        $c = ord($incomingDataFromSerial{$i});

        $translated = getFixed(dechex($c), 2, "0", STR_PAD_LEFT);
        $buffer .= $translated;

        if ($config['dump']) {
            $dump->write($translated);
        }

        if (strlen($buffer) === $config['bufferLimit']) {
            $log->append('Buffer: limit reached');
            $log->append(sprintf('Buffer: %s', $buffer));
            exit;       
        }
    }

    $matches = null;
    if (1 === preg_match('/(fc200c01[0-9a-f]{62})/', $buffer, $matches)) {
        $monitor->save($telegram = $matches[1]);
        $buffer = str_replace($telegram, '', $buffer);
    }

    $matches = null;
    if (1 === preg_match('/(fc220c02[0-9a-f]{66})/', $buffer, $matches)) {
        $monitor->save($telegram = $matches[1]);
        $buffer =  str_replace($telegram, '', $buffer);
    }

    // remove keep alive response from buffer
    if (0 === strpos($buffer, SystaBridge::COMMAND_START_MONITORING_V1) || 0 === strpos($buffer, SystaBridge::COMMAND_START_MONITORING_V2)) {
        $buffer = '';
 
    }
}
