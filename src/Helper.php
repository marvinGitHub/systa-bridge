<?php

class Helper
{
    public static function unsignedWordToSignedInt(string $hex)
    {
        if (false === preg_match('/[a-f0-9]{4}/', $hex)) {
            return;
        }

        return unpack('s', pack('v', hexdec($hex)))[1];
    }

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

    public static function getPeriodIntersectionSeconds(int $start1, int $end1, int $start2, int $end2): int
    {
        $range1 = [$start1, $end1];
        $range2 = [$start2, $end2];

        $start1 = DateTime::createFromFormat('U', min($range1));
        $end1 = DateTime::createFromFormat('U', max($range1));

        $start2 = DateTime::createFromFormat('U', min($range2));
        $end2 = DateTime::createFromFormat('U', max($range2));

        // check for special cases
        if ($start1 >= $start2 && $end1 <= $end2) {
            // range1 completely contained inside range2
            $overlap = $start1->diff($end1);
        } elseif ($start2 >= $start1 && $end2 <= $end1) {
            // range2 completely contained inside range1
            $overlap = $start2->diff($end2);
        } elseif ($end2 > $end1) {
            // range1 ends first
            $overlap = $start2->diff($end1);
        } else {
            // range2 ends first
            $overlap = $start1->diff($end2);
        }

        $reference = new DateTimeImmutable();
        $end = $reference->add($overlap);

        return 1 + ($end->getTimestamp() - $reference->getTimestamp());
    }
}