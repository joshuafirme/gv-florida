<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\VehicleTicketController;
use App\Models\BookedTicket;
use App\Models\Counter;
use App\Models\Trip;
use App\Models\VehicleRoute;
use ReflectionMethod;
use Tests\TestCase;

class SameTripTransferTest extends TestCase
{
    public function test_current_trip_lists_only_different_downstream_drop_offs(): void
    {
        $trip = $this->trip(10, 1, 4, [1, 2, 3, 4]);
        $ticket = (new BookedTicket())->forceFill([
            'trip_id' => 10,
            'pickup_point' => 1,
            'dropping_point' => 4,
        ]);

        $dropIds = $this->invoke('transferSelectableDropIds', [$trip, $ticket]);

        $this->assertSame([2, 3], $dropIds);
    }

    public function test_reverse_route_lists_only_different_downstream_drop_offs(): void
    {
        $trip = $this->trip(10, 4, 1, [1, 2, 3, 4]);
        $ticket = (new BookedTicket())->forceFill([
            'trip_id' => 10,
            'pickup_point' => 4,
            'dropping_point' => 1,
        ]);

        $dropIds = $this->invoke('transferSelectableDropIds', [$trip, $ticket]);

        $this->assertSame([3, 2], $dropIds);
    }

    public function test_same_trip_drop_off_transfer_preserves_trip_date_and_seat(): void
    {
        $ticket = (new BookedTicket())->forceFill([
            'trip_id' => 10,
            'pickup_point' => 1,
            'dropping_point' => 4,
            'date_of_journey' => '2026-11-08',
            'seats' => ['1-D1'],
            'ticket_count' => 1,
            'unit_price' => 2160,
            'sub_total' => 2160,
        ]);
        $trip = (new Trip())->forceFill(['id' => 10]);
        $drop = (new Counter())->forceFill(['id' => 3]);

        $this->invoke('fillTransferredTicketDetails', [
            $ticket,
            $ticket,
            $trip,
            $drop,
            '2026-11-08',
            '1-D1',
            ['final_fare' => 1840],
        ]);

        $this->assertSame(10, (int) $ticket->trip_id);
        $this->assertSame('2026-11-08', $ticket->date_of_journey);
        $this->assertSame(['1-D1'], $ticket->seats);
        $this->assertSame(3, (int) $ticket->dropping_point);
        $this->assertSame([1, 3], $ticket->source_destination);
        $this->assertSame(1840.0, (float) $ticket->unit_price);
        $this->assertSame(1840.0, (float) $ticket->sub_total);
    }

    private function trip(int $id, int $start, int $end, array $stoppages): Trip
    {
        $route = (new VehicleRoute())->forceFill(['stoppages' => $stoppages]);
        $trip = (new Trip())->forceFill([
            'id' => $id,
            'start_from' => $start,
            'end_to' => $end,
        ]);
        $trip->setRelation('route', $route);

        return $trip;
    }

    private function invoke(string $method, array $arguments)
    {
        $reflection = new ReflectionMethod(VehicleTicketController::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs(app(VehicleTicketController::class), $arguments);
    }
}
