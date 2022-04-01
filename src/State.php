<?php

require_once 'SerialDeviceConfiguration.php';
require_once 'Monitor.php';

class State {
    const STATE_NOT_CONNECTED = 0;
    const STATE_OK = 1;
    const STATE_ERROR_BOILER = 2;
    const STATE_ERROR_SENSOR = 3;
    const STATE_UNKNOWN = 4;
    const STATE_BOILER_ON = 5;
    const STATE_BOILER_OFF = 6;
    const STATE_BOILER_UNKNOWN = 7;

    private $serialDeviceConfiguration;
    private $monitor;

    public function __construct(SerialDeviceConfiguration $serialDeviceConfiguration, Monitor $monitor) {
        $this->serialDeviceConfiguration = $serialDeviceConfiguration;
        $this->monitor = $monitor;
    }

    public function getStateSystem() {
        if (!$this->serialDeviceConfiguration->serialDeviceAttached()) {
            return State::STATE_NOT_CONNECTED;
        }

        $errorCodes = $this->monitor->getErrorCodes();
        
        if (false === $errorCodes) {
            return State::STATE_UNKNOWN;
        }
        
        if (!empty($errorCodes[0])) {
            return State::STATE_ERROR_BOILER;
        }

        if (!empty($errorCodes[1])) {
            return State::STATE_ERROR_SENSOR;
        }

        return State::STATE_OK;
    }

    public function getStateBoiler() {
        $page2 = json_decode($this->monitor->loadPage2(), true);

        if (!isset($page2['powerActual'])) {
            return State::STATE_BOILER_UNKNOWN;
        }

        if (0 < $page2['powerActual']) {
            return State::STATE_BOILER_ON;
        }

        return State::STATE_BOILER_OFF;
    }
}
