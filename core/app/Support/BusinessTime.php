<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class BusinessTime
{
    public const TIMEZONE = 'Asia/Manila';

    public static function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    public static function today(): Carbon
    {
        return self::now()->startOfDay();
    }

    public static function parse(CarbonInterface|string $value): Carbon
    {
        return Carbon::parse($value, self::TIMEZONE)->setTimezone(self::TIMEZONE);
    }
}
