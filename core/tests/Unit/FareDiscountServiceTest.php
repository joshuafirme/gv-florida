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

    public function test_all_discount_percentages_round_the_final_payable_fare_to_the_nearest_five(): void
    {
        $service = new FareDiscountService();

        $this->assertSame(380.0, $service->discountedFare(755, 50));
        $this->assertSame(640.0, $service->discountedFare(755, 15));
        $this->assertSame(680.0, $service->discountedFare(755, 10));
    }

    public function test_percentage_refund_amount_is_rounded_to_the_nearest_five(): void
    {
        $service = new FareDiscountService();

        $this->assertSame(380.0, $service->percentageAmount(755, 50));
        $this->assertSame(375.0, $service->percentageAmount(750, 50));
    }
}
