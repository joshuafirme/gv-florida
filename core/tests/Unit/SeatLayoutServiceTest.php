<?php

namespace Tests\Unit;

use App\Models\FleetType;
use App\Services\SeatLayoutService;
use PHPUnit\Framework\TestCase;

class SeatLayoutServiceTest extends TestCase
{
    public function test_it_builds_and_canonicalizes_fleet_seat_identifiers(): void
    {
        $fleetType = new FleetType();
        $fleetType->seat_layout = '1x1';
        $fleetType->deck_seats = [4];
        $fleetType->prefixes = ['D'];
        $fleetType->disabled_seats = ['D2'];
        $fleetType->cr_position = null;
        $fleetType->cr_row = null;
        $fleetType->cr_override_seat = false;

        $service = new SeatLayoutService();

        $this->assertSame(
            ['1-D1', '1-D2', '1-D3', '1-D4'],
            $service->seatIds($fleetType)->all()
        );
        $this->assertSame('1-D2', $service->canonicalSeatId($fleetType, 'd2'));
        $this->assertSame('1-D3', $service->canonicalSeatId($fleetType, '1-d3'));
        $this->assertNull($service->canonicalSeatId($fleetType, 'D9'));
        $this->assertSame(['1-D2'], $service->disabledSeatIds($fleetType));
    }
}
