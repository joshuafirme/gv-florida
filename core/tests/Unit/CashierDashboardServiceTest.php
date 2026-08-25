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
            $this->transaction('Discount Override', -100, 100),
            $this->transaction('Validated', 0),
        ]);

        $this->assertSame([
            'tickets' => 2,
            'gross_sales' => 1350.0,
            'discounts' => 200.0,
            'surcharges' => 50.0,
            'refunds' => 300.0,
            'voids' => 200.0,
            'rebooked' => 1,
            'cancelled' => 1,
            'net_collection' => 800.0,
            'transaction_count' => 8,
        ], $service->summarize($transactions));
    }

    public function test_rebooking_does_not_remove_the_original_sale_from_status_metrics(): void
    {
        $service = new CashierDashboardService(
            $this->createMock(CashierTransactionRecorder::class)
        );

        $transactions = collect([
            $this->transaction('Sold', 900, slipId: 1),
            $this->transaction('Rebooked', 0, slipId: 1),
            $this->transaction('Sold', 500, slipId: 2),
            $this->transaction('Refunded', -500, slipId: 2),
        ]);
        $latestTransactions = collect([
            $transactions[1],
            $transactions[3],
        ]);

        $metrics = $service->statusMetrics($transactions, $latestTransactions);

        $this->assertSame(1, $metrics['sold']['count']);
        $this->assertSame(900.0, $metrics['sold']['amount']);
        $this->assertSame(1, $metrics['rebooked']['count']);
        $this->assertSame(0.0, $metrics['rebooked']['amount']);
        $this->assertSame(1, $metrics['refunded']['count']);
        $this->assertSame(500.0, $metrics['refunded']['amount']);
    }

    private function transaction(
        string $status,
        float $amount,
        float $discount = 0,
        float $surcharge = 0,
        ?int $slipId = null
    ): object {
        return (object) [
            'status' => $status,
            'amount' => $amount,
            'discount_amount' => $discount,
            'surcharge_amount' => $surcharge,
            'slip_series_number_id' => $slipId,
        ];
    }
}
