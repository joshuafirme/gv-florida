<?php

namespace Tests\Unit;

use App\Models\Trip;
use PHPUnit\Framework\TestCase;

class TripBookingChannelTest extends TestCase
{
    public function test_it_resolves_online_and_kiosk_availability_independently(): void
    {
        $trip = new Trip();
        $trip->online_booking_enabled = true;
        $trip->kiosk_booking_enabled = false;

        $this->assertTrue($trip->bookingEnabledFor());
        $this->assertTrue($trip->bookingEnabledFor(null));
        $this->assertFalse($trip->bookingEnabledFor(1));

        $trip->online_booking_enabled = false;
        $trip->kiosk_booking_enabled = true;

        $this->assertFalse($trip->bookingEnabledFor());
        $this->assertTrue($trip->bookingEnabledFor(1));
    }
}
