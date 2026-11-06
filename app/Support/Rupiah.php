<?php

namespace App\Support;

class Rupiah
{
    /**
     * Format an amount as Rupiah, for example "Rp150.000".
     */
    public static function format(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
