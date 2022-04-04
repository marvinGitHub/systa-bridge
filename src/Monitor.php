<?php

class Monitor
{
    private $directory;
    private $states;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    private function getState(int $bit) {
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

    public function clear() {
        @unlink($this->getPathnamePage1());
        @unlink($this->getPathnamePage2());
    }

    public function save(string $message)
    {
        if ($typ == 1) {
//            // Systa Solar
//            $date = date("Y-m-d H:i:00");
//            $tsa = ($message[0] * 256 + $message[1]) / 10;
//            $tse = ($message[2] * 256 + $message[3]) / 10;
//            $twu = ($message[4] * 256 + $message[5]) / 10;
//            $tw2 = ($message[6] * 256 + $message[7]) / 10;
//            $pso = $message[8];
//            $ulv = $message[9];
//            $status = $message[10];
//            $stoercode = $message[11];
//            $ctr = $message[13];
//            $tagesenergie = $message[22] * 256 + $message[23];
//            
//            file_put_contents($this->directory . '/1.txt');


        } else if (0 === strpos($message, 'fc200c01')) {
            // Systa Comfort, Monitordatensatz 1
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

        } else if (0 === strpos($message, 'fc220c02')) {

            // Systa Comfort, Monitordatensatz 2



            $message = str_replace('fc220c02', '', $message);

            #$states = strrev(base_convert(substr($message, 24, 4) ,16, 2));
            $this->states = hexdec(substr($message, 24, 4));

            $raumsollhk1 = hexdec(substr($message, 0, 4)) * 0.1;
            $raumsollhk2 = hexdec(substr($message, 4, 4)) * 0.1;
            $vlsollhk1 = hexdec(substr($message, 8, 4)) * 0.1;
            $vlsollhk2 = hexdec(substr($message, 12, 4)) * 0.1;
            $wwsoll = hexdec(substr($message, 16, 4)) * 0.1;
            $puffersoll = hexdec(substr($message, 20, 4)) * 0.1;
            $bskessel = hexdec(substr($message, 28, 8));
            $kesselstarts = hexdec(substr($message, 36, 8));
            $stoercodekessel = hexdec(substr($message, 44, 4));
            $stoercodefuehler = hexdec(substr($message, 48, 2));
            $betriebsarthk1 = hexdec(substr($message, 50, 2));
            $niveauhk1 = hexdec(substr($message, 52, 2));
            $betriebsarthk2 = hexdec(substr($message, 54, 2));
            $niveauhk2 = hexdec(substr($message, 56, 2));
            $leistungphk1 = hexdec(substr($message, 58, 2));
            $leistungphk2 = hexdec(substr($message, 60, 2));
            $leistungpk = hexdec(substr($message, 62, 2));

            $page = [
'timestamp' => time(),
'temperatureSetRoomCircuit1' => $raumsollhk1,
'temperatureSetRoomCircuit2' => $raumsollhk2,
'temperatureSetFlowCircuit1' => $vlsollhk1,
'temperatureSetFlowCircuit2' => $vlsollhk2,
'temperatureSetHotWater' => $wwsoll,
'temperatureSetBuffer' => $puffersoll,
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
'stateButtonBurnerDeactivate' => $this->getState(10),
'stateModuleLON' => $this->getState(11),
'stateModuleOpenTherm' => $this->getState(12),
'operationTimeHoursBoiler' => $bskessel,
'counterBoilerStart' => $kesselstarts,
'averageOperationTimeMinutes' => round(($bskessel / $kesselstarts) * 60, 0),
'errorCodeBoiler' => $stoercodekessel,
'errorCodeSensor' => $stoercodefuehler,
'operationModeCircuit1' => $betriebsarthk1,
'niveauCircuit1' => $niveauhk1,
'operationModeCircuit2' => $betriebsarthk2,
'niveauCircuit2' => $niveauhk2,
'powerSetPumpCircuit1' => $leistungphk1,
'powerSetPumpCircuit2' => $leistungphk2,
'powerSetPumpBoiler' => $leistungpk
];

            return file_put_contents($this->getPathnamePage2(), json_encode($page, JSON_PRETTY_PRINT));
        }
    }

}
