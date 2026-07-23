<?php

namespace Tests\Unit;

use App\Services\CashierDashboardService;
use App\Services\CashierTransactionRecorder;
use PHPUnit\Framework\TestCase;

class CashierDashboardServiceTest extends TestCase
{
    public function test_summary_uses_signed_transaction_amounts_and_separates_adjustments(): void
    {
        $service = new CashierDashboardService(
            $this->createMock(CashierTransactionRecorder::class)
        );

        $transactions = collect([
            $this->transaction('Sold', 900, 100, 50),
            $this->transaction('Sold', 500),
            $this->transaction('Refunded', -300),
            $this->transaction('Voided', -200),
            $this->transaction('Rebooked', 0),
            $this->transaction('Cancelled', 0),
        ]);

        $this->assertSame([
            'tickets' => 2,
            'gross_sales' => 1350.0,
            'discounts' => 100.0,
            'surcharges' => 50.0,
            'refunds' => 300.0,
            'voids' => 200.0,
            'rebooked' => 1,
            'cancelled' => 1,
            'net_collection' => 900.0,
            'transaction_count' => 6,
        ], $service->summarize($transactions));
    }

    private function transaction(
        string $status,
        float $amount,
        float $discount = 0,
        float $surcharge = 0
    ): object {
        return (object) [
            'status' => $status,
            'amount' => $amount,
            'discount_amount' => $discount,
            'surcharge_amount' => $surcharge,
        ];
    }
}
