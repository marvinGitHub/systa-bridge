<?php

class SystaBridge
{
    const COMMAND_START_MONITORING_LEGACY = '0a01141f';
    const COMMAND_START_MONITORING = '0a0114e1';
    const COMMAND_STOP_MONITORING = '0a0115e0';
    const COMMAND_CIRCUIT1_CONTINUOUS_HEATING = '0a0a1d0c1153455400020103c0';
    const COMMAND_CIRCUIT2_CONTINUOUS_HEATING = '0a0a1d0c115345540188010339';
    const COMMAND_CIRCUIT1_SUMMER = '0a0a1d0c1153455400020106bd';
    const COMMAND_CIRCUIT2_SUMMER = '0a0a1d0c115345540188010636';
    const COMMAND_CIRCUIT1_AUTO1 = '0a0a1d0c1153455400020100c3';
    const COMMAND_CIRCUIT1_AUTO2 = '0a0a1d0c1153455400020101c2';
    const COMMAND_CIRCUIT1_AUTO3 = '0a0a1d0c1153455400020102c1';
    const COMMAND_CIRCUIT1_LOWERING = '0a0a1d0c1153455400020105be';

    const COMMAND_CIRCUIT1_COMFORT = '0a0a1d0c1153455400020104bf';
    const COMMAND_CIRCUIT2_COMFORT = '0a0a1d0c115345540188010438';

    public static function getDocumentedCommands(): array
    {
        return [
            SystaBridge::COMMAND_CIRCUIT1_AUTO1 => 'Circuit1: Set operation mode to auto (1)',
            SystaBridge::COMMAND_CIRCUIT1_AUTO2 => 'Circuit1: Set operation mode to auto (2)',
            SystaBridge::COMMAND_CIRCUIT1_AUTO3 => 'Circuit1: Set operation mode to auto (3)',
            SystaBridge::COMMAND_CIRCUIT1_CONTINUOUS_HEATING => 'Circuit1: Set operation mode to continuous heating',
            SystaBridge::COMMAND_CIRCUIT1_COMFORT => 'Circuit1: Set operation mode to continuous comfort',
            SystaBridge::COMMAND_CIRCUIT1_LOWERING => 'Circuit1: Set operation mode to lowering',
            SystaBridge::COMMAND_CIRCUIT1_SUMMER => 'Circuit1: Set operation mode to summer',
            '0a0a1d0c1153455400020107bc' => 'Circuit1: Set operation mode to disabled',
            '0a0a1d0c115345540188010042' => 'Circuit2: Set operation mode to auto (1)',
            '0a0a1d0c115345540188010141' => 'Circuit2: Set operation mode to auto (2)',
            '0a0a1d0c115345540188010240' => 'Circuit2: Set operation mode to auto (3)',
            SystaBridge::COMMAND_CIRCUIT2_CONTINUOUS_HEATING => 'Circuit2: Set operation mode to continuous heating',
            SystaBridge::COMMAND_CIRCUIT2_COMFORT => 'Circuit2: Set operation mode to continuous comfort',
            '0a0a1d0c115345540188010537' => 'Circuit2: Set operation mode to lowering',
            SystaBridge::COMMAND_CIRCUIT2_SUMMER => 'Circuit2: Set operation mode to summer',
            '0a0a1d0c115345540188010735' => 'Circuit2: Set operation mode to disabled',
            SystaBridge::COMMAND_START_MONITORING_LEGACY => 'System: Start Monitoring (legacy)',
            SystaBridge::COMMAND_START_MONITORING => 'System: Start Monitoring',
            SystaBridge::COMMAND_STOP_MONITORING => 'System: Stop Monitoring',
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

        return Helper::getFixed(dechex($checksum > 0 ? (256 - $checksum) : 0));
    }
}
