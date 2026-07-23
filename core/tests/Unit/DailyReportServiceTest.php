<?php

namespace Tests\Unit;

use App\Services\CashierDashboardService;
use App\Services\CashierTransactionRecorder;
use App\Services\DailyReportService;
use PHPUnit\Framework\TestCase;

class DailyReportServiceTest extends TestCase
{
    public function test_it_aggregates_daily_collections_by_cashier_channel_and_status(): void
    {
        $recorder = $this->createMock(CashierTransactionRecorder::class);
        $service = new DailyReportService(
            $recorder,
            new CashierDashboardService($recorder)
        );

        $transactions = collect([
            $this->transaction(1, 'Alice', 'Sold', 'Kiosk', 900, 100, 50),
            $this->transaction(1, 'Alice', 'Refunded', 'Kiosk', -300),
            $this->transaction(2, 'Ben', 'Sold', 'Counter', 500),
            $this->transaction(2, 'Ben', 'Voided', 'Counter', -200),
            $this->transaction(2, 'Ben', 'Rebooked', 'Counter', 0),
            $this->transaction(2, 'Ben', 'Cancelled', 'Counter', 0),
        ]);

        $report = $service->compile($transactions);

        $this->assertSame(2, $report['summary']['tickets']);
        $this->assertSame(900.0, $report['summary']['net_collection']);
        $this->assertSame([
            'sold' => 2,
            'rebooked' => 1,
            'cancelled' => 1,
            'voided' => 1,
            'refunded' => 1,
        ], $report['activity']);

        $this->assertSame('Alice', $report['cashier_collections'][0]['cashier']);
        $this->assertSame(600.0, $report['cashier_collections'][0]['summary']['net_collection']);
        $this->assertSame('Ben', $report['cashier_collections'][1]['cashier']);
        $this->assertSame(300.0, $report['cashier_collections'][1]['summary']['net_collection']);

        $this->assertSame('Counter', $report['channel_collections'][0]['channel']);
        $this->assertSame(500.0, $report['channel_collections'][0]['amount']);
        $this->assertSame('Kiosk', $report['channel_collections'][1]['channel']);
        $this->assertSame(900.0, $report['channel_collections'][1]['amount']);
    }

    private function transaction(
        int $adminId,
        string $cashier,
        string $status,
        string $source,
        float $amount,
        float $discount = 0,
        float $surcharge = 0
    ): object {
        return (object) [
            'admin_id' => $adminId,
            'admin' => (object) ['name' => $cashier, 'username' => strtolower($cashier)],
            'status' => $status,
            'source' => $source,
            'amount' => $amount,
            'discount_amount' => $discount,
            'surcharge_amount' => $surcharge,
        ];
    }
}
