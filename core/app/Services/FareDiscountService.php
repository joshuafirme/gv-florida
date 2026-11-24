<?php

namespace App\Services;

class FareDiscountService
{
    private const ROUNDING_INCREMENT = 5;

    public function discountedFare(float $baseFare, float $percentage): float
    {
        $baseFare = max($baseFare, 0);
        $percentage = min(max($percentage, 0), 100);
        $discountedFare = $baseFare * (1 - ($percentage / 100));

        return $this->roundToNearestIncrement($discountedFare);
    }

    public function discountAmount(float $baseFare, float $percentage): float
    {
        return max($baseFare - $this->discountedFare($baseFare, $percentage), 0);
    }

    public function percentageAmount(float $amount, float $percentage): float
    {
        $amount = max($amount, 0);
        $percentage = min(max($percentage, 0), 100);

        return $this->roundToNearestIncrement($amount * ($percentage / 100));
    }

    private function roundToNearestIncrement(float $amount): float
    {
        return round(
            $amount / self::ROUNDING_INCREMENT,
            0,
            PHP_ROUND_HALF_UP
        ) * self::ROUNDING_INCREMENT;
    }
}
