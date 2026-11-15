<?php

namespace Tests\Unit;

use App\Models\FleetType;
use App\Models\Trip;
use App\Models\TripPrioritySeatAccess;
use App\Services\PrioritySeatAccessService;
use App\Services\ScheduleBoardBroadcaster;
use App\Services\SeatLayoutService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class PrioritySeatAccessServiceTest extends TestCase
{
    public function test_online_priority_seats_default_to_unavailable(): void
    {
        [$service, $trip] = $this->serviceAndTrip();
        $prioritySeats = $service->prioritySeatIds($trip->fleetType);

        $this->assertNotEmpty($prioritySeats);
        $this->assertSame(
            $prioritySeats,
            $service->restrictedSeatIds($trip, '2026-11-15', false)
        );
    }

    public function test_walk_in_priority_seats_default_to_unavailable(): void
    {
        [$service, $trip] = $this->serviceAndTrip();

        $this->assertSame(
            $service->prioritySeatIds($trip->fleetType),
            $service->restrictedSeatIds($trip, '2026-11-15', true)
        );
    }

    public function test_authorized_date_setting_releases_priority_seats_for_walk_in_only(): void
    {
        [$service, $trip] = $this->serviceAndTrip();
        $access = new TripPrioritySeatAccess();
        $access->setDateFormat('Y-m-d H:i:s');
        $access->journey_date = Carbon::parse('2026-11-15');
        $access->walk_in_enabled = true;
        $trip->setRelation('prioritySeatAccesses', collect([$access]));

        $this->assertSame([], $service->restrictedSeatIds($trip, '2026-11-15', true));
        $this->assertNotEmpty($service->restrictedSeatIds($trip, '2026-11-15', false));
        $this->assertNotEmpty($service->restrictedSeatIds($trip, '2026-11-16', true));
    }

    public function test_online_and_walk_in_access_are_controlled_independently(): void
    {
        [$service, $trip] = $this->serviceAndTrip();
        $access = new TripPrioritySeatAccess();
        $access->setDateFormat('Y-m-d H:i:s');
        $access->journey_date = Carbon::parse('2026-11-15');
        $access->online_enabled = true;
        $access->walk_in_enabled = false;
        $trip->setRelation('prioritySeatAccesses', collect([$access]));

        $this->assertSame([], $service->restrictedSeatIds($trip, '2026-11-15', false));
        $this->assertNotEmpty($service->restrictedSeatIds($trip, '2026-11-15', true));
    }

    public function test_seat_layout_exposes_a_distinct_walk_in_only_state(): void
    {
        [$service, $trip, $seatLayouts] = $this->serviceAndTrip();
        $prioritySeats = $service->prioritySeatIds($trip->fleetType);
        $layout = $seatLayouts->layout($trip->fleetType, ['walk_in_only' => $prioritySeats]);
        $priorityCells = collect($layout['decks'])
            ->flatMap(fn (array $deck) => collect($deck['rows'])->flatMap(
                fn (array $row) => collect($row['groups'])->flatMap(fn (array $group) => $group['cells'])
            ))
            ->where('is_sc_pwd', true);

        $this->assertNotEmpty($priorityCells);
        $this->assertSame(['walk_in_only'], $priorityCells->pluck('state')->unique()->values()->all());
        $this->assertSame(
            $seatLayouts->seatIds($trip->fleetType)->count() - count($prioritySeats),
            $seatLayouts->availableSeatCount($trip->fleetType, ['walk_in_only' => $prioritySeats])
        );
    }

    private function serviceAndTrip(): array
    {
        $seatLayouts = new SeatLayoutService();
        $service = new PrioritySeatAccessService(
            $seatLayouts,
            $this->createMock(ScheduleBoardBroadcaster::class)
        );
        $fleetType = new FleetType();
        $fleetType->name = 'Executive Sleeper';
        $fleetType->seat_layout = '2x1';
        $fleetType->deck_seats = [9];
        $fleetType->last_row = [0];
        $fleetType->prefixes = ['D'];
        $fleetType->disabled_seats = [];
        $fleetType->cr_position = null;
        $fleetType->cr_row = 0;
        $fleetType->cr_row_covered = 0;
        $fleetType->cr_column_covered = 0;
        $fleetType->cr_override_seat = false;

        $trip = new Trip();
        $trip->setRelation('fleetType', $fleetType);
        $trip->setRelation('prioritySeatAccesses', collect());

        return [$service, $trip, $seatLayouts];
    }
}
