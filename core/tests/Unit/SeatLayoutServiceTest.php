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

    public function test_it_uses_the_same_cr_and_custom_back_row_geometry_for_every_consumer(): void
    {
        $fleetType = $this->sleeperFleet();
        $layout = (new SeatLayoutService())->layout($fleetType);

        $this->assertNotContains('1-D9', $layout['seat_ids']);
        $this->assertContains('2-U16', $layout['seat_ids']);

        $lowerRows = $layout['decks'][0]['rows'];
        $this->assertSame(['D1', 'D2', 'D3'], $this->seatLabels($lowerRows[0]));
        $this->assertTrue($this->seatCells($lowerRows[0])->every(
            fn (array $cell) => $cell['is_sc_pwd']
        ));
        $this->assertSame(['D7', 'D8', 'CR'], $this->cellLabels($lowerRows[2]));

        $upperLastRow = collect($layout['decks'][1]['rows'])->last();
        $this->assertTrue($upperLastRow['centered']);
        $this->assertSame(['U13', 'U14', 'U15', 'U16'], $this->seatLabels($upperLastRow));
    }

    public function test_non_operational_and_locked_states_apply_to_custom_back_row_seats(): void
    {
        $fleetType = $this->sleeperFleet();
        $fleetType->disabled_seats = ['U16'];

        $layout = (new SeatLayoutService())->layout($fleetType, [
            'locked' => ['1-D1'],
            'booked' => ['U15'],
        ]);
        $cells = collect($layout['decks'])
            ->flatMap(fn (array $deck) => collect($deck['rows'])->flatMap(fn (array $row) => $this->seatCells($row)))
            ->keyBy('seat_id');

        $this->assertSame('locked', $cells['1-D1']['state']);
        $this->assertSame('booked', $cells['2-U15']['state']);
        $this->assertSame('disabled', $cells['2-U16']['state']);
        $this->assertSame(['2-U16'], $layout['disabled_seat_ids']);
    }

    private function sleeperFleet(): FleetType
    {
        $fleetType = new FleetType();
        $fleetType->name = 'Executive Sleeper';
        $fleetType->seat_layout = '2x1';
        $fleetType->deck_seats = [18, 16];
        $fleetType->last_row = [0, 4];
        $fleetType->prefixes = ['D', 'U'];
        $fleetType->disabled_seats = [];
        $fleetType->cr_position = 'Right';
        $fleetType->cr_row = 3;
        $fleetType->cr_row_covered = 1;
        $fleetType->cr_override_seat = true;

        return $fleetType;
    }

    private function seatCells(array $row)
    {
        return collect($row['groups'])
            ->flatMap(fn (array $group) => $group['cells'])
            ->where('type', 'seat')
            ->values();
    }

    private function seatLabels(array $row): array
    {
        return $this->seatCells($row)->pluck('label')->all();
    }

    private function cellLabels(array $row): array
    {
        return collect($row['groups'])
            ->flatMap(fn (array $group) => $group['cells'])
            ->reject(fn (array $cell) => $cell['type'] === 'empty')
            ->pluck('label')
            ->all();
    }
}
