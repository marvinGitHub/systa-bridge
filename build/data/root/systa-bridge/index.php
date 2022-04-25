<?php


$users = [];
foreach (explode(PHP_EOL, file_get_contents(__DIR__ . '/config/users.txt')) as $line) {
    $credentials = explode(':', $line);
    $users[$credentials[0]] = $credentials[1];
}

$user = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

$validated = array_key_exists($user, $users) && ($password === $users[$user]);

if (!$validated) {
  header('WWW-Authenticate: Basic realm="Restricted Area"');
  header('HTTP/1.0 401 Unauthorized');
  die ("Not authorized");
}

require 'src/autoload.php';

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

$statesBoiler = [
    State::STATE_BOILER_ON => 'Running',
    State::STATE_BOILER_OFF => 'Idle',
    State::STATE_BOILER_UNKNOWN => 'Unknown'
];

$translationStateBoiler = $statesBoiler[$state->getStateBoiler()];
$translationStateSystem = $statesSystem[$state->getStateSystem()];

$blockDocumentedCommands = '';
foreach (SystaBridge::getDocumentedCommands() as $hex => $description) {
    $blockDocumentedCommands .= <<<HTML
<li><b>$hex</b> - $description</li>
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
    <button name="command" value="getSystemLog">Show System Log</button>    
    <button name="command" value="clearSystemLog">Clear System Log</button>
    <button name="command" value="getSystemConfiguration">Show System Configuration</button>
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
    <button name="command" value="reboot">Reboot</button>
</form>

<b>State System: </b>$translationStateSystem<br />
<b>State Boiler: </b>$translationStateBoiler<br /><br />
<b>Supported Systa Commands</b><br />
<ul>
{$blockDocumentedCommands}
</ul>   
 
<form action="server.php" method="post">
    <button name="command" value="showCommandQueue">Show Command Queue</button>
    <button name="command" value="clearCommandQueue">Clear Command Queue</button>
    <button name="command" value="sendSystaCommand">Send Systa Command</button>
    <input type="text" name="systaCommand" placeholder="command (HEX)"/>
</form>
</body>
</html>

HTML;



