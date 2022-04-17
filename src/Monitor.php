<?php

class Monitor
{
    private $directory;
    private $states;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    private function getState(int $bit)
    {
        if (($bit < 0) || ($bit > 12)) {
            return false;
        }

        return ($this->states & (1 << $bit)) === 0 ? 0 : 1;
    }


    private function getPathnamePage1()
    {
        return $this->directory . '/page1.txt';
    }

    public function getPathnamePage2()
    {
        return $this->directory . '/page2.txt';
    }

    public function loadPage1()
    {
        if (!file_exists($pathname = $this->getPathnamePage1())) {
            return false;
        }
        return file_get_contents($pathname);
    }

    public function loadPage2()
    {
        if (!file_exists($pathname = $this->getPathnamePage2())) {
            return false;
        }
        return file_get_contents($pathname);
    }

    public function getErrorCodes()
    {
        if (false === $data = $this->loadPage2()) {
            return false;
        }

        $data = json_decode($data, true);
        if (false === $data) {
            return false;
        }

        if (65535 === $data['errorCodeBoiler']) {
            $data['errorCodeBoiler'] = 0;
        }

        return [$data['errorCodeBoiler'], $data['errorCodeSensor']];
    }

    public function clear()
    {
        @unlink($this->getPathnamePage1());
        @unlink($this->getPathnamePage2());
    }

    public function save(string $message)
    {
        // Systa Comfort, Monitordatensatz 1
        if (0 === strpos($message, 'fc200c01')) {
            
            $message = str_replace('fc200c01', '', $message);

            $ta = hexdec(substr($message, 8, 4)) * 0.1;
            $tww = hexdec(substr($message, 12, 4)) * 0.1;
            $kv = hexdec(substr($message, 16, 4)) * 0.1;
            $kr = hexdec(substr($message, 20, 4)) * 0.1;
            $rthk1 = hexdec(substr($message, 24, 4)) * 0.1;
            $rthk2 = hexdec(substr($message, 28, 4)) * 0.1;
            $vlhk1 = hexdec(substr($message, 32, 4)) * 0.1;
            $vlhk2 = hexdec(substr($message, 36, 4)) * 0.1;
            $rlhk1 = hexdec(substr($message, 40, 4)) * 0.1;
            $rlhk2 = hexdec(substr($message, 44, 4)) * 0.1;
            $po = hexdec(substr($message, 48, 4)) * 0.1;
            $pu = hexdec(substr($message, 52, 4)) * 0.1;
            $zk = hexdec(substr($message, 56, 4)) * 0.1;

            $page = [
                'timestamp' => time(),
                'temperatureOutside' => $ta,
                'temperatureHotWater' => $tww,
                'temperatureFlowBoiler' => $kv,
                'temperatureReturnBoiler' => $kr,
                'temperatureActualRoomCircuit1' => $rthk1,
                'temperatureActualRoomCircuit2' => $rthk2,
                'temperatureFlowCircuit1' => $vlhk1,
                'temperatureFlowCircuit2' => $vlhk2,
                'temperatureReturnCircuit1' => $rlhk1,
                'temperatureReturnCircuit2' => $rlhk2,
                'temperatureDifferenceFlowReturnCircuit1' => abs($vlhk1 - $rlhk1),
                'temperatureDifferenceFlowReturnCircuit2' => abs($vlhk2 - $rlhk2),
                'temperatureBufferTop' => $po,
                'temperatureBufferBottom' => $pu,
                'temperatureCirculation' => $zk
            ];

            return file_put_contents($this->getPathnamePage1(), json_encode($page, JSON_PRETTY_PRINT));
        }
        
        // Systa Comfort, Monitordatensatz 2
        if (0 === strpos($message, 'fc220c02')) {
            $message = str_replace('fc220c02', '', $message);

            $this->states = hexdec(substr($message, 24, 4));

            $page = [
                'timestamp' => time(),
                'temperatureSetRoomCircuit1' => hexdec(substr($message, 0, 4)) * 0.1,
                'temperatureSetRoomCircuit2' => hexdec(substr($message, 4, 4)) * 0.1,
                'temperatureSetFlowCircuit1' => hexdec(substr($message, 8, 4)) * 0.1,
                'temperatureSetFlowCircuit2' => hexdec(substr($message, 12, 4)) * 0.1,
                'temperatureSetHotWater' => hexdec(substr($message, 16, 4)) * 0.1,
                'temperatureSetBuffer' => hexdec(substr($message, 20, 4)) * 0.1,
                'states' => $this->states,
                'statePumpCircuit1' => $this->getState(0),
                'statePumpCircuit2' => $this->getState(1),
                'statePumpBoiler' => $this->getState(2),
                'stateMixerOpenCircuit1' => $this->getState(3),
                'stateMixerClosedCircuit1' => $this->getState(4),
                'stateMixerOpenCircuit2' => $this->getState(5),
                'stateMixerClosedCircuit2' => $this->getState(6),
                'stateSwitchingValve' => $this->getState(7),
                'statePumpCirculation' => $this->getState(8),
                'stateBurnerContact' => $this->getState(9),
                'stateButtonCirculation' => $this->getState(10),
                'stateModuleLON' => $this->getState(11),
                'stateModuleOpenTherm' => $this->getState(12),
                'operationTimeHoursBoiler' => hexdec(substr($message, 28, 8)),
                'counterBoilerStart' => hexdec(substr($message, 36, 8)),
                'averageOperationTimeMinutes' => 0,
                'errorCodeBoiler' => hexdec(substr($message, 44, 4)),
                'errorCodeSensor' => hexdec(substr($message, 48, 2)),
                'operationModeCircuit1' => hexdec(substr($message, 50, 2)),
                'niveauCircuit1' => hexdec(substr($message, 52, 2)),
                'operationModeCircuit2' => hexdec(substr($message, 54, 2)),
                'niveauCircuit2' => hexdec(substr($message, 56, 2)),
                'powerSetPumpCircuit1' => hexdec(substr($message, 58, 2)),
                'powerSetPumpCircuit2' => hexdec(substr($message, 60, 2)),
                'powerSetPumpBoiler' => hexdec(substr($message, 62, 2))
            ];

            $page['averageOperationTimeMinutes'] = round(($page['operationTimeHoursBoiler'] / $page['counterBoilerStart']) * 60, 0);

            return file_put_contents($this->getPathnamePage2(), json_encode($page, JSON_PRETTY_PRINT));
        }
    }
}
