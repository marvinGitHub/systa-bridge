<?php

class Helper
{
    public static function getState(int $states, int $bit)
    {
        if (($bit < 0) || ($bit > 12)) {
            return false;
        }

        return ($states & (1 << $bit)) === 0 ? 0 : 1;
    }

    public static function getFixed(string $string, $length = 2, $padchar = "0", $type = STR_PAD_LEFT)
    {
        if (strlen($string) > $length) {
            return substr($string, 0, $length);
        } else {
            return str_pad($string, $length, $padchar, $type);
        }
    }
}