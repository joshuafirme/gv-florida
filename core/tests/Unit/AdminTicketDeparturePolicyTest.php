<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Http\Controllers\Admin\VehicleTicketController;
use App\Models\BookedTicket;
use App\Models\SlipSeriesNumber;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class AdminTicketDeparturePolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-28 17:30:00');

        Storage::shouldReceive('exists')->with('settings/advance_booking.json')->andReturn(true);
        Storage::shouldReceive('get')->with('settings/advance_booking.json')->andReturn(json_encode([
            'kiosk_advance_booking_days' => 2,
            'kiosk_booking_cutoff_minutes' => 60,
        ]));

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->time('start_from');
            $table->boolean('status')->default(true);
        });
        Schema::create('vehicle_routes', function (Blueprint $table) {
            $table->id();
            $table->json('stoppages');
            $table->boolean('status')->default(true);
        });
        Schema::create('fleet_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('vehicle_route_id');
            $table->unsignedBigInteger('fleet_type_id');
            $table->unsignedBigInteger('start_from');
            $table->unsignedBigInteger('end_to');
            $table->boolean('status')->default(true);
            $table->string('trip_status');
            $table->json('day_off')->nullable();
        });
        Schema::create('ticket_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fleet_type_id');
            $table->unsignedBigInteger('vehicle_route_id');
        });
        Schema::create('ticket_price_by_stoppages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_price_id');
            $table->json('source_destination');
            $table->decimal('price', 10, 2);
        });
        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(true);
        });

        DB::table('schedules')->insert([
            ['id' => 1, 'start_from' => '17:00:00'],
            ['id' => 2, 'start_from' => '16:45:00'],
        ]);
        DB::table('vehicle_routes')->insert(['id' => 1, 'stoppages' => json_encode(['1', '2', '3'])]);
        DB::table('fleet_types')->insert(['id' => 1, 'name' => 'Test Bus']);
        foreach ([1, 2] as $id) {
            DB::table('trips')->insert([
                'id' => $id,
                'schedule_id' => $id,
                'vehicle_route_id' => 1,
                'fleet_type_id' => 1,
                'start_from' => 1,
                'end_to' => 3,
                'trip_status' => Status::TRIP_BOARDING,
            ]);
        }
        DB::table('ticket_prices')->insert(['id' => 1, 'fleet_type_id' => 1, 'vehicle_route_id' => 1]);
        DB::table('ticket_price_by_stoppages')->insert([
            'ticket_price_id' => 1,
            'source_destination' => json_encode(['1', '3']),
            'price' => 755,
        ]);
        DB::table('counters')->insert(['id' => 3, 'name' => 'Destination']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_rebooking_target_allows_earlier_boarding_trip_for_availability_and_confirmation(): void
    {
        foreach ([false, true] as $lockForUpdate) {
            [$trip, $date] = $this->invoke('resolveRebookingTarget', [
                $this->ticket(),
                ['type' => 'new_trip', 'date' => '2026-11-28', 'trip_id' => 2],
                $lockForUpdate,
            ]);

            $this->assertSame(2, (int) $trip->id);
            $this->assertSame('2026-11-28', $date);
        }
    }

    public function test_transfer_target_allows_earlier_boarding_trip_for_availability_hold_and_confirmation(): void
    {
        foreach ([false, true] as $lockForUpdate) {
            [$trip, $drop, $date, $fare] = $this->invoke('resolveTransferTarget', [
                $this->ticket(),
                (new SlipSeriesNumber())->forceFill(['seat' => '1-D1']),
                ['date' => '2026-11-28', 'trip_id' => 2, 'dropping_point' => 3],
                $lockForUpdate,
            ]);

            $this->assertSame(2, (int) $trip->id);
            $this->assertSame(3, (int) $drop->id);
            $this->assertSame('2026-11-28', $date);
            $this->assertSame(755.0, $fare['fare']);
        }
    }

    public function test_admin_changes_are_allowed_inside_the_normal_booking_cutoff(): void
    {
        DB::table('schedules')->where('id', 2)->update(['start_from' => '17:45:00']);

        [$trip] = $this->invoke('resolveRebookingTarget', [
            $this->ticket(),
            ['type' => 'new_trip', 'date' => '2026-11-28', 'trip_id' => 2],
        ]);
        [$transferTrip] = $this->invoke('resolveTransferTarget', [
            $this->ticket(),
            (new SlipSeriesNumber())->forceFill(['seat' => '1-D1']),
            ['date' => '2026-11-28', 'trip_id' => 2, 'dropping_point' => 3],
        ]);

        $this->assertSame(2, (int) $trip->id);
        $this->assertSame(2, (int) $transferTrip->id);
    }

    public function test_seat_change_is_allowed_after_scheduled_time_while_boarding(): void
    {
        [$trip, $date] = $this->invoke('resolveRebookingTarget', [$this->ticket(), ['type' => 'change_seat']]);

        $this->assertSame(1, (int) $trip->id);
        $this->assertSame('2026-11-28', $date);
    }

    public function test_confirmation_rechecks_dispatch_status_after_the_seat_map_was_loaded(): void
    {
        $ticket = $this->ticket();
        $this->invoke('resolveRebookingTarget', [$ticket, ['type' => 'change_seat']]);
        DB::table('trips')->where('id', 1)->update(['trip_status' => Status::TRIP_DEPARTED]);

        $this->expectException(ValidationException::class);

        $this->invoke('resolveRebookingTarget', [$ticket, ['type' => 'change_seat'], true]);
    }

    public function test_day_off_still_rejects_post_departure_rebooking_and_transfer(): void
    {
        DB::table('trips')->where('id', 2)->update(['day_off' => json_encode([6])]);
        foreach (['resolveRebookingTarget', 'resolveTransferTarget'] as $method) {
            $arguments = $method === 'resolveRebookingTarget'
                ? [$this->ticket(), ['type' => 'new_trip', 'date' => '2026-11-28', 'trip_id' => 2]]
                : [$this->ticket(), (new SlipSeriesNumber())->forceFill(['seat' => '1-D1']),
                    ['date' => '2026-11-28', 'trip_id' => 2, 'dropping_point' => 3]];

            try {
                $this->invoke($method, $arguments);
                $this->fail('The day-off restriction must still apply.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('date', $exception->errors());
            }
        }
    }

    private function ticket(): BookedTicket
    {
        $ticket = (new BookedTicket())->forceFill([
            'trip_id' => 1,
            'status' => Status::BOOKED_APPROVED,
            'date_of_journey' => '2026-11-28',
            'pickup_point' => 1,
            'dropping_point' => 3,
            'unit_price' => 755,
            'passenger_manifest' => [[
                'seat' => '1-D1',
                'name' => 'Test Passenger',
                'passenger_type' => 'regular',
                'contact_number' => '09171234567',
            ]],
        ]);
        $ticket->setRelation('trip', Trip::with(['route', 'schedule', 'fleetType'])->findOrFail(1));

        return $ticket;
    }

    private function invoke(string $method, array $arguments): array
    {
        return (new ReflectionMethod(VehicleTicketController::class, $method))
            ->invokeArgs(app(VehicleTicketController::class), $arguments);
    }
}
