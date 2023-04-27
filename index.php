<?php

require 'authorization.php';

require 'bootstrap.php';

if (function_exists('opcache_reset')) {
    opcache_reset();
}

$configuration = new Configuration(__DIR__ . '/config/config.json', __DIR__ . '/config/default.json');
if (false === $config = $configuration->load()) {
    echo 'Please check system configuration.';
    exit;
}

$log = new Log($config['logfile']);

$monitor = new Monitor($config['monitor']);

$serialDeviceConfiguration = new SerialDeviceConfiguration($config['serialDevice']);
$state = new State($serialDeviceConfiguration, $monitor);

$statesSystem = [
    State::STATE_OK => 'OK',
    State::STATE_ERROR_BOILER => 'Error Boiler',
    State::STATE_ERROR_SENSOR => 'Error Sensor',
    State::STATE_NOT_CONNECTED => 'Not connected',
    State::STATE_UNKNOWN => 'Unknown'
];

$statesPumpBoiler = [
    State::STATE_PUMP_BOILER_ON => 'Running',
    State::STATE_PUMP_BOILER_OFF => 'Idle',
    State::STATE_PUMP_BOILER_UNKNOWN => 'Unknown'
];

$statesBurner = [
    State::STATE_BURNER_ON => 'Running',
    State::STATE_BURNER_OFF => 'Idle',
    State::STATE_BURNER_UNKNOWN => 'Unknown'
];

$statesCircuit = [
    State::STATE_CIRCUIT_UNKNOWN => 'Unknown',
    State::STATE_CIRCUIT_CONTINUOUS_HEATING => 'Continuous Heating',
    State::STATE_CIRCUIT_DISABLED => 'Disabled',
    State::STATE_CIRCUIT_SYSTEM_OFF => 'Off',
    State::STATE_CIRCUIT_SUMMER => 'Summer',
    State::STATE_CIRCUIT_AUTO_1 => 'Auto (1)',
    State::STATE_CIRCUIT_AUTO_2 => 'Auto (2)',
    State::STATE_CIRCUIT_AUTO_3 => 'Auto (3)',
    State::STATE_CIRCUIT_CONTINUOUS_COMFORT => 'Continuous Comfort',
    State::STATE_CIRCUIT_LOWERING => 'Lowering'
];

$translationStateBoiler = $statesPumpBoiler[$state->getStatePumpBoiler()];
$translationStateBurner = $statesBurner[$state->getStateBurner()];
$translationStateSystem = $statesSystem[$state->getStateSystem()];
$translationStateCircuit1 = $statesCircuit[$state->getStateCircuit1()];
$translationStateCircuit2 = $statesCircuit[$state->getStateCircuit2()];

$errorCodesBoiler = [
    0 => '---'
];

$errorCodesSensor = [
    0 => '---',
    State::ERROR_SENSOR_TEMPERATURE_CIRCULATION_IMPLAUSIBLE => sprintf('%s - Temperature Circulation Implausible', State::ERROR_SENSOR_TEMPERATURE_CIRCULATION_IMPLAUSIBLE),
    State::ERROR_SENSOR_TEMPERATURE_BUFFER_BOTTOM_IMPLAUSIBLE => sprintf('%s - Temperature Buffer Bottom Implausible', State::ERROR_SENSOR_TEMPERATURE_BUFFER_BOTTOM_IMPLAUSIBLE),
];

$translationErrorCodeBoiler = $errorCodesBoiler[$monitor->getErrorCodeBoiler()] ?? $monitor->getErrorCodeBoiler();

$blockDocumentedCommands = '';
foreach (SystaBridge::getDocumentedCommands() as $hex => $description) {
    $blockDocumentedCommands .= <<<HTML
<li><b>$hex</b> - $description</li>
HTML;
}

$blockErrorCodeSensor = '';
foreach ($monitor->getErrorCodeSensor() as $errorCodeSensor) {
    $translationErrorCodeSensor = $errorCodesSensor[$errorCodeSensor] ?? $errorCodeSensor;

    $blockErrorCodeSensor .= <<<HTML
<b>Error Code Sensor: </b>$translationErrorCodeSensor<br />
HTML;
}

echo <<<HTML
<html>
<body style="font-family: sans-serif">
<pre>
  ___                  _ _                   ___         _        ___     _    _          
 | _ \__ _ _ _ __ _ __| (_)__ _ _ __  __ _  / __|_  _ __| |_ __ _| _ )_ _(_)__| |__ _ ___ 
 |  _/ _` | '_/ _` / _` | / _` | '  \/ _` | \__ \ || (_-<  _/ _` | _ \ '_| / _` / _` / -_)
 |_| \__,_|_| \__,_\__,_|_\__, |_|_|_\__,_| |___/\_, /__/\__\__,_|___/_| |_\__,_\__, \___|
                          |___/                  |__/                           |___/     
</pre>
<form action="server.php" method="post">
    <button name="command" value="showMonitor">Show Monitor</button>
    <button name="command" value="clearMonitor">Clear Monitor</button>
    <button name="command" value="getSystemLog">Show System Log</button>    
    <button name="command" value="clearSystemLog">Clear System Log</button>
    <button name="command" value="showSystemConfigurationEditor">Show System Configuration</button>
    <button name="command" value="resetSystemConfiguration">Reset System Configuration</button>
    <button name="command" value="startMonitoring">Start Monitoring</button>
    <button name="command" value="stopMonitoring">Stop Monitoring</button>
    <button name="command" value="showDump">Show Dump</button>    
    <button name="command" value="enableDump">Enable Dump</button>
    <button name="command" value="disableDump">Disable Dump</button> 
    <button name="command" value="clearDump">Clear Dump</button>  
    <button name="command" value="configureSerialDevice">Configure Serial Device</button>
    <button name="command" value="showSerialDeviceConfiguration">Show Serial Device Configuration</button>
    <button name="command" value="findSerialDevices">Find Serial Devices</button>
    <button name="command" value="enablePluginMQTTPublisher">Enable MQTT</button>
    <button name="command" value="disablePluginMQTTPublisher">Disable MQTT</button> 
    <button name="command" value="reboot">Reboot</button>
</form>

<b>State System: </b>$translationStateSystem<br />
<b>State Pump Boiler: </b>$translationStateBoiler<br />
<b>State Burner: </b>$translationStateBurner<br />
<b>Error Code Boiler: </b>$translationErrorCodeBoiler<br />
{$blockErrorCodeSensor}
<b>Circuit 1: </b>$translationStateCircuit1<br />
<b>Circuit 2: </b>$translationStateCircuit2<br />
<br />
<b>Supported Systa Commands</b><br />
<ul>
{$blockDocumentedCommands}
</ul>   
 
<form action="server.php" method="post">
    <button name="command" value="showCommandQueue">Show Command Queue</button>
    <button name="command" value="clearCommandQueue">Clear Command Queue</button>
    <button name="command" value="sendSystaCommand">Send Systa Command</button>
    <input type="text" name="systaCommand" placeholder="command (HEX)"/>
    <input type="checkbox" name="allowUndocumentedCommands" />
    <span>Allow undocumented commands</span>
</form>
</body>
</html>

HTML;



