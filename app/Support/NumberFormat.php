<?php

namespace App\Support;

/**
 * Indonesian number formatting for admin statistics, matching Rupiah::format().
 */
class NumberFormat
{
    /**
     * For example "2.480".
     */
    public static function integer(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    /**
     * Share of a total with at most one decimal, for example "76,6%" or "75%".
     */
    public static function percentage(int $part, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }

        $percentage = round($part / $total * 100, 1);
        $decimals = fmod($percentage, 1.0) === 0.0 ? 0 : 1;

        return number_format($percentage, $decimals, ',', '.').'%';
    }
}
