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

        $layout = (new SeatLayoutService())->layout($fleetType);
        $rows = $layout['decks'][0]['rows'];

        $this->assertSame(['left', 'right'], array_column($rows[0]['groups'], 'name'));
        $this->assertSame(['D1', 'D2'], array_column($rows[0]['groups'][0]['cells'], 'label'));
        $this->assertSame(['D3', 'D4'], array_column($rows[0]['groups'][1]['cells'], 'label'));
        $this->assertSame(['D5', 'D6'], array_column($rows[1]['groups'][0]['cells'], 'label'));
        $this->assertSame(['D7', 'D8'], array_column($rows[1]['groups'][1]['cells'], 'label'));
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

        $layout = (new SeatLayoutService())->layout($fleetType);
        $rows = $layout['decks'][0]['rows'];

        $this->assertSame(['left', 'center', 'right'], array_column($rows[0]['groups'], 'name'));
        $this->assertSame(['D1', 'D2', 'D3'], $this->seatLabels($rows[0]));
        $this->assertSame(['D4', 'D5'], $this->seatLabels($rows[1]));
        $this->assertTrue($rows[1]['centered']);
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

    public function test_comfort_room_covers_configured_rows_and_columns_with_override(): void
    {
        $fleetType = new FleetType();
        $fleetType->seat_layout = '2x2';
        $fleetType->deck_seats = [20];
        $fleetType->prefixes = ['D'];
        $fleetType->last_row = [0];
        $fleetType->disabled_seats = [];
        $fleetType->cr_position = 'Right';
        $fleetType->cr_row = 3;
        $fleetType->cr_row_covered = 2;
        $fleetType->cr_column_covered = 2;
        $fleetType->cr_override_seat = true;

        $layout = (new SeatLayoutService())->layout($fleetType);
        $rightGroup = $layout['decks'][0]['rows'][2]['groups'][1]['cells'];

        $this->assertSame(['cr', 'covered'], array_column($rightGroup, 'type'));
        $this->assertSame(2, $rightGroup[0]['span']);
        $this->assertSame(2, $rightGroup[0]['row_span']);
        $this->assertNotContains('1-D11', $layout['seat_ids']);
        $this->assertNotContains('1-D12', $layout['seat_ids']);
        $this->assertNotContains('1-D15', $layout['seat_ids']);
        $this->assertNotContains('1-D16', $layout['seat_ids']);
        $this->assertCount(16, $layout['seat_ids']);
    }

    public function test_comfort_room_shifts_seat_labels_when_override_is_disabled(): void
    {
        $fleetType = new FleetType();
        $fleetType->seat_layout = '2x2';
        $fleetType->deck_seats = [20];
        $fleetType->prefixes = ['D'];
        $fleetType->last_row = [0];
        $fleetType->disabled_seats = [];
        $fleetType->cr_position = 'Right';
        $fleetType->cr_row = 3;
        $fleetType->cr_row_covered = 2;
        $fleetType->cr_column_covered = 2;
        $fleetType->cr_override_seat = false;

        $layout = (new SeatLayoutService())->layout($fleetType);

        $this->assertCount(20, $layout['seat_ids']);
        $this->assertContains('1-D15', $layout['seat_ids']);
        $this->assertContains('1-D16', $layout['seat_ids']);
        $this->assertSame(['D11', 'D12'], $this->seatLabels($layout['decks'][0]['rows'][3]));
        $this->assertSame(['D13', 'D14', 'D15', 'D16'], $this->seatLabels($layout['decks'][0]['rows'][4]));
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

    public function test_last_row_configuration_applies_independently_to_every_deck(): void
    {
        $fleetType = new FleetType();
        $fleetType->seat_layout = '2x2';
        $fleetType->deck_seats = [16, 15];
        $fleetType->last_row = [4, 3];
        $fleetType->prefixes = ['D', 'U'];
        $fleetType->disabled_seats = [];
        $fleetType->cr_position = null;
        $fleetType->cr_row = null;
        $fleetType->cr_override_seat = false;

        $layout = (new SeatLayoutService())->layout($fleetType);
        $lowerLastRow = collect($layout['decks'][0]['rows'])->last();
        $upperLastRow = collect($layout['decks'][1]['rows'])->last();

        $this->assertTrue($lowerLastRow['centered']);
        $this->assertSame(['D13', 'D14', 'D15', 'D16'], $this->seatLabels($lowerLastRow));
        $this->assertTrue($upperLastRow['centered']);
        $this->assertSame(['U13', 'U14', 'U15'], $this->seatLabels($upperLastRow));
    }

    public function test_two_deck_manifest_rows_share_one_legal_portrait_page_budget(): void
    {
        $service = new SeatLayoutService();
        $layout = $service->layout($this->sleeperFleet());
        $print = $service->manifestPrintSizing($layout);

        $this->assertSame(2, $print['deck_count']);
        $this->assertSame(
            collect($layout['decks'])->sum(fn (array $deck) => count($deck['rows'])),
            $print['row_count']
        );
        $this->assertLessThanOrEqual(300.0, $print['row_height_mm'] * $print['row_count']);
        $this->assertLessThanOrEqual(30.0, $print['row_height_mm']);
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
        $fleetType->cr_column_covered = 1;
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
