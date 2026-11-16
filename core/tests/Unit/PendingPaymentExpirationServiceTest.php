<?php

namespace Tests\Unit;

use App\Models\Deposit;
use App\Services\PendingPaymentExpirationService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class PendingPaymentExpirationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('booking.kiosk_pending_payment_expiry_minutes', 15);
    }

    public function test_voucher_expires_exactly_fifteen_minutes_after_creation(): void
    {
        $createdAt = CarbonImmutable::parse('2026-08-21 10:05:30', 'Asia/Manila');

        $expiresAt = (new PendingPaymentExpirationService())->expiresAt($createdAt);

        $this->assertSame('2026-08-21 10:20:30', $expiresAt->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-21 10:05:30', $createdAt->format('Y-m-d H:i:s'));
    }

    public function test_voucher_uses_the_configured_kiosk_expiration(): void
    {
        config()->set('booking.kiosk_pending_payment_expiry_minutes', 22);
        $createdAt = CarbonImmutable::parse('2026-08-21 10:05:30', 'Asia/Manila');

        $expiresAt = (new PendingPaymentExpirationService())->expiresAt($createdAt);

        $this->assertSame(22, PendingPaymentExpirationService::expirationMinutes());
        $this->assertSame('2026-08-21 10:27:30', $expiresAt->format('Y-m-d H:i:s'));
    }

    public function test_non_kiosk_fallback_keeps_its_existing_expiration(): void
    {
        config()->set('booking.kiosk_pending_payment_expiry_minutes', 22);
        $createdAt = CarbonImmutable::parse('2026-08-21 10:05:30', 'Asia/Manila');

        $expiresAt = (new PendingPaymentExpirationService())->expiresAt($createdAt, false);

        $this->assertSame(15, PendingPaymentExpirationService::expirationMinutes(false));
        $this->assertSame('2026-08-21 10:20:30', $expiresAt->format('Y-m-d H:i:s'));
    }

    public function test_kiosk_payment_views_do_not_use_a_hard_coded_expiration(): void
    {
        $adminPayments = file_get_contents(resource_path('views/admin/deposit/log.blade.php'));
        $paymentDone = file_get_contents(resource_path('views/templates/basic/user/payment/done.blade.php'));
        $kioskVoucher = file_get_contents(resource_path('views/templates/basic/user/print_ticket_kiosk.blade.php'));

        $this->assertStringContainsString('->expiresAt($deposit->created_at, $ticket?->isKioskBooking() ?? true)', $adminPayments);
        $this->assertStringNotContainsString('addMinutes(15)', $adminPayments);
        $this->assertStringNotContainsString('15 mins 00 secs', $paymentDone);
        $this->assertStringContainsString('PendingPaymentExpirationService::expirationMinutes()', $kioskVoucher);
        $this->assertStringNotContainsString('valid for 15 minutes', $kioskVoucher);
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
