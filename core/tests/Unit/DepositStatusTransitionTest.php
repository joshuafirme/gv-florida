<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Models\Deposit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DepositStatusTransitionTest extends TestCase
{
    #[DataProvider('allowedTransitions')]
    public function test_non_final_payments_can_transition(int $currentStatus, int $nextStatus): void
    {
        $this->assertTrue(Deposit::canTransitionStatus($currentStatus, $nextStatus));
    }

    public static function allowedTransitions(): array
    {
        return [
            'initiated to pending' => [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING],
            'initiated to successful' => [Status::PAYMENT_INITIATE, Status::PAYMENT_SUCCESS],
            'pending to successful' => [Status::PAYMENT_PENDING, Status::PAYMENT_SUCCESS],
            'pending to rejected' => [Status::PAYMENT_PENDING, Status::PAYMENT_REJECT],
            'pending to expired' => [Status::PAYMENT_PENDING, Status::PAYMENT_EXPIRED],
        ];
    }

    #[DataProvider('finalStatuses')]
    public function test_final_payment_status_cannot_change(int $finalStatus): void
    {
        foreach ([
            Status::PAYMENT_INITIATE,
            Status::PAYMENT_SUCCESS,
            Status::PAYMENT_PENDING,
            Status::PAYMENT_REJECT,
            Status::PAYMENT_EXPIRED,
        ] as $nextStatus) {
            $expected = $nextStatus === $finalStatus;
            $this->assertSame($expected, Deposit::canTransitionStatus($finalStatus, $nextStatus));
        }
    }

    public static function finalStatuses(): array
    {
        return [
            'successful' => [Status::PAYMENT_SUCCESS],
            'rejected' => [Status::PAYMENT_REJECT],
            'expired' => [Status::PAYMENT_EXPIRED],
        ];
    }
}
