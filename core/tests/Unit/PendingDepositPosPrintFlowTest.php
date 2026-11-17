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
        $confirmation = strpos($view, 'confirmation = await finalizePendingPayment(confirmedPayment);');
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

        $this->assertStringContainsString(
            "requestTimeouts.lease, 'The processing session check timed out.'",
            $view
        );
        $this->assertStringContainsString('if (!error.timedOut) throw error;', $view);
        $this->assertStringContainsString('return await requestPaymentConfirmation(payment);', $view);
        $this->assertStringContainsString('return requestPaymentConfirmation(payment);', $view);
        $this->assertStringContainsString('stopProcessingHeartbeat();', $view);
        $this->assertStringContainsString('startProcessingHeartbeat();', $view);
        $this->assertStringContainsString("'confirmation_url' => route('admin.deposit.pending.confirm')", $controller);
        $this->assertStringContainsString('$paymentCompleted = PaymentController::userDataUpdate($deposit, true, false);', $controller);
        $this->assertStringContainsString('if ($paymentCompleted) {', $controller);
        $this->assertStringContainsString('app()->terminating(function () use ($depositId, $ticketId)', $controller);
        $this->assertStringContainsString("'X-Payment-Attempt-ID': paymentAttemptId", $view);
        $this->assertStringContainsString("'code' => 'processing_session_inactive'", $controller);
        $this->assertStringContainsString('recoverPaymentClaim(confirmedPayment)', $view);
        $this->assertStringNotContainsString('await renewProcessingLease(confirmedPayment);', $view);
    }

    public function test_payment_modal_handoffs_release_focus_before_hiding_the_source_modal(): void
    {
        $view = file_get_contents(resource_path('views/admin/deposit/log.blade.php'));

        $focusRelease = strpos($view, 'releasePaymentModalFocus(sourceElement);');
        $modalHide = strpos($view, 'sourceModal.hide();', $focusRelease);

        $this->assertNotFalse($focusRelease);
        $this->assertNotFalse($modalHide);
        $this->assertLessThan($modalHide, $focusRelease);
        $this->assertStringContainsString(".on('hide.bs.modal', function()", $view);
        $this->assertStringContainsString("modalElement.contains(focusedElement)", $view);
        $this->assertStringContainsString("focusedElement.blur();", $view);
        $this->assertStringContainsString('if (paymentModalTransitionInProgress) return;', $view);
        $this->assertStringContainsString("destinationElement.addEventListener('shown.bs.modal'", $view);
        $this->assertStringContainsString(
            'if (paymentModalTransitionInProgress || paymentFinalizationInProgress) return;',
            $view
        );
    }

    public function test_transient_heartbeat_failures_do_not_discard_the_active_payment(): void
    {
        $view = file_get_contents(resource_path('views/admin/deposit/log.blade.php'));

        $this->assertStringContainsString(
            'const sessionEnded = [404, 409, 410, 419, 422].includes(error.status);',
            $view
        );
        $this->assertStringContainsString('Payment processing will keep retrying.', $view);
        $this->assertStringContainsString('if (!sessionEnded) {', $view);
        $this->assertStringContainsString('confirmedPayment = await recoverPaymentClaim();', $view);
        $this->assertStringContainsString(".on('click.posConfirmPayment', '#posConfirmPrintBtn'", $view);
        $this->assertStringContainsString(".html(confirmPrintButtonDefaultLabel);", $view);
    }
}
