<?php

ini_set('serialize_precision', 10);

try {
    require 'src/autoload.php';

    $supportedCommands = [
        'sendSystaCommand',
        'showCommandQueue',
        'stopMonitoring',
        'startMonitoring',
        'getSystemLog',
        'clearSystemLog',
        'getSystemConfiguration',
        'setSystemConfiguration',
        'resetSystemConfiguration',
        'getServerStatus',
        'clearCommandQueue',
        'showMonitor',
        'showDump',
        'clearDump',
        'reboot',
        'configureSerialDevice',
        'showSerialDeviceConfiguration',
        'findSerialDevices',
        'enableDump',
        'disableDump',
        'enableAutomaticDesinfection',
        'disableAutomaticDesinfection'
    ];
    sort($supportedCommands);

    function stdout(string $content, bool $formatted = true)
    {
        if ($formatted) {
            $content = sprintf('<pre>%s</pre>', $content);
        }
        echo $content;
    }

    if (!isset($_POST['command'])) {
        stdout(sprintf('Please choose between supported commands: [%s]', implode(', ', $supportedCommands)));
        exit;
    }

    if (!in_array($command = $_POST['command'], $supportedCommands)) {
        stdout(sprintf('Unsupported command: %s', $command));
        exit;
    }

    $configuration = new Configuration(__DIR__ . '/config/config.json', __DIR__ . '/config/default.json');
    if (empty($config = $configuration->load())) {
        stdout('Please check system configuration.');
        exit;
    }

    $log = new Log($config['logfile']);
    $queue = new Queue($config['queue']);
    $systaBridge = new SystaBridge();
    $monitor = new Monitor($config['monitor']);
    $dump = new Dump($config['dumpfile']);
    $serialDeviceConfiguration = new SerialDeviceConfiguration($config['serialDevice']);

    switch ($command) {
        case 'getSystemConfiguration':
            echo json_encode($config, JSON_PRETTY_PRINT);
            exit;
        case 'clearSystemLog':
            $log->clear();
            stdout($message = 'System Log has been cleared.');
            $log->append($message);
            exit;
        case 'getSystemLog':
            stdout($log->load());
            exit;
        case 'resetSystemConfiguration':
            $configuration->restore();
            stdout($message = 'System Configuration has been restored to default values.');
            $log->append($message);
            exit;
        case 'startMonitoring':
            $config['monitoring'] = true;
            $configuration->save($config);
            stdout($message = 'Monitoring has been enabled.');
            $log->append($message);
            exit;
        case 'stopMonitoring':
            $config['monitoring'] = false;
            $configuration->save($config);
            $monitor->clear();
            stdout($message = 'Monitoring has been disabled.');
            $log->append($message);
            exit;
        case 'sendSystaCommand':
            if (empty($_POST['systaCommand'])) {
                stdout($message = 'No systa command provided.');
                $log->append($message);
                exit;
            }
            $valid = $invalid = [];
            foreach (explode(',', $_POST['systaCommand']) as $systaCommand) {
                if (SystaBridge::isSupportedCommand($systaCommand)) {
                    $valid[] = $systaCommand;
                } else {
                    $invalid[] = $systaCommand;
                }
            }
            if (count($invalid)) {
                stdout($message = sprintf('Unsupported systa commands provided: %s', implode(', ', $invalid)));
                $log->append($message);
                exit;
            }
            foreach ($valid as $systaCommand) {
                $queue->queue($systaCommand);
                stdout($message = sprintf('Command %s has been added to queue.', $systaCommand));
                $log->append($message);
            }
            exit;
        case 'showCommandQueue':
            $log->append('Show command queue');
            if (empty($commandQueue = $queue->load())) {
                stdout($message = 'Command queue is empty.');
                $log->append($message);
                exit;
            }
            stdout($commandQueue);
            exit;
        case 'clearCommandQueue':
            $queue->clear();
            stdout($message = 'Command queue has been cleared.');
            $log->append($message);
            exit;
        case 'showMonitor':
            $log->append('Load monitor.');
            if (empty($page = $monitor->load())) {
                stdout($message = 'No monitoring data available.');
                $log->append($message);
                exit;
            }
            header('Content-Type: application/json');
            stdout(json_encode($page, JSON_PRETTY_PRINT), false);
            exit;
        case 'showDump':
            $log->append('Load dump');
            if (empty($data = $dump->load())) {
                stdout($message = 'No dump available.');
                $log->append($message);
                exit;
            }
            stdout($data);
            exit;
        case 'clearDump':
            $dump->clear();
            stdout($message = 'Dump has been cleared.');
            $log->append($message);
            exit;
        case 'reboot':
            stdout($message = 'Rebooting...');
            $log->append($message);
            exec('reboot');
            exit;
        case 'configureSerialDevice':
            if ($serialDeviceConfiguration->alreadyConfigured()) {
                stdout($message = 'Serial device is already configured.');
                $log->append($message);
                exit;
            }
            if (false === $serialDeviceConfiguration->configure()) {
                stdout($message = 'Serial device configuration failed.');
            } else {
                stdout($message = 'Serial device configuration succesful.');
            }
            $log->append($message);
            exit;
        case 'showSerialDeviceConfiguration':
            $log->append('Load serial device configuration');
            if (empty($currentSerialDeviceConfiguration = $serialDeviceConfiguration->load())) {
                stdout($message = 'Failed getting current serial device configuration.');
                exit;
            }
            stdout($currentSerialDeviceConfiguration);
            exit;
        case 'findSerialDevices':
            $log->append('Searching for serial devices...');
            $serialDevices = $serialDeviceConfiguration->findSerialDevices();
            if (empty($serialDevices)) {
                stdout($message = 'No serial device found.');
                $log->append($message);
                exit;
            }
            stdout($message = 'Found serial devices:');
            $log->append($message);
            stdout($message = implode(', ', $serialDevices));
            $log->append($message);
            exit;
        case 'enableDump':
            $config['dump'] = true;
            $configuration->save($config);
            stdout($message = 'Dump has been enabled.');
            $log->append($message);
            exit;
        case 'disableDump':
            $config['dump'] = false;
            $configuration->save($config);
            stdout($message = 'Dump has been disabled.');
            $log->append($message);
            exit;
        case 'disableAutomaticDesinfection':
            $config['automaticDesinfection'] = false;
            $configuration->save($config);
            stdout($message = 'Automatic desinfection has been disabled.');
            $log->append($message);
            exit;
        case 'enableAutomaticDesinfection':
            $config['automaticDesinfection'] = true;
            $configuration->save($config);
            stdout($message = 'Automatic desinfection has been enabled.');
            $log->append($message);
            exit;
    }
} catch (Exception $e) {
    stdout('Unknown server error');
    exit;
}
