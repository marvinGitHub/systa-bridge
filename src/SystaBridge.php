<?php

class SystaBridge
{
    const COMMAND_START_MONITORING_V1 = '0a01141f';
    const COMMAND_START_MONITORING_V2 = '0a0114e1';
    const COMMAND_CIRCUIT1_CONTINOUS_HEATING = '0a0a1d0c1153455400020103c0';
    const COMMAND_CIRCUIT2_CONTINOUS_HEATING = '0a0a1d0c115345540188010339';

    const COMMAND_CIRCUIT1_COMFORT = '0a0a1d0c1153455400020104bf';
    const COMMAND_CIRCUIT2_COMFORT = '0a0a1d0c115345540188010438';

    public static function getDocumentedCommands(): array
    {
        return [
            '0a0a1d0c1153455400020100c3' => 'Circuit1: Set operation mode to auto (1)',
            SystaBridge::COMMAND_CIRCUIT1_CONTINOUS_HEATING => 'Circuit1: Set operation mode to continuous heating',
            SystaBridge::COMMAND_CIRCUIT1_COMFORT => 'Circuit1: Set operation mode to continuous comfort',
            '0a0a1d0c1153455400020105be' => 'Circuit1: Set operation mode to lowering',
            '0a0a1d0c1153455400020106bd' => 'Circuit1: Set operation mode to summer',
            '0a0a1d0c1153455400020107bc' => 'Circuit1: Set operation mode to inactive',
            '0a0a1d0c115345540188010042' => 'Circuit2: Set operation mode to auto (1)',
            SystaBridge::COMMAND_CIRCUIT2_CONTINOUS_HEATING => 'Circuit2: Set operation mode to continuous heating',
            SystaBridge::COMMAND_CIRCUIT2_COMFORT => 'Circuit2: Set operation mode to continuous comfort',
            '0a0a1d0c115345540188010537' => 'Circuit2: Set operation mode to lowering',
            '0a0a1d0c115345540188010636' => 'Circuit2: Set operation mode to summer',
            '0a0a1d0c115345540188010735' => 'Circuit2: Set operation mode to inactive',
            SystaBridge::COMMAND_START_MONITORING_V1 => 'System: Keep Alive Packet v1 to request monitoring data',
            SystaBridge::COMMAND_START_MONITORING_V2 => 'System: Keep Alive Packet v2 to request monitoring data',
            '0a0116df' => 'System: Retrieve systa comfort version',
            '0a0117de' => '',
            '0a061c0c0300022a99' => 'Circuit1: Retrieve heating information',
            '0a061c0c0301882a12' => 'Circuit2: Retrieve heating information',
            '0a061c0c03030e09ab' => '',
            '0a061c0c0303f70fbc' => 'Circulation: Retrieve information',
            '0a061c0c0304e612c9' => 'System: Retrieve maintenance information (phone contact, date next maintenance)',
            '0a061c0c030317703b' => '',
            '0a061c0c03038770cb' => '',
            '0a061c0c030406704b' => '',
            '0a061c0c03047670db' => '',
            '0a061c0c03002c7029' => '',
            '0a061c0c03009c70b9' => '',
            '0a061c0c03010c7048' => '',
            '0a061c0c0301b270a2' => '',
            '0a061c0c0302227031' => '',
            '0a061c0c03029270c1' => '',
            '0a031c0c14b7' => '',
            '0a061c0c03050305b8' => '',
            '0a061c0c03050803b5' => ''
        ];
    }

    public static function isSupportedCommand(string $command): bool
    {
        return array_key_exists($command, static::getDocumentedCommands());

    }

    public static function checksum(string $hex)
    {
        $value = 0;

        for ($i = 0; $i < strlen($hex) / 2; $i++) {
            $value += hexdec(substr($hex, $i * 2, 2));
        }

        $checksum = $value % 256;

        if ($checksum > 0) {
            return Helper::getFixed(dechex(256 - $checksum));
        }

        return 0;
    }
}
