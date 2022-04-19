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
}