<?php

namespace Tests\Unit;

use Tests\TestCase;

class TransferModalLayoutTest extends TestCase
{
    public function test_transfer_modal_body_scrolls_without_dropdown_fields_overflowing(): void
    {
        $view = file_get_contents(resource_path('views/admin/ticket/log.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.transfer-modal-content\s*\{[^}]*flex-direction:\s*column;[^}]*max-height:\s*calc\(100dvh - 2rem\);/s',
            $view
        );
        $this->assertMatchesRegularExpression(
            '/\.transfer-body\s*\{[^}]*min-height:\s*0;[^}]*overflow-y:\s*auto;/s',
            $view
        );
        $this->assertStringContainsString(
            'grid-template-columns: minmax(0, .8fr) minmax(0, 1.4fr) minmax(0, 1fr);',
            $view
        );
        $this->assertStringContainsString('.transfer-fields > .form-group { min-width: 0; }', $view);
        $this->assertMatchesRegularExpression(
            '/#transferTicketModal \.select2-container \.select2-selection__rendered\s*\{[^}]*text-overflow:\s*ellipsis;[^}]*white-space:\s*nowrap;/s',
            $view
        );
    }

    public function test_transfer_selects_release_stale_scroll_locks_before_dynamic_refresh(): void
    {
        $view = file_get_contents(resource_path('views/admin/ticket/log.blade.php'));

        $this->assertStringContainsString('function clearTransferSelectScrollLock(select)', $view);
        $this->assertStringContainsString(
            'instance.$container.parents().off(`scroll.select2.${instance.id}`);',
            $view
        );
        $this->assertStringContainsString("select2:close.transferScrollLock", $view);
        $this->assertStringContainsString('function queueTransferAvailability(renderCurrentTrip = false)', $view);
        $this->assertStringContainsString('clearAllTransferSelectScrollLocks();', $view);
    }

    public function test_active_ticket_lists_launch_the_existing_transfer_workflow(): void
    {
        $log = file_get_contents(resource_path('views/admin/ticket/log.blade.php'));
        $allTickets = file_get_contents(resource_path('views/admin/ticket/all.blade.php'));
        $rebooked = file_get_contents(resource_path('views/admin/ticket/rebooked.blade.php'));
        $onlineValidation = file_get_contents(resource_path('views/admin/ticket/online-validation.blade.php'));

        $this->assertStringContainsString("['transfer', 'refund', 'cancel', 'void']", $log);
        $this->assertStringContainsString("transfer: { className: 'transfer-ticket-btn', attribute: 'data-options-url' }", $log);
        $this->assertStringContainsString("\$event['transfer_url']", $rebooked);
        $this->assertStringContainsString("\$ticket['transfer_url']", $onlineValidation);
        $this->assertStringContainsString("\$ticket['actions']", $allTickets);
    }
}
