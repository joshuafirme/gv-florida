<?php

namespace Tests\Unit;

use App\Services\TicketTransferService;
use PHPUnit\Framework\TestCase;

class TicketTransferServiceTest extends TestCase
{
    public function test_passenger_requested_transfer_requires_an_upgrade_difference(): void
    {
        $quote = (new TicketTransferService())->quote('passenger', 1680, 2100);

        $this->assertSame('Upgraded', $quote['status']);
        $this->assertSame(2100.0, $quote['final_fare']);
        $this->assertSame(420.0, $quote['amount_due']);
        $this->assertSame(420.0, $quote['cash_adjustment']);
    }

    public function test_passenger_requested_downgrade_retains_the_original_fare_without_refund(): void
    {
        $quote = (new TicketTransferService())->quote('passenger', 2100, 1680);

        $this->assertSame('Downgraded', $quote['status']);
        $this->assertSame(2100.0, $quote['final_fare']);
        $this->assertSame(0.0, $quote['refund_amount']);
        $this->assertSame(0.0, $quote['cash_adjustment']);
    }

    public function test_equal_passenger_requested_fare_is_transferred_without_adjustment(): void
    {
        $quote = (new TicketTransferService())->quote('passenger', 2100, 2100);

        $this->assertSame('Transferred', $quote['status']);
        $this->assertSame(2100.0, $quote['final_fare']);
        $this->assertSame(0.0, $quote['cash_adjustment']);
    }

    public function test_management_transfer_refunds_only_a_lower_selected_fare(): void
    {
        $lower = (new TicketTransferService())->quote('management', 2100, 1680);
        $higher = (new TicketTransferService())->quote('management', 1680, 2100);

        $this->assertSame('Management Transferred', $lower['status']);
        $this->assertSame(1680.0, $lower['final_fare']);
        $this->assertSame(420.0, $lower['refund_amount']);
        $this->assertSame(-420.0, $lower['cash_adjustment']);

        $this->assertSame(1680.0, $higher['final_fare']);
        $this->assertSame(0.0, $higher['amount_due']);
        $this->assertSame(0.0, $higher['cash_adjustment']);
    }
}
