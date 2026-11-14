<?php

namespace Tests\Unit;

use Tests\TestCase;

class PassengerContactVisibilityTest extends TestCase
{
    public function test_payment_modules_display_the_passenger_contact_number(): void
    {
        $list = file_get_contents(resource_path('views/admin/deposit/partials/ticket-list.blade.php'));
        $details = file_get_contents(resource_path('views/admin/deposit/detail.blade.php'));
        $paymentFlow = file_get_contents(resource_path('views/admin/deposit/log.blade.php'));

        $this->assertStringContainsString("\$ticketRow['contact_number']", $list);
        $this->assertStringContainsString('Passenger Contact Number', $details);
        $this->assertStringContainsString('ticket.contact_number', $paymentFlow);
    }

    public function test_booking_history_modules_display_the_passenger_contact_number(): void
    {
        $views = [
            'views/admin/ticket/log.blade.php' => 'passengerContactNumber',
            'views/admin/ticket/all.blade.php' => 'passenger_contact',
            'views/admin/ticket/rebooked.blade.php' => 'passenger_contact',
            'views/admin/ticket/refunded.blade.php' => "passenger['contact_number']",
            'views/admin/ticket/cancelled.blade.php' => "passenger['contact_number']",
            'views/admin/ticket/voided.blade.php' => 'passengerContact',
            'views/admin/ticket/online-validation.blade.php' => 'passenger_phone',
            'views/templates/basic/user/partials/book_logs.blade.php' => 'contact_numbers',
            'views/templates/basic/modals/book_info.blade.php' => 'contact_numbers',
        ];

        foreach ($views as $path => $expected) {
            $this->assertStringContainsString($expected, file_get_contents(resource_path($path)), $path);
        }
    }
}
