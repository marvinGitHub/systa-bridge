<?php

class Monitor
{
    private $directory;
    private $data = [];

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    private function getPathname()
    {
        return $this->directory . '/monitor.txt';
    }

    public function clear()
    {
        @unlink($this->getPathname());
        $this->data = [];
    }

    public function load()
    {
        $data = @json_decode(file_get_contents($this->getPathname()), true);
        if ($data) {
            return $data;
        }
        return [];
    }

    public function save()
    {
        return file_put_contents($this->getPathname(), json_encode($this->data, JSON_PRETTY_PRINT));
    }


    public function getErrorCodes()
    {
        $errorCodes = [null, null];

        $data = $this->load();

        if (isset($data['errorCodeBoiler'])) {
            $errorCodes[0] = $data['errorCodeBoiler'] === 65535 ? 0 : $data['errorCodeBoiler'];
        }

        if (isset($data['errorCodeSensor'])) {
            $errorCodes[1] = $data['errorCodeSensor'];
        }

        return $errorCodes;
    }


    public function process(string $message)
    {
        // Systa Comfort, Monitordatensatz 1
        if (0 === strpos($message, 'fc200c01')) {

            $message = str_replace('fc200c01', '', $message);

            $this->data['timestamp'] = time();
            $this->data['temperatureOutside'] = hexdec(substr($message, 8, 4)) * 0.1;
            $this->data['temperatureHotWater'] = hexdec(substr($message, 12, 4)) * 0.1;
            $this->data['temperatureFlowBoiler'] = hexdec(substr($message, 16, 4)) * 0.1;
            $this->data['temperatureReturnBoiler'] = hexdec(substr($message, 20, 4)) * 0.1;
            $this->data['temperatureActualRoomCircuit1'] = hexdec(substr($message, 24, 4)) * 0.1;
            $this->data['temperatureActualRoomCircuit2'] = hexdec(substr($message, 28, 4)) * 0.1;
            $this->data['temperatureFlowCircuit1'] = hexdec(substr($message, 32, 4)) * 0.1;
            $this->data['temperatureFlowCircuit2'] = hexdec(substr($message, 36, 4)) * 0.1;
            $this->data['temperatureReturnCircuit1'] = hexdec(substr($message, 40, 4)) * 0.1;
            $this->data['temperatureReturnCircuit2'] = hexdec(substr($message, 44, 4)) * 0.1;
            $this->data['temperatureBufferTop'] = hexdec(substr($message, 48, 4)) * 0.1;
            $this->data['temperatureBufferBottom'] = hexdec(substr($message, 52, 4)) * 0.1;
            $this->data['temperatureCirculation'] = hexdec(substr($message, 56, 4)) * 0.1;
            $this->data['temperatureDifferenceFlowReturnCircuit1'] = abs($this->data['temperatureFlowCircuit1'] - $this->data['temperatureReturnCircuit1']);
            $this->data['temperatureDifferenceFlowReturnCircuit2'] = abs($this->data['temperatureFlowCircuit2'] - $this->data['temperatureReturnCircuit2']);
        }

        // Systa Comfort, Monitordatensatz 2
        if (0 === strpos($message, 'fc220c02')) {
            $message = str_replace('fc220c02', '', $message);

            $states = hexdec(substr($message, 24, 4));


            $this->data['timestamp'] = time();
            $this->data['temperatureSetRoomCircuit1'] = hexdec(substr($message, 0, 4)) * 0.1;
            $this->data['temperatureSetRoomCircuit2'] = hexdec(substr($message, 4, 4)) * 0.1;
            $this->data['temperatureSetFlowCircuit1'] = hexdec(substr($message, 8, 4)) * 0.1;
            $this->data['temperatureSetFlowCircuit2'] = hexdec(substr($message, 12, 4)) * 0.1;
            $this->data['temperatureSetHotWater'] = hexdec(substr($message, 16, 4)) * 0.1;
            $this->data['temperatureSetBuffer'] = hexdec(substr($message, 20, 4)) * 0.1;
            $this->data['states'] = $states;
            $this->data['statePumpCircuit1'] = Helper::getState($states, 0);
            $this->data['statePumpCircuit2'] = Helper::getState($states, 1);
            $this->data['statePumpBoiler'] = Helper::getState($states, 2);
            $this->data['stateMixerOpenCircuit1'] = Helper::getState($states, 3);
            $this->data['stateMixerClosedCircuit1'] = Helper::getState($states, 4);
            $this->data['stateMixerOpenCircuit2'] = Helper::getState($states, 5);
            $this->data['stateMixerClosedCircuit2'] = Helper::getState($states, 6);
            $this->data['stateSwitchingValve'] = Helper::getState($states, 7);
            $this->data['statePumpCirculation'] = Helper::getState($states, 8);
            $this->data['stateBurnerContact'] = Helper::getState($states, 9);
            $this->data['stateButtonCirculation'] = Helper::getState($states, 10);
            $this->data['stateModuleLON'] = Helper::getState($states, 11);
            $this->data['stateModuleOpenTherm'] = Helper::getState($states, 12);
            $this->data['operationTimeHoursBoiler'] = hexdec(substr($message, 28, 8));
            $this->data['counterBoilerStart'] = hexdec(substr($message, 36, 8));
            $this->data['averageOperationTimeMinutes'] = 0;
            $this->data['errorCodeBoiler'] = hexdec(substr($message, 44, 4));
            $this->data['errorCodeSensor'] = hexdec(substr($message, 48, 2));
            $this->data['operationModeCircuit1'] = hexdec(substr($message, 50, 2));
            $this->data['niveauCircuit1'] = hexdec(substr($message, 52, 2));
            $this->data['operationModeCircuit2'] = hexdec(substr($message, 54, 2));
            $this->data['niveauCircuit2'] = hexdec(substr($message, 56, 2));
            $this->data['powerSetPumpCircuit1'] = hexdec(substr($message, 58, 2));
            $this->data['powerSetPumpCircuit2'] = hexdec(substr($message, 60, 2));
            $this->data['powerSetPumpBoiler'] = hexdec(substr($message, 62, 2));
            $this->data['averageOperationTimeMinutes'] = round(($this->data['operationTimeHoursBoiler'] / $this->data['counterBoilerStart']) * 60, 0);
        }

        if (0 === strpos($message, 'fd170c03')) {
            $message = str_replace('fd170c03', '', $message);

            $message = substr($message, 10, 30);
            $phone = '';
            foreach (str_split($message, 2) as $digit) {
                $phone .= chr(hexdec($digit));
            }

            $this->data['maintenanceContactPhone'] = trim($phone);
        }

        $this->save();
    }
}
