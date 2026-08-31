<?php

namespace Tests\Unit;

use App\Services\FareDiscountService;
use PHPUnit\Framework\TestCase;

class FareDiscountServiceTest extends TestCase
{
    public function test_discounted_fare_is_rounded_to_the_nearest_five_pesos(): void
    {
        $service = new FareDiscountService();

        $this->assertSame(605.0, $service->discountedFare(755, 20));
        $this->assertSame(505.0, $service->discountedFare(630, 20));
        $this->assertSame(1680.0, $service->discountedFare(2100, 20));
        $this->assertSame(2240.0, $service->discountedFare(2800, 20));
    }

    public function test_rounding_can_move_the_discounted_fare_down_or_up(): void
    {
        $service = new FareDiscountService();

        $this->assertSame(600.0, $service->discountedFare(752, 20));
        $this->assertSame(605.0, $service->discountedFare(755, 20));
        $this->assertSame(150.0, $service->discountAmount(755, 20));
    }
}
