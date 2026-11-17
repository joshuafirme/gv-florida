<?php

namespace Tests\Unit;

use App\Models\CashierTransactionEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CashierTransactionEventPaymentMovementTest extends TestCase
{
    #[DataProvider('nonMonetaryStatuses')]
    public function test_zero_value_rebooking_and_validation_events_hide_payment_details(string $status): void
    {
        $transaction = new CashierTransactionEvent([
            'status' => $status,
            'amount' => 0,
            'payment_method' => 'GCash',
        ]);

        $this->assertFalse($transaction->hasReportPaymentMovement());
    }

    public function test_nonzero_rebooking_amount_remains_visible(): void
    {
        $transaction = new CashierTransactionEvent([
            'status' => 'Rebooked',
            'amount' => 250,
        ]);

        $this->assertTrue($transaction->hasReportPaymentMovement());
    }

    public function test_zero_value_statuses_outside_rebooking_and_validation_remain_visible(): void
    {
        $transaction = new CashierTransactionEvent([
            'status' => 'Sold',
            'amount' => 0,
        ]);

        $this->assertTrue($transaction->hasReportPaymentMovement());
    }

    public function test_discount_override_uses_the_discount_applied_display_label(): void
    {
        $transaction = new CashierTransactionEvent(['status' => 'Discount Override']);

        $this->assertSame('Discount Applied', $transaction->status_label);
        $this->assertSame('Discount Override', $transaction->status);
    }

    public static function nonMonetaryStatuses(): array
    {
        return [
            'rebooked' => ['Rebooked'],
            'seat changed' => ['Seat Changed'],
            'validated' => ['Validated'],
        ];
    }
}
