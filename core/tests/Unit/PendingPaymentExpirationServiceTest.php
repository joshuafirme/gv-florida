<?php

namespace Tests\Unit;

use App\Models\Deposit;
use App\Services\PendingPaymentExpirationService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class PendingPaymentExpirationServiceTest extends TestCase
{
    public function test_voucher_expires_exactly_fifteen_minutes_after_creation(): void
    {
        $createdAt = CarbonImmutable::parse('2026-08-21 10:05:30', 'Asia/Manila');

        $expiresAt = (new PendingPaymentExpirationService())->expiresAt($createdAt);

        $this->assertSame('2026-08-21 10:20:30', $expiresAt->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-21 10:05:30', $createdAt->format('Y-m-d H:i:s'));
    }

    public function test_gateway_expiry_overrides_the_legacy_voucher_expiry(): void
    {
        $deposit = new Deposit();
        $deposit->setRawAttributes([
            'created_at' => CarbonImmutable::parse('2026-08-25 14:00:00', 'Asia/Manila'),
            'expiry_limit' => '2026-08-25 14:30:00',
        ]);

        $expiresAt = (new PendingPaymentExpirationService())->expiresAtForDeposit($deposit);

        $this->assertSame('2026-08-25 14:30:00', $expiresAt->format('Y-m-d H:i:s'));
    }
}
