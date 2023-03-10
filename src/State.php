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

        $errorCodes = $this->monitor->getErrorCodes();

        if (!empty($errorCodes[0])) {
            return State::STATE_ERROR_BOILER;
        }

        if (!empty($errorCodes[1])) {
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
}
