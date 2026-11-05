<?php

namespace Tests\Unit;

use Tests\TestCase;

class OnlineVoucherLayoutTest extends TestCase
{
    public function test_online_and_kiosk_tickets_use_their_respective_print_templates(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/User/UserController.php'));

        $this->assertStringContainsString("'Template::user.print_ticket_online'", $controller);
        $this->assertStringContainsString("'Template::user.print_ticket'", $controller);
        $this->assertStringContainsString("'deposit.userDiscount'", $controller);
        $this->assertStringContainsString("'paymentSourceDeposit.userDiscount'", $controller);
        $this->assertStringContainsString("'renderForPdf' => true", $controller);
        $this->assertStringContainsString('registerOnlineTicketFonts($pdf)', $controller);
        $this->assertStringContainsString("'family' => 'Montserrat'", $controller);
        $this->assertStringContainsString("'fontCache' => \$fontCache", $controller);
        $this->assertStringContainsString("'defaultMediaType' => 'print'", $controller);
        $this->assertStringContainsString("'chroot' => [base_path(), public_path()]", $controller);
    }

    public function test_online_voucher_prioritizes_validation_and_journey_details(): void
    {
        $view = file_get_contents(resource_path('views/templates/basic/user/print_ticket_online.blade.php'));

        $this->assertStringContainsString('Booking Reference / PNR', $view);
        $this->assertStringContainsString('Scan for terminal validation', $view);
        $this->assertStringContainsString('Departure Date', $view);
        $this->assertStringContainsString('Departure Time', $view);
        $this->assertStringContainsString('Passenger &amp; Seat Details', $view);
        $this->assertStringContainsString('Transaction Reference', $view);
        $this->assertStringContainsString('@page { size: A4 portrait; margin: 9mm; }', $view);
        $this->assertStringContainsString('@media (max-width: 640px)', $view);
        $this->assertStringContainsString('@media print', $view);
        $this->assertStringContainsString('.qr-code { height: 78px; width: 78px; }', $view);
        $this->assertStringContainsString("font-family: 'Montserrat'", $view);
        $this->assertStringContainsString('Montserrat-ExtraBold.ttf', $view);
        $this->assertStringContainsString('@unless ($renderForPdf)', $view);
        $this->assertStringContainsString("'PHP ' . number_format", $view);
        $this->assertStringNotContainsString('showAmount($totalPaid)', $view);
    }
}
