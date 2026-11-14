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
        $confirmation = strpos($view, 'const confirmation = await finalizePendingPayment(confirmedPayment);');
        $slipGeneration = strpos($view, "url: confirmation.print_url || confirmedPayment.print_url", $completion);

        $this->assertNotFalse($completion);
        $this->assertNotFalse($printing);
        $this->assertNotFalse($confirmation);
        $this->assertNotFalse($slipGeneration);
        $this->assertLessThan($completion, $confirmation);
        $this->assertLessThan($slipGeneration, $completion);
        $this->assertLessThan($printing, $completion);
        $this->assertStringContainsString('paymentFinalizationInProgress = false;', $view);
        $this->assertStringContainsString('Printer discovery timed out.', $view);
        $this->assertStringContainsString('Payment was confirmed, but automatic printing failed:', $view);
        $this->assertStringNotContainsString('window.location.search = reloadParams.toString();', $view);
    }

    public function test_confirmation_recovers_from_delayed_requests_without_a_page_refresh(): void
    {
        $view = file_get_contents(resource_path('views/admin/deposit/log.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Admin/DepositController.php'));

        $this->assertStringContainsString('timeout: requestTimeouts.lease', $view);
        $this->assertStringContainsString('if (!error.timedOut) throw error;', $view);
        $this->assertStringContainsString('return await requestPaymentConfirmation(payment);', $view);
        $this->assertStringContainsString('return requestPaymentConfirmation(payment);', $view);
        $this->assertStringContainsString('stopProcessingHeartbeat();', $view);
        $this->assertStringContainsString('startProcessingHeartbeat();', $view);
        $this->assertStringContainsString("'confirmation_url' => route('admin.deposit.pending.confirm')", $controller);
        $this->assertStringContainsString('$paymentCompleted = PaymentController::userDataUpdate($deposit, true, false);', $controller);
        $this->assertStringContainsString('if ($paymentCompleted) {', $controller);
        $this->assertStringContainsString('app()->terminating(function () use ($depositId, $ticketId)', $controller);
    }
}
