<?php

class PluginAutomaticDesinfection extends PluginAbstract
{
    use IntervalAwareTrait;

    const STORAGE_KEY_OPERATION_MODE_CIRCUIT1 = 'PluginAutomaticDesinfection.operationModeCircuit1';
    const STORAGE_KEY_OPERATION_MODE_CIRCUIT2 = 'PluginAutomaticDesinfection.operationModeCircuit2';
    const STORAGE_KEY_TIMESTAMP_NEXT_DESINFECTION = 'PluginAutomaticDesinfection.timestampNextDesinfection';

    const INTERVAL_DEFAULT = 604800;

    private $temperatureDifferenceCelsius = 1;

    private $mappingOperationModesCircuit1 = [
        3 => SystaBridge::COMMAND_CIRCUIT1_CONTINOUS_HEATING
    ];

    private $mappingOperationModesCircuit2 = [
        3 => SystaBridge::COMMAND_CIRCUIT2_CONTINOUS_HEATING
    ];

    protected function init()
    {
        $this->setInterval(PluginAutomaticDesinfection::INTERVAL_DEFAULT);

        $timestampNextDesinfection = $this->getTimestampNextDesinfection();

        if (!$timestampNextDesinfection) {
            $this->setTimestampNextDesinfection(time());
        }
    }

    public function reset()
    {
        $this->getStorage()->clear(self::STORAGE_KEY_TIMESTAMP_NEXT_DESINFECTION);
        $this->getStorage()->clear(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT1);
        $this->getStorage()->clear(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT2);
    }

    public function getTimestampNextDesinfection()
    {
        return $this->getStorage()->get(self::STORAGE_KEY_TIMESTAMP_NEXT_DESINFECTION);
    }

    private function setTimestampNextDesinfection(int $timestamp)
    {
        return $this->getStorage()->set(self::STORAGE_KEY_TIMESTAMP_NEXT_DESINFECTION, $timestamp);
    }

    private function storeOperationModeCircuit1($operationMode): void
    {
        $this->getStorage()->set(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT1, $operationMode);
    }

    private function storeOperationModeCircuit2($operationMode): void
    {
        $this->getStorage()->set(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT2, $operationMode);
    }

    private function getPreviousOperationModeCircuit1()
    {
        return $this->getStorage()->get(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT1);
    }

    private function getPreviousOperationModeCircuit2()
    {
        return $this->getStorage()->get(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT2);
    }

    public function run()
    {
        $operationModeCircuit1 = $this->getMonitor()->getOperationModeCircuit1();
        $operationModeCircuit2 = $this->getMonitor()->getOperationModeCircuit2();

        if ($operationModeCircuit1 === null || $operationModeCircuit2 === null) {
            return;
        }

        $timestampNextDesinfection = $this->getTimestampNextDesinfection();

        if (null === $timestampNextDesinfection) {
            return;
        }

        $this->getMonitor()->set('timestampNextDesinfection', $timestampNextDesinfection);

        // enable desinfection (comfort mode of circuit will be used for hot water settings)
        if (time() >= $timestampNextDesinfection) {
            $this->getLog()->append('Automatic Desinfection: Started');

            $this->getLog()->append(sprintf('Automatic Desinfection: Operation Mode Circuit1: %u', $operationModeCircuit1));
            $this->getLog()->append(sprintf('Automatic Desinfection: Operation Mode Circuit2: %u', $operationModeCircuit2));

            $this->storeOperationModeCircuit1($operationModeCircuit1);
            $this->storeOperationModeCircuit2($operationModeCircuit2);

            $this->getQueue()->queue(SystaBridge::COMMAND_CIRCUIT1_COMFORT);
            $this->getQueue()->queue(SystaBridge::COMMAND_CIRCUIT2_COMFORT);

            $this->setTimestampNextDesinfection(time() + $this->interval);
        }

        // fallback to previous operation mode if hot water temperature set is reached
        if ($this->getMonitor()->getTemperatureHotWater() >= $this->getMonitor()->getTemperatureSetHotWater() - $this->temperatureDifferenceCelsius) {

            $previousOperationModeCircuit1 = $this->getPreviousOperationModeCircuit1();
            $previousOperationModeCircuit2 = $this->getPreviousOperationModeCircuit2();

            if (null !== $previousOperationModeCircuit1) {
                if (array_key_exists($previousOperationModeCircuit1, $this->mappingOperationModesCircuit1)) {
                    $this->getQueue()->queue($this->mappingOperationModesCircuit1[$previousOperationModeCircuit1]);
                    $this->getStorage()->clear(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT1);
                } else {
                    $this->getLog()->append(sprintf('Error: Circuit1: unable to restore previous operation mode (%u).', $previousOperationModeCircuit1));
                }
            }

            if (null !== $previousOperationModeCircuit2) {
                if (array_key_exists($previousOperationModeCircuit2, $this->mappingOperationModesCircuit2)) {
                    $this->getQueue()->queue($this->mappingOperationModesCircuit2[$previousOperationModeCircuit2]);
                    $this->getStorage()->clear(self::STORAGE_KEY_OPERATION_MODE_CIRCUIT2);
                } else {
                    $this->getLog()->append(sprintf('Error: Circuit2: unable to restore previous operation mode (%u).', $previousOperationModeCircuit2));
                }
            }
        }
    }
}