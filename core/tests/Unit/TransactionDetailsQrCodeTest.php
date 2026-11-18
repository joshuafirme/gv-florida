<?php

namespace Tests\Unit;

use Tests\TestCase;

class TransactionDetailsQrCodeTest extends TestCase
{
    public function test_my_ticket_transaction_modal_contains_a_lazy_loaded_qr_code(): void
    {
        $modal = file_get_contents(resource_path('views/templates/basic/modals/book_info.blade.php'));
        $script = file_get_contents(resource_path('views/templates/basic/scripts/booking_history.blade.php'));
        $dashboard = file_get_contents(resource_path('views/templates/basic/user/dashboard.blade.php'));
        $history = file_get_contents(resource_path('views/templates/basic/user/booking_history.blade.php'));

        $this->assertStringContainsString('id="ticketModalQrPanel"', $modal);
        $this->assertStringContainsString('id="ticketModalQr"', $modal);
        $this->assertStringContainsString('Scan for terminal validation', $modal);
        $this->assertStringContainsString('setModalQr(ticket.qr_code_url)', $script);
        $this->assertStringContainsString("image.removeAttribute('src')", $script);
        $this->assertStringContainsString("templates.basic.modals.book_info", $dashboard);
        $this->assertStringContainsString("templates.basic.scripts.booking_history", $dashboard);
        $this->assertStringContainsString("templates.basic.modals.book_info", $history);
        $this->assertStringContainsString("templates.basic.scripts.booking_history", $history);
    }

    public function test_qr_endpoint_uses_the_pnr_and_enforces_ticket_ownership(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/User/UserController.php'));
        $routes = file_get_contents(base_path('routes/user.php'));

        $this->assertStringContainsString('public function ticketQrCode(int $id)', $controller);
        $this->assertStringContainsString("->where('user_id', auth()->id())", $controller);
        $this->assertStringContainsString("->generate(\$pnr)", $controller);
        $this->assertStringContainsString("'qr_code_url'", $controller);
        $this->assertStringContainsString("->name('ticket.qr-code')", $routes);
    }
}
