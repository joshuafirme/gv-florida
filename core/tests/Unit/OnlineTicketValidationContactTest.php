<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\OnlineTicketValidationController;
use App\Models\BookedTicket;
use App\Models\User;
use App\Services\CashierTransactionRecorder;
use ReflectionMethod;
use Tests\TestCase;

class OnlineTicketValidationContactTest extends TestCase
{
    public function test_it_exposes_the_online_passengers_email_and_phone_number(): void
    {
        $user = new User();
        $user->forceFill([
            'email' => 'passenger@example.com',
            'dial_code' => '63',
            'mobile' => '9171234567',
        ]);

        $ticket = new BookedTicket();
        $ticket->setRelation('user', $user);

        $controller = new OnlineTicketValidationController(
            $this->createMock(CashierTransactionRecorder::class)
        );
        $method = new ReflectionMethod($controller, 'passengerContact');

        $this->assertSame([
            'email' => 'passenger@example.com',
            'phone' => '+639171234567',
        ], $method->invoke($controller, $ticket));
    }

    public function test_missing_contact_details_are_returned_as_null(): void
    {
        $ticket = new BookedTicket();
        $ticket->setRelation('user', null);

        $controller = new OnlineTicketValidationController(
            $this->createMock(CashierTransactionRecorder::class)
        );
        $method = new ReflectionMethod($controller, 'passengerContact');

        $this->assertSame([
            'email' => null,
            'phone' => null,
        ], $method->invoke($controller, $ticket));
    }
}
