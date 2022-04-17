<?php

class SystaBridge
{
    const COMMAND_START_MONITORING_V1 = '0a01141f';
    const COMMAND_START_MONITORING_V2 = '0a0114e1';

    public function getDocumentedCommands()
    {
        return [
            '0a0a1d0c1153455400020100c3' => 'Circuit1: Set operation mode to auto (1)',
            '0a0a1d0c1153455400020103c0' => 'Circuit1: Set operation mode to continous heating',
            '0a0a1d0c1153455400020105be' => 'Circuit1: Set operation mode to lowering',
            '0a0a1d0c1153455400020106bd' => 'Circuit1: Set operation mode to summer',
            '0a0a1d0c1153455400020107bc' => 'Circuit1: Set operation mode to inactive',
            '0a0a1d0c115345540188010042' => 'Circuit2: Set operation mode to auto (1)',
            '0a0a1d0c115345540188010339' => 'Circuit2: Set operation mode to continous heating',
            '0a0a1d0c115345540188010537' => 'Circuit2: Set operation mode to lowering',
            '0a0a1d0c115345540188010636' => 'Circuit2: Set operation mode to summer',
            '0a0a1d0c115345540188010735' => 'Circuit2: Set operation mode to inactive',
            SystaBridge::COMMAND_START_MONITORING_V1 => 'System: Keep Alive Packet v1 to request monitoring data',
            SystaBridge::COMMAND_START_MONITORING_V2 => 'System: Keep Alive Packet v2 to request monitoring data'
        ];
    }

    public static function getFixed(string $string, $length = 2, $padchar = "0", $type = STR_PAD_LEFT)
    {
        if (strlen($string) > $length) {
            return substr($string, 0, $length);
        } else {
            return str_pad($string, $length, $padchar, $type);
        }
    }


    public static function checksum(string $hex)
    {
        $value = 0;

        for ($i = 0; $i < strlen($hex) / 2; $i++) {
            $value += hexdec(substr($hex, $i * 2, 2));
        }
      
        $checksum = $value % 256;

        if ($checksum > 0) {
            return static::getFixed(dechex(256 - $checksum));
        }

        return 0;  
    }
}
