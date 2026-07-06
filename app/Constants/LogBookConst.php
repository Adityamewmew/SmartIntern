<?php

namespace App\Constants;

use Carbon\Carbon;

class LogBookConst
{
    /**
     * Opsi bulan (1-12) untuk filter index.
     *
     * @return array<int, string>
     */
    public static function getMonthOptions(): array
    {
        $months = [];

        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = Carbon::create(null, $m)->translatedFormat('F');
        }

        return $months;
    }
}
