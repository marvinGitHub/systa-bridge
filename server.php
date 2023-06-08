<?php

require 'bootstrap.php';

try {
    $supportedCommands = [
        'sendSystaCommand',
        'showCommandQueue',
        'stopMonitoring',
        'startMonitoring',
        'getSystemLog',
        'clearSystemLog',
        'showSystemConfigurationEditor',
        'resetSystemConfiguration',
        'getServerStatus',
        'clearCommandQueue',
        'showMonitor',
        'clearMonitor',
        'showDump',
        'clearDump',
        'reboot',
        'configureSerialDevice',
        'showSerialDeviceConfiguration',
        'findSerialDevices',
        'enableDump',
        'disableDump',
        'enablePluginMQTTPublisher',
        'disablePluginMQTTPublisher',
        'saveSystemConfiguration'
    ];
    sort($supportedCommands);

    function stdout(string $content, bool $formatted = true)
    {
        if ($formatted) {
            $content = sprintf('<pre>%s</pre>', $content);
        }
        echo $content;
    }

    function printJSON($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT) ?? '';
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

    $log = new Log($config['logfile'], $config['verbose']);
    $queue = new Queue($config['queue']);
    $systaBridge = new SystaBridge();
    $monitor = new Monitor($configuration);
    $dump = new Dump($config['dumpfile']);
    $serialDeviceConfiguration = new SerialDeviceConfiguration($config['serialDevice']);
    $storage = new KeyValueStorage($config['storagePath']);

    function queue(string $command)
    {
        global $queue, $log;
        $queue->queue($command);
        stdout($message = sprintf('Command %s has been added to queue.', $command));
        $log->print('info', $message);
    }

    switch ($command) {
        case 'saveSystemConfiguration':
            if (!isset($_POST['config'])) {
                stdout('No configuration provided');
                exit;
            }

            try {
                if (!$configuration->save($_POST['config'])) {
                    throw new Exception();
                }
            } catch (Exception $e) {
                stdout('Posted configuration contains errors. Please check JSON syntax.');
                exit;
            }

            stdout('System configuration has been saved successfully.');
            exit;
        case 'showSystemConfigurationEditor':
            $config = json_encode($config, JSON_PRETTY_PRINT);

            echo <<<HTML
<form action="server.php" method="post">
    <textarea name="config" style="width: 100%; height: 90%;">$config</textarea><br />
    <button name="command" value="saveSystemConfiguration">Save</button>
</form>
HTML;
            exit;
        case 'clearSystemLog':
            $log->clear();
            stdout($message = 'System Log has been cleared.');
            $log->print('info', $message);
            exit;
        case 'getSystemLog':
            stdout($log->load());
            exit;
        case 'resetSystemConfiguration':
            $configuration->restore();
            stdout($message = 'System Configuration has been restored to default values.');
            $log->print('info', $message);
            exit;
        case 'startMonitoring':
            $config['pluginMonitoringKeepAlive'] = true;
            $configuration->save($config);
            stdout($message = 'Monitoring has been enabled.');
            $log->print('info', $message);
            exit;
        case 'stopMonitoring':
            $config['pluginMonitoringKeepAlive'] = false;
            $configuration->save($config);
            $queue->queue(SystaBridge::COMMAND_STOP_MONITORING);
            stdout($message = 'Monitoring has been disabled.');
            $log->print('info', $message);
            exit;
        case 'sendSystaCommand':
            if (empty($_POST['systaCommand'])) {
                stdout($message = 'No systa command provided.');
                $log->print('error', $message);
                exit;
            }
            $allowUndocumentedCommands = isset($_POST['allowUndocumentedCommands']);
            $valid = $invalid = [];
            foreach (explode(',', $_POST['systaCommand']) as $systaCommand) {
                if (SystaBridge::isSupportedCommand($systaCommand) || $allowUndocumentedCommands) {
                    $valid[] = $systaCommand;
                } else {
                    $invalid[] = $systaCommand;
                }
            }
            if (count($invalid)) {
                stdout($message = sprintf('Unsupported systa commands provided: %s', implode(', ', $invalid)));
                $log->print('error', $message);
                exit;
            }
            foreach ($valid as $systaCommand) {
                queue($systaCommand);
            }
            exit;
        case 'showCommandQueue':
            $log->print('info', 'Show command queue');
            if (empty($commandQueue = $queue->load())) {
                stdout($message = 'Command queue is empty.');
                $log->print('info', $message);
                exit;
            }
            stdout($commandQueue);
            exit;
        case 'clearCommandQueue':
            $queue->clear();
            stdout($message = 'Command queue has been cleared.');
            $log->print('info', $message);
            exit;
        case 'showMonitor':
            $log->print('info', 'Load monitor.');
            if (empty($page = $monitor->load())) {
                stdout($message = 'No monitoring data available.');
                $log->print('info', $message);
                exit;
            }
            printJSON($page);
            exit;
        case 'clearMonitor':
            $monitor->clear();
            stdout($message = 'Monitor has been cleared.');
            $log->print('info', $message);
            exit;
            exit;
        case 'showDump':
            $log->print('info', 'Load dump');
            if (empty($data = $dump->load())) {
                stdout($message = 'No dump available.');
                $log->print('info', $message);
                exit;
            }
            stdout($data);
            exit;
        case 'clearDump':
            $dump->clear();
            stdout($message = 'Dump has been cleared.');
            $log->print('info', $message);
            exit;
        case 'reboot':
            stdout($message = 'Rebooting...');
            $log->print('info', $message);
            exec('reboot');
            exit;
        case 'configureSerialDevice':
            if ($serialDeviceConfiguration->alreadyConfigured()) {
                stdout($message = 'Serial device is already configured.');
                $log->print('info', $message);
                exit;
            }
            if (false === $serialDeviceConfiguration->configure()) {
                stdout($message = 'Serial device configuration failed.');
                $log->print('error', $message);
            } else {
                stdout($message = 'Serial device configuration successful.');
                $log->print('info', $message);
            }

            exit;
        case 'showSerialDeviceConfiguration':
            $log->print('info', 'Load serial device configuration');
            if (empty($currentSerialDeviceConfiguration = $serialDeviceConfiguration->load())) {
                stdout($message = 'Failed getting current serial device configuration.');
                exit;
            }
            stdout($currentSerialDeviceConfiguration);
            exit;
        case 'findSerialDevices':
            $log->print('info', 'Searching for serial devices...');
            $serialDevices = $serialDeviceConfiguration->findSerialDevices();
            if (empty($serialDevices)) {
                stdout($message = 'No serial device found.');
                $log->print('error', $message);
                exit;
            }
            stdout($message = 'Found serial devices:');
            $log->print('info', $message);
            stdout($message = implode(', ', $serialDevices));
            $log->print('log', $message);
            exit;
        case 'enableDump':
            $config['dump'] = true;
            $configuration->save($config);
            stdout($message = 'Dump has been enabled.');
            $log->print('info', $message);
            exit;
        case 'disableDump':
            $config['dump'] = false;
            $configuration->save($config);
            stdout($message = 'Dump has been disabled.');
            $log->print('info', $message);
            exit;
        case 'disablePluginMQTTPublisher':
            $config['pluginMQTTPublisher'] = false;
            $configuration->save($config);
            stdout($message = 'Plugin MQTT Publisher has been disabled.');
            $log->print('info', $message);
            exit;
        case 'enablePluginMQTTPublisher':
            $config['pluginMQTTPublisher'] = true;
            $configuration->save($config);
            stdout($message = 'Plugin MQTT Publisher has been enabled.');
            $log->print('info', $message);
            exit;
    }
} catch (Exception $e) {
    stdout('Unknown server error');
    exit;
}
