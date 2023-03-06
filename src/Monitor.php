<?php

class Monitor
{
    private $directory;
    private $data = [];

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    public function set(string $key, $value)
    {
        if (!is_scalar($value)) {
            return;
        }
        $this->data[$key] = $value;
    }

    private function getPathname(): string
    {
        return $this->directory . '/monitor.txt';
    }

    public function clear()
    {
        @unlink($this->getPathname());
        $this->data = [];
    }

    public function load(): array
    {
        $data = @json_decode(file_get_contents($this->getPathname()), true);

        if (!is_array($data)) {
            return [];
        }

        ksort($data);
        return $data;
    }

    public function save()
    {
        return file_put_contents($this->getPathname(), json_encode($this->data, JSON_PRETTY_PRINT));
    }

    public function getErrorCodes(): array
    {
        $errorCodes = [null, null];

        $data = $this->load();

        if (isset($data['errorCodeBoiler'])) {
            $errorCodes[0] = $data['errorCodeBoiler'];
        }

        if (isset($data['errorCodeSensor'])) {
            $errorCodes[1] = $data['errorCodeSensor'];
        }

        return $errorCodes;
    }

    public function process(string $message)
    {
        // Systa Comfort
        $isHeaderMonitoringData1Firmware1 = 0 === strpos($message, 'fc200c01'); // Header Firmware 1.32.1
        $isHeaderMonitoringData1Firmware2 = 0 === strpos($message, 'fc270c01'); // Header Firmware 2.14.1

        if ($isHeaderMonitoringData1Firmware1 || $isHeaderMonitoringData1Firmware2) {

            $message = str_replace('fc200c01', '', $message);
            $message = str_replace('fc270c01', '', $message);

            $this->data['timestamp'] = time();
            $this->data['temperatureOutside'] = Helper::unsignedWordToSignedInt(substr($message, 8, 4)) * 0.1;
            $this->data['temperatureHotWater'] = hexdec(substr($message, 12, 4)) * 0.1;
            $this->data['temperatureFlowBoiler'] = hexdec(substr($message, 16, 4)) * 0.1;
            $this->data['temperatureReturnBoiler'] = hexdec(substr($message, 20, 4)) * 0.1;
            $this->data['temperatureRoomCircuit1'] = hexdec(substr($message, 24, 4)) * 0.1;
            $this->data['temperatureRoomCircuit2'] = hexdec(substr($message, 28, 4)) * 0.1;
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

        // Systa Comfort
        $isHeaderMonitoringData2Firmware1 = 0 === strpos($message, 'fc220c02'); // Header Firmware 1.32.1
        $isHeaderMonitoringData2Firmware2 = 0 === strpos($message, 'fc230c02'); // Header Firmware 2.14.1

        if ($isHeaderMonitoringData2Firmware1 || $isHeaderMonitoringData2Firmware2) {
            $message = str_replace('fc220c02', '', $message);
            $message = str_replace('fc230c02', '', $message);

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
            $this->data['errorCodeBoiler'] = hexdec(substr($message, 44, 4));
            $this->data['errorCodeSensor'] = hexdec(substr($message, 48, 2));
            $this->data['operationModeCircuit1'] = hexdec(substr($message, 50, 2));
            $this->data['niveauCircuit1'] = hexdec(substr($message, 52, 2));
            $this->data['operationModeCircuit2'] = hexdec(substr($message, 54, 2));
            $this->data['niveauCircuit2'] = hexdec(substr($message, 56, 2));
            $this->data['powerSetPumpCircuit1'] = hexdec(substr($message, 58, 2));
            $this->data['powerSetPumpCircuit2'] = hexdec(substr($message, 60, 2));
            $this->data['powerSetPumpBoiler'] = hexdec(substr($message, 62, 2));
        }

        if (0 === strpos($message, 'fd170c03')) {
            $message = str_replace('fd170c03', '', $message);

            $phone = '';
            foreach (str_split(substr($message, 10, 30), 2) as $digit) {
                $phone .= chr(hexdec($digit));
            }

            $counterMonths = hexdec(substr($message, 6, 4));
            $residue = $counterMonths % 12;

            $month = 1 + $residue;
            $year = 2000 + (($counterMonths - $residue) / 12);

            $this->data['maintenanceDate'] = sprintf('%s/%u', str_pad($month, 2, '0', STR_PAD_LEFT), $year);
            $this->data['maintenanceContactPhone'] = trim($phone);
        }

        if (0 === strpos($message, 'fd05aa0c')) {
            $message = str_replace('fd05aa0c', '', $message);

            $versionMajor = hexdec(substr($message, 0, 2));
            $versionMinor = hexdec(substr($message, 2, 2));
            $versionPatch = hexdec(substr($message, 4, 2));

            $this->data['versionSystaComfort'] = sprintf('%u.%u.%u', $versionMajor, $versionMinor, $versionPatch);
        }

        if (0 === strpos($message, 'fd2f0c0301')) {
            $message = str_replace('fd2f0c0301', '', $message);

            $data = $this->getHeatingInformation($message);

            $this->data['basePointCircuit2'] = $data['basePoint'];
            $this->data['steepnessCircuit2'] = $data['steepness'];
            $this->data['spreadCircuit2'] = $data['spread'];
            $this->data['temperatureMaxFlowCircuit2'] = $data['temperatureMaxFlow'];
            $this->data['temperatureHeatLimitNormalOperationCircuit2'] = $data['temperatureHeatLimitNormalOperation'];
            $this->data['timeMinutesPreheatCircuit2'] = $data['timeMinutesPreheat'];
        }

        if (0 === strpos($message, 'fd2f0c0300')) {
            $message = str_replace('fd2f0c0300', '', $message);

            $data = $this->getHeatingInformation($message);

            $this->data['basePointCircuit1'] = $data['basePoint'];
            $this->data['steepnessCircuit1'] = $data['steepness'];
            $this->data['spreadCircuit1'] = $data['spread'];
            $this->data['temperatureMaxFlowCircuit1'] = $data['temperatureMaxFlow'];
            $this->data['temperatureHeatLimitNormalOperationCircuit1'] = $data['temperatureHeatLimitNormalOperation'];
            $this->data['timeMinutesPreheatCircuit1'] = $data['timeMinutesPreheat'];
        }

        if (0 === strpos($message, 'fd140c03')) {
            $message = str_replace('fd140c03', '', $message);

            $this->data['circulationPumpDelayTimeMinutes'] = hexdec(substr($message, 28, 2));
            $this->data['circulationButtonLockTimeMinutes'] = hexdec(substr($message, 30, 2));
            $this->data['circulationPumpDifferentialGap'] = hexdec(substr($message, 34, 2)) * 0.1;
        }

        $this->calculateAdditionalValues();
        $this->applyCorrections();
        $this->filterDeactivatedCircuitValues();
        $this->save();
    }

    private function calculateAdditionalValues()
    {
        if (isset($this->data['temperatureSetFlowCircuit1']) && isset($this->data['temperatureFlowCircuit1'])) {
            $this->data['temperatureDifferenceFlowSetCurrentCircuit1'] = abs($this->data['temperatureSetFlowCircuit1'] - $this->data['temperatureFlowCircuit1']);
        }

        if (isset($this->data['temperatureSetFlowCircuit2']) && isset($this->data['temperatureFlowCircuit2'])) {
            $this->data['temperatureDifferenceFlowSetCurrentCircuit2'] = abs($this->data['temperatureSetFlowCircuit2'] - $this->data['temperatureFlowCircuit2']);
        }
    }

    private function getHeatingInformation(string $message): array
    {
        return [
            'basePoint' => hexdec(substr($message, 32, 4)) * 0.1,
            'steepness' => hexdec(substr($message, 36, 4)) * 0.1,
            'spread' => hexdec(substr($message, 76, 4)) * 0.1,
            'temperatureMaxFlow' => hexdec(substr($message, 42, 4)) * 0.1,
            'temperatureHeatLimitNormalOperation' => hexdec(substr($message, 58, 4)) * 0.1,
            'timeMinutesPreheat' => hexdec(substr($message, 70, 2))
        ];
    }

    private function applyCorrections()
    {
        if ($this->data['errorCodeBoiler'] === 65535) {
            $this->data['errorCodeBoiler'] = 0;
        }

        if (((int)($this->data['temperatureBufferBottom'] * 10)) === 65247) {
            $this->data['temperatureBufferBottom'] = 0;
        }
    }

    public function isActivatedCircuit1(): bool
    {
        return isset($this->data['operationModeCircuit1']) && $this->data['operationModeCircuit1'] !== 7;
    }

    public function isActivatedCircuit2(): bool
    {
        return isset($this->data['operationModeCircuit2']) && $this->data['operationModeCircuit2'] !== 7;
    }

    public function getTemperatureBufferTop()
    {
        return $this->data['temperatureBufferTop'] ?? 0;
    }

    public function getTemperatureHotWater()
    {
        return $this->data['temperatureHotWater'] ?? 0;
    }

    public function getTemperatureFlowBoiler(): int
    {
        return $this->data['temperatureFlowBoiler'] ?? 0;
    }

    public function getCounterBoilerStart(): int
    {
        return $this->data['counterBoilerStart'] ?? 0;
    }

    private function filterDeactivatedCircuitValues()
    {
        foreach ($this->data as $key => $value) {
            if (!$this->isActivatedCircuit1() && false !== stripos($key, 'circuit1')) {
                unset($this->data[$key]);
            }

            if (!$this->isActivatedCircuit2() && false !== stripos($key, 'circuit2')) {
                unset($this->data[$key]);
            }
        }
    }
}
