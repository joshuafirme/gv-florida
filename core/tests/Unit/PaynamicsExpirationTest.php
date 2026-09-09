<?php

namespace Tests\Unit;

use App\Services\Paynamics;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class PaynamicsExpirationTest extends TestCase
{
    public function test_kiosk_transaction_expires_fifteen_minutes_after_gateway_creation(): void
    {
        $createdAt = CarbonImmutable::parse('2026-08-25 14:00:00', 'Asia/Manila');

        $expiresAt = Paynamics::expiresAt(true, $createdAt);

        $this->assertSame(15, Paynamics::expirationMinutes(true));
        $this->assertSame('08/25/2026 14:15:00', $expiresAt->format('m/d/Y H:i:s'));
    }

    public function test_online_transaction_expires_thirty_minutes_after_gateway_creation(): void
    {
        $createdAt = CarbonImmutable::parse('2026-08-25 14:00:00', 'Asia/Manila');

        $expiresAt = Paynamics::expiresAt(false, $createdAt);

        $this->assertSame(30, Paynamics::expirationMinutes(false));
        $this->assertSame('08/25/2026 14:30:00', $expiresAt->format('m/d/Y H:i:s'));
    }
}
