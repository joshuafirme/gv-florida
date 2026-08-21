<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Models\Deposit;
use Tests\TestCase;

class DepositPaymentFilterTest extends TestCase
{
    public function test_payment_search_includes_the_payment_request_id(): void
    {
        $query = Deposit::query()->paymentSearch('REQ-12345');

        $this->assertStringContainsString('trx', strtolower($query->toSql()));
        $this->assertContains('%REQ-12345%', $query->getBindings());
    }

    public function test_all_payment_filters_include_date_source_method_status_and_processor(): void
    {
        $query = Deposit::query()->paymentFilters([
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-21',
            'source' => 'kiosk',
            'payment_method' => 'channel:gcash_ph',
            'payment_status' => Status::PAYMENT_INITIATE,
            'processed_by' => 'admin:12',
        ]);
        $sql = strtolower($query->toSql());
        $bindings = $query->getBindings();

        $this->assertStringContainsString('created_at', $sql);
        $this->assertStringContainsString('kiosk_id', $sql);
        $this->assertStringContainsString('pchannel', $sql);
        $this->assertStringContainsString('status', $sql);
        $this->assertStringContainsString('processed_by_admin_id', $sql);
        $this->assertContains('gcash_ph', $bindings);
        $this->assertContains(Status::PAYMENT_INITIATE, $bindings);
        $this->assertContains(12, $bindings);
    }
}
