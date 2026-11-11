<?php

namespace Tests\Unit;

use Tests\TestCase;

class PendingDepositPosPrintFlowTest extends TestCase
{
    public function test_payment_completion_is_not_blocked_by_printer_discovery(): void
    {
        $view = file_get_contents(resource_path('views/admin/deposit/log.blade.php'));

        $completion = strpos($view, 'completeConfirmedPayment(confirmedPayment, button, originalLabel);');
        $printing = strpos($view, 'await printReservation(printUrl);', $completion);

        $this->assertNotFalse($completion);
        $this->assertNotFalse($printing);
        $this->assertLessThan($printing, $completion);
        $this->assertStringContainsString('paymentFinalizationInProgress = false;', $view);
        $this->assertStringContainsString('Printer discovery timed out.', $view);
        $this->assertStringContainsString('Payment was confirmed, but automatic printing failed:', $view);
        $this->assertStringNotContainsString('window.location.search = reloadParams.toString();', $view);
    }
}
