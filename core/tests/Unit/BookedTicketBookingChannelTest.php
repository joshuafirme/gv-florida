<?php

namespace Tests\Unit;

use App\Models\BookedTicket;
use PHPUnit\Framework\TestCase;

class BookedTicketBookingChannelTest extends TestCase
{
    public function test_it_identifies_kiosk_bookings_from_the_saved_kiosk_id(): void
    {
        $ticket = new BookedTicket();

        $ticket->kiosk_id = null;
        $this->assertFalse($ticket->isKioskBooking());

        $ticket->kiosk_id = 0;
        $this->assertFalse($ticket->isKioskBooking());

        $ticket->kiosk_id = 1;
        $this->assertTrue($ticket->isKioskBooking());
    }
}
