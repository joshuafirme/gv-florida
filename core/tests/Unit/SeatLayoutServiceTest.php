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

    public function test_it_builds_manifest_rows_with_the_configured_seat_groups_and_aisle(): void
    {
        $fleetType = new FleetType();
        $fleetType->seat_layout = '2x2';
        $fleetType->deck_seats = [8];
        $fleetType->prefixes = ['D'];
        $fleetType->last_row = null;
        $fleetType->cr_position = null;
        $fleetType->cr_row = null;
        $fleetType->cr_override_seat = false;

        $deck = (new SeatLayoutService())->manifestDecks($fleetType)[0];

        $this->assertSame(4, $deck['seat_columns']);
        $this->assertSame('repeat(2, minmax(0, 1fr)) minmax(20px, .18fr) repeat(2, minmax(0, 1fr))', $deck['grid_template']);
        $this->assertSame(
            ['1-D1', '1-D2', null, '1-D3', '1-D4'],
            array_column($deck['rows'][0]['slots'], 'seat_id')
        );
        $this->assertSame('aisle', $deck['rows'][0]['slots'][2]['type']);
        $this->assertSame(
            ['1-D5', '1-D6', null, '1-D7', '1-D8'],
            array_column($deck['rows'][1]['slots'], 'seat_id')
        );
    }

    public function test_it_supports_three_seat_groups_and_a_centered_custom_last_row(): void
    {
        $fleetType = new FleetType();
        $fleetType->seat_layout = '1x1x1';
        $fleetType->deck_seats = [5];
        $fleetType->prefixes = ['D'];
        $fleetType->last_row = [2];
        $fleetType->cr_position = null;
        $fleetType->cr_row = null;
        $fleetType->cr_override_seat = false;

        $deck = (new SeatLayoutService())->manifestDecks($fleetType)[0];

        $this->assertSame(
            ['1-D1', null, '1-D2', null, '1-D3'],
            array_column($deck['rows'][0]['slots'], 'seat_id')
        );
        $this->assertSame(
            ['1-D4', '1-D5'],
            array_column($deck['rows'][1]['slots'], 'seat_id')
        );
        $this->assertTrue($deck['rows'][1]['centered']);
    }
}
