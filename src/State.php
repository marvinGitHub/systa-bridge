<?php

require_once 'SerialDeviceConfiguration.php';
require_once 'Monitor.php';

class State
{
    const STATE_NOT_CONNECTED = 0;
    const STATE_OK = 1;
    const STATE_ERROR_BOILER = 2;
    const STATE_ERROR_SENSOR = 3;
    const STATE_UNKNOWN = 4;
    const STATE_PUMP_BOILER_ON = 5;
    const STATE_PUMP_BOILER_OFF = 6;
    const STATE_PUMP_BOILER_UNKNOWN = 7;
    const STATE_BURNER_ON = 8;
    const STATE_BURNER_OFF = 9;
    const STATE_BURNER_UNKNOWN = 10;
    const STATE_CIRCUIT_UNKNOWN = 11;
    const STATE_CIRCUIT_CONTINUOUS_HEATING = 12;
    const STATE_CIRCUIT_DISABLED = 13;
    const STATE_CIRCUIT_SYSTEM_OFF = 14;
    const STATE_CIRCUIT_CONTINUOUS_COMFORT = 15;
    const STATE_CIRCUIT_SUMMER = 16;
    const STATE_CIRCUIT_LOWERING = 17;
    const STATE_CIRCUIT_AUTO_1 = 18;
    const STATE_CIRCUIT_AUTO_2 = 19;
    const STATE_CIRCUIT_AUTO_3 = 20;

    const ERROR_SENSOR_TEMPERATURE_BUFFER_BOTTOM_IMPLAUSIBLE = 9000;
    const ERROR_SENSOR_TEMPERATURE_CIRCULATION_IMPLAUSIBLE = 9001;

    private $serialDeviceConfiguration;
    private $monitor;

    public function __construct(SerialDeviceConfiguration $serialDeviceConfiguration, Monitor $monitor)
    {
        $this->serialDeviceConfiguration = $serialDeviceConfiguration;
        $this->monitor = $monitor;
    }

    public function getStateSystem(): int
    {
        if (!$this->serialDeviceConfiguration->serialDeviceAttached()) {
            return State::STATE_NOT_CONNECTED;
        }

        $this->monitor->load();

        if (!empty($this->monitor->getErrorCodeBoiler())) {
            return State::STATE_ERROR_BOILER;
        }

        if (!empty($this->monitor->getErrorCodeSensor())) {
            return State::STATE_ERROR_SENSOR;
        }

        return State::STATE_OK;
    }

    public function getStatePumpBoiler(): int
    {
        $data = $this->monitor->load();

        if (!isset($data['statePumpBoiler'])) {
            return State::STATE_PUMP_BOILER_UNKNOWN;
        }

        if (1 === (int)$data['statePumpBoiler']) {
            return State::STATE_PUMP_BOILER_ON;
        }

        return State::STATE_PUMP_BOILER_OFF;
    }

    public function getStateBurner(): int
    {
        $data = $this->monitor->load();

        if (!isset($data['stateBurnerContact'])) {
            return State::STATE_BURNER_UNKNOWN;
        }

        if (1 === (int)$data['stateBurnerContact']) {
            return State::STATE_BURNER_ON;
        }

        return State::STATE_BURNER_OFF;
    }

    private function getStateCircuit(int $state): int
    {
        $states = [
            0 => State::STATE_CIRCUIT_AUTO_1,
            1 => State::STATE_CIRCUIT_AUTO_2,
            2 => State::STATE_CIRCUIT_AUTO_3,
            3 => State::STATE_CIRCUIT_CONTINUOUS_HEATING,
            4 => State::STATE_CIRCUIT_CONTINUOUS_COMFORT,
            5 => State::STATE_CIRCUIT_LOWERING,
            6 => State::STATE_CIRCUIT_SUMMER,
            7 => State::STATE_CIRCUIT_DISABLED,
            17 => State::STATE_CIRCUIT_SYSTEM_OFF
        ];

        return $states[$state] ?? State::STATE_CIRCUIT_UNKNOWN;
    }

    public function getStateCircuit1(): int
    {
        $data = $this->monitor->load();

        if (!isset($data['operationModeCircuit1'])) {
            return STate::STATE_CIRCUIT_UNKNOWN;
        }

        return $this->getStateCircuit($data['operationModeCircuit1']);
    }

    public function getStateCircuit2(): int
    {
        $data = $this->monitor->load();

        if (!isset($data['operationModeCircuit2'])) {
            return STate::STATE_CIRCUIT_UNKNOWN;
        }

        return $this->getStateCircuit($data['operationModeCircuit2']);
    }
}
