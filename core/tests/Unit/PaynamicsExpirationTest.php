<?php

namespace Tests\Unit;

use App\Services\Paynamics;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class PaynamicsExpirationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('booking.kiosk_pending_payment_expiry_minutes', 15);
    }

    public function test_kiosk_transaction_expires_fifteen_minutes_after_gateway_creation(): void
    {
        $createdAt = CarbonImmutable::parse('2026-08-25 14:00:00', 'Asia/Manila');

        $expiresAt = Paynamics::expiresAt(true, $createdAt);

        $this->assertSame(15, Paynamics::expirationMinutes(true));
        $this->assertSame('08/25/2026 14:15:00', $expiresAt->format('m/d/Y H:i:s'));
    }

    public function test_kiosk_transaction_uses_the_configured_expiration(): void
    {
        config()->set('booking.kiosk_pending_payment_expiry_minutes', 20);
        $createdAt = CarbonImmutable::parse('2026-08-25 14:00:00', 'Asia/Manila');

        $expiresAt = Paynamics::expiresAt(true, $createdAt);

        $this->assertSame(20, Paynamics::expirationMinutes(true));
        $this->assertSame('08/25/2026 14:20:00', $expiresAt->format('m/d/Y H:i:s'));
    }

    public function test_online_transaction_expires_thirty_minutes_after_gateway_creation(): void
    {
        $createdAt = CarbonImmutable::parse('2026-08-25 14:00:00', 'Asia/Manila');

        $expiresAt = Paynamics::expiresAt(false, $createdAt);

        $this->assertSame(30, Paynamics::expirationMinutes(false));
        $this->assertSame('08/25/2026 14:30:00', $expiresAt->format('m/d/Y H:i:s'));
    }
}
