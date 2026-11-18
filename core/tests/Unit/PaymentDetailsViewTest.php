<?php

namespace Tests\Unit;

use Tests\TestCase;

class PaymentDetailsViewTest extends TestCase
{
    public function test_payment_details_use_the_requested_labels_and_travel_fields(): void
    {
        $view = file_get_contents(resource_path('views/admin/deposit/detail.blade.php'));

        foreach ([
            "@lang('Travel Date')",
            "@lang('Departure Time')",
            "@lang('Seat Number(s)')",
            "@lang('Passenger Name')",
            "@lang('Contact Number')",
            "@lang('Fare Amount')",
            "@lang('Payment Fee')",
        ] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        $this->assertStringContainsString('$passengerNames', $view);
        $this->assertStringContainsString('$passengerContacts', $view);
        $this->assertStringContainsString('trip?->schedule?->start_from', $view);
    }
}
