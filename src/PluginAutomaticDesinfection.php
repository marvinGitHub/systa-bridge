<?php

class PluginAutomaticDesinfection
{
    const STORAGE_KEY_OPERATION_MODE_CIRCUIT1 = 'PluginAutomaticDesinfection.operationModeCircuit1';
    const STORAGE_KEY_OPERATION_MODE_CIRCUIT2 = 'PluginAutomaticDesinfection.operationModeCircuit2';
    const STORAGE_KEY_TIMESTAMP_NEXT_DESINFECTION = 'PluginAutomaticDesinfection.timestampNextDesinfection';
    const INTERVAL_DEFAULT = 604800;

    private $storage;
    private $monitor;
    private $interval;
    private $queue;
    private $log;
    private $temperatureDifferenceCelsius = 1;

    private $mappingOperationModesCircuit1 = [
        3 => SystaBridge::COMMAND_CIRCUIT1_CONTINOUS_HEATING
    ];

    private $mappingOperationModesCircuit2 = [
        3 => SystaBridge::COMMAND_CIRCUIT2_CONTINOUS_HEATING
    ];

    public function __construct(KeyValueStorage $storage, ?Monitor $monitor = null, ?Queue $queue = null, ?Log $log = null, int $interval = PluginAutomaticDesinfection::INTERVAL_DEFAULT)
    {
        $this->storage = $storage;
        $this->monitor = $monitor;
        $this->interval = $interval;
        $this->queue = $queue;
        $this->log = $log;
        $this->init();
    }

    private function init()
    {
        $timestampNextDesinfection = $this->getTimestampNextDesinfection();

        if (!$timestampNextDesinfection) {
            $this->setTimestampNextDesinfection(time());
        }
    }

    public function reset()
    {
        $this->storage->clear(self::STORAGE_KEY_TIMESTAMP_NEXT_DESINFECTION);
        $this->storage->clear(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT1);
        $this->storage->clear(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT2);
    }

    public function getTimestampNextDesinfection()
    {
        return $this->storage->get(self::STORAGE_KEY_TIMESTAMP_NEXT_DESINFECTION);
    }

    private function setTimestampNextDesinfection(int $timestamp)
    {
        return $this->storage->set(self::STORAGE_KEY_TIMESTAMP_NEXT_DESINFECTION, $timestamp);
    }

    private function storeOperationModeCircuit1($operationMode): void
    {
        $this->storage->set(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT1, $operationMode);
    }

    private function storeOperationModeCircuit2($operationMode): void
    {
        $this->storage->set(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT2, $operationMode);
    }

    private function getPreviousOperationModeCircuit1()
    {
        return $this->storage->get(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT1);
    }

    private function getPreviousOperationModeCircuit2()
    {
        return $this->storage->get(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT2);
    }

    public function run()
    {
        $operationModeCircuit1 = $this->monitor->getOperationModeCircuit1();
        $operationModeCircuit2 = $this->monitor->getOperationModeCircuit2();

        if ($operationModeCircuit1 === null || $operationModeCircuit2 === null) {
            return;
        }

        $timestampNextDesinfection = $this->getTimestampNextDesinfection();

        if (null === $timestampNextDesinfection) {
            return;
        }

        $this->monitor->set('timestampNextDesinfection', $timestampNextDesinfection);

        // enable desinfection (comfort mode of circuit will be used for hot water settings)
        if (time() >= $timestampNextDesinfection) {
            $this->log->append('Automatic Desinfection: Started');

            $this->log->append(sprintf('Automatic Desinfection: Operation Mode Circuit1: %u', $operationModeCircuit1));
            $this->log->append(sprintf('Automatic Desinfection: Operation Mode Circuit2: %u', $operationModeCircuit2));

            $this->storeOperationModeCircuit1($operationModeCircuit1);
            $this->storeOperationModeCircuit2($operationModeCircuit2);

            $this->queue->queue(SystaBridge::COMMAND_CIRCUIT1_COMFORT);
            $this->queue->queue(SystaBridge::COMMAND_CIRCUIT2_COMFORT);

            $this->setTimestampNextDesinfection(time() + $this->interval);
        }

        // fallback to previous operation mode if hot water temperature set is reached
        if ($this->monitor->getTemperatureHotWater() >= $this->monitor->getTemperatureSetHotWater() - $this->temperatureDifferenceCelsius) {

            $previousOperationModeCircuit1 = $this->getPreviousOperationModeCircuit1();
            $previousOperationModeCircuit2 = $this->getPreviousOperationModeCircuit2();

            if (null !== $previousOperationModeCircuit1) {
                if (array_key_exists($previousOperationModeCircuit1, $this->mappingOperationModesCircuit1)) {
                    $this->queue->queue($this->mappingOperationModesCircuit1[$previousOperationModeCircuit1]);
                    $this->storage->clear(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT1);
                } else {
                    $this->log->append(sprintf('Error: Circuit1: unable to restore previous operation mode (%u).', $previousOperationModeCircuit1));
                }
            }

            if (null !== $previousOperationModeCircuit2) {
                if (array_key_exists($previousOperationModeCircuit2, $this->mappingOperationModesCircuit2)) {
                    $this->queue->queue($this->mappingOperationModesCircuit2[$previousOperationModeCircuit2]);
                    $this->storage->clear(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT2);
                } else {
                    $this->log->append(sprintf('Error: Circuit2: unable to restore previous operation mode (%u).', $previousOperationModeCircuit2));
                }
            }
        }
    }
}