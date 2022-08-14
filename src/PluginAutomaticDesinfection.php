<?php

class PluginAutomaticDesinfection
{
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

    public function __construct(KeyValueStorage $storage, Monitor $monitor, Queue $queue, Log $log, int $interval)
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

    private function getTimestampNextDesinfection()
    {
        return $this->storage->get(sprintf('%s.timestampNextDesinfection', self::class));
    }

    public function setTimestampNextDesinfection(int $timestamp)
    {
        return $this->storage->set(sprintf('%s.timestampNextDesinfection', self::class), $timestamp);
    }

    private function storeOperationModeCircuit1($operationMode): void
    {
        $this->storage->set(sprintf('%s.operationModeCircuit1', self::class), $operationMode);
    }

    private function storeOperationModeCircuit2($operationMode): void
    {
        $this->storage->set(sprintf('%s.operationModeCircuit2', self::class), $operationMode);
    }

    private function getPreviousOperationModeCircuit1()
    {
        return $this->storage->set(sprintf('%s.operationModeCircuit1', self::class));
    }

    private function getPreviousOperationModeCircuit2()
    {
        return $this->storage->set(sprintf('%s.operationModeCircuit2', self::class));
    }

    public function run()
    {
        // enable desinfection (comfort mode of circuit will be used for hot water settings)
        if (time() >= $this->getTimestampNextDesinfection()) {
            $this->storeOperationModeCircuit1($this->monitor->getOperationModeCircuit1());
            $this->storeOperationModeCircuit2($this->monitor->getOperationModeCircuit2());

            $this->queue->queue(SystaBridge::COMMAND_CIRCUIT1_COMFORT);
            $this->queue->queue(SystaBridge::COMMAND_CIRCUIT2_COMFORT);
            $this->setTimestampNextDesinfection(time() + $this->interval);
        }

        // fallback to previous operation mode
        if ($this->monitor->getTemperatureHotWater() >= $this->monitor->getTemperatureSetHotWater() - $this->temperatureDifferenceCelsius) {

            // restore circuit1
            if (array_key_exists($previousOperationModeCircuit1 = $this->getPreviousOperationModeCircuit1())) {
                $this->queue->queue($this->mappingOperationModesCircuit1[$previousOperationModeCircuit1]);
            } else {
                $this->log->append(sprintf('Error: Circuit1: unable to restore previous operation mode (%u).', $previousOperationModeCircuit1));
            }

            // restore circuit2
            if (array_key_exists($previousOperationModeCircuit2 = $this->getPreviousOperationModeCircuit2())) {
                $this->queue->queue($this->mappingOperationModesCircuit2[$previousOperationModeCircuit2]);
            } else {
                $this->log->append(sprintf('Error: Circuit2: unable to restore previous operation mode (%u).', $previousOperationModeCircuit2));
            }
        }
    }
}