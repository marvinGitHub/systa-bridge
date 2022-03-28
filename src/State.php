<?php

require_once 'SerialDeviceConfiguration.php';
require_once 'Monitor.php';

class State {
    const STATE_NOT_CONNECTED = 0;
    const STATE_OK = 1;
    const STATE_ERROR_BOILER = 2;
    const STATE_ERROR_SENSOR = 3;
    const STATE_UNKNOWN = 4;

    private $serialDeviceConfiguration;
    private $monitor;

    public function __construct(SerialDeviceConfiguration $serialDeviceConfiguration, Monitor $monitor) {
        $this->serialDeviceConfiguration = $serialDeviceConfiguration;
        $this->monitor = $monitor;
    }

    public function getState() {
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
}
