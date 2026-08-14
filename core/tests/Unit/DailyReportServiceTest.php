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

    public function test_it_keeps_online_and_kiosk_sales_in_the_daily_total_without_listing_them_as_cashiers(): void
    {
        $recorder = $this->createMock(CashierTransactionRecorder::class);
        $service = new DailyReportService(
            $recorder,
            new CashierDashboardService($recorder)
        );

        $transactions = collect([
            $this->transaction(null, null, 'Sold', 'Kiosk', 900),
            $this->transaction(null, null, 'Sold', 'Online', 500),
        ]);

        $report = $service->compile($transactions);

        $this->assertSame(2, $report['summary']['tickets']);
        $this->assertSame(1400.0, $report['summary']['net_collection']);
        $this->assertCount(0, $report['cashier_collections']);
        $this->assertSame(['Kiosk', 'Online'], $report['channel_collections']->pluck('channel')->all());
    }

    public function test_it_filters_transactions_by_type_source_processor_and_payment_method(): void
    {
        $recorder = $this->createMock(CashierTransactionRecorder::class);
        $service = new DailyReportService(
            $recorder,
            new CashierDashboardService($recorder)
        );

        $transactions = collect([
            $this->transaction(1, 'Alice', 'Sold', 'Counter', 900, paymentMethod: 'Cash'),
            $this->transaction(1, 'Alice', 'Refunded', 'Counter', -300, paymentMethod: 'Cash'),
            $this->transaction(null, null, 'Sold', 'Online', 500, paymentMethod: 'GCash'),
        ]);

        $filtered = $service->filterTransactions($transactions, [
            'transaction_type' => 'Sold',
            'source' => 'Counter',
            'processed_by' => 'admin:1',
            'payment_method' => 'Cash',
        ]);

        $this->assertCount(1, $filtered);
        $this->assertSame(900.0, $filtered->first()->amount);

        $online = $service->filterTransactions($transactions, [
            'processed_by' => 'channel:Online',
        ]);

        $this->assertCount(1, $online);
        $this->assertSame('GCash', $online->first()->payment_method);
    }

    private function transaction(
        ?int $adminId,
        ?string $cashier,
        string $status,
        string $source,
        float $amount,
        float $discount = 0,
        float $surcharge = 0,
        string $paymentMethod = 'Cash'
    ): object {
        return (object) [
            'admin_id' => $adminId,
            'admin' => $cashier ? (object) ['name' => $cashier, 'username' => strtolower($cashier)] : null,
            'status' => $status,
            'source' => $source,
            'processed_by_label' => $cashier ?: $source,
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'discount_amount' => $discount,
            'surcharge_amount' => $surcharge,
        ];
    }
}
