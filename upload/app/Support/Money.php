<?php

namespace App\Support;

class Money
{
    /** Định dạng 1.000.000 (thay cho Add_Dot trong library.prg). */
    public static function dot(int|float $amount, string $separator = '.'): string
    {
        return number_format(round($amount), 0, ',', $separator);
    }
}
