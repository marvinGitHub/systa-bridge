<?php

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
        'showMonitorPage1',
        'showMonitorPage2',
        'showDump',
        'clearDump',
        'reboot',
        'configureSerialDevice',
        'showSerialDeviceConfiguration',
        'findSerialDevices',
        'enableDump',
        'disableDump'
    ];
    sort($supportedCommands);

    function stdout($content) {
        echo <<<HTML
<pre>$content</pre>
HTML;
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
            if (!array_key_exists($systaCommand = $_POST['systaCommand'], $systaBridge->getDocumentedCommands())) {
                stdout($message = 'Unsupported systa command provided.');
                $log->append($message);
                exit;
            }
            $queue->queue($systaCommand);
            stdout($message = sprintf('Command %s has been added to queue.', $systaCommand));
            $log->append($message);
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
        case 'showMonitorPage1':
            $log->append('Load monitor page 1.');
            if (empty($page = $monitor->loadPage1())) {
                stdout($message = 'No monitoring data available on page 1.');
                $log->append($message);
                exit;
            }
            stdout($page);
            exit;
        case 'showMonitorPage2':
            $log->append('Load monitor page 2.');
            if (empty($page = $monitor->loadPage2())) {                                                            
                stdout($message = 'No monitoring data available on page 2.');                                      
                $log->append($message);                                                                            
                exit;                                                                                              
            }
            stdout($page);
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
    }
} catch (Exception $e) {
    stdout('Unknown server error');
    exit;
}
