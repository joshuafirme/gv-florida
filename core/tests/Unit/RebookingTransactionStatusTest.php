<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\VehicleTicketController;
use App\Models\CashierTransactionEvent;
use ReflectionMethod;
use Tests\TestCase;

class RebookingTransactionStatusTest extends TestCase
{
    public function test_only_a_seat_change_is_classified_as_seat_changed(): void
    {
        $history = $this->history();
        $history['new']['seats_by_reference']['1001'] = '1-D2';

        $this->assertSame(
            CashierTransactionEvent::STATUS_SEAT_CHANGED,
            $this->classify($history)
        );
    }

    public function test_trip_or_schedule_change_is_classified_as_rebooked(): void
    {
        $history = $this->history();
        $history['new']['trip_id'] = 12;
        $history['new']['departure_at'] = '2026-11-21T09:00:00+08:00';
        $history['new']['seats_by_reference']['1001'] = '1-D2';

        $this->assertSame(
            CashierTransactionEvent::STATUS_REBOOKED,
            $this->classify($history)
        );
    }

    public function test_unchanged_booking_is_not_recorded_as_a_change(): void
    {
        $this->assertNull($this->classify($this->history()));
    }

    private function classify(array $history): ?string
    {
        $method = new ReflectionMethod(VehicleTicketController::class, 'rebookingTransactionStatus');

        return $method->invoke(new VehicleTicketController(), $history);
    }

    private function history(): array
    {
        $details = [
            'trip_id' => 10,
            'route_id' => 4,
            'trip' => 'Sampaloc - Tuguegarao',
            'trip_class' => 'Executive Sleeper',
            'journey_date' => '2026-11-21',
            'departure_at' => '2026-11-21T08:00:00+08:00',
            'pickup_point' => 1,
            'dropping_point' => 8,
            'drop_off' => 'Tuguegarao',
            'km_post' => 'KM 449',
            'fare' => 2300.0,
            'seats' => ['1-D1'],
            'seats_by_reference' => ['1001' => '1-D1'],
        ];

        return [
            'previous' => $details,
            'new' => $details,
        ];
    }
}
