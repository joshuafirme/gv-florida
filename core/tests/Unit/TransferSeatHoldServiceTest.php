<?php

namespace Tests\Unit;

use App\Models\Admin;
use App\Models\SlipSeriesNumber;
use App\Models\TransferSeatHold;
use App\Models\Trip;
use App\Models\VehicleRoute;
use App\Services\ScheduleBoardBroadcaster;
use App\Services\SeatConflictService;
use App\Services\TransferSeatHoldService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransferSeatHoldServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('vehicle_routes', function (Blueprint $table) {
            $table->id();
            $table->json('stoppages');
            $table->json('route_stops')->nullable();
            $table->timestamps();
        });

        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_route_id');
            $table->unsignedBigInteger('start_from');
            $table->unsignedBigInteger('end_to');
            $table->boolean('online_booking_enabled')->default(true);
            $table->boolean('kiosk_booking_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('transfer_seat_holds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('slip_series_number_id');
            $table->unsignedBigInteger('booked_ticket_id');
            $table->unsignedBigInteger('trip_id');
            $table->date('date_of_journey');
            $table->unsignedBigInteger('pickup_point_id');
            $table->unsignedBigInteger('dropping_point_id');
            $table->string('seat_no', 30);
            $table->char('token_hash', 64)->unique();
            $table->unsignedBigInteger('held_by_admin_id')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_active_hold_blocks_only_an_overlapping_route_segment(): void
    {
        $trip = $this->trip();
        $token = str_repeat('a', 64);
        $this->hold($trip, $token, [2, 3]);
        $conflicts = app(SeatConflictService::class);

        $this->assertSame([], $conflicts->transferHeldSeats($trip, '2026-10-22', 1, 2));
        $this->assertSame(['D1'], $conflicts->transferHeldSeats($trip, '2026-10-22', 1, 3));
        $this->assertSame([], $conflicts->transferHeldSeats($trip, '2026-10-22', 1, 3, $token));
    }

    public function test_cancel_releases_the_hold_immediately(): void
    {
        $trip = $this->trip();
        $token = str_repeat('b', 64);
        $hold = $this->hold($trip, $token, [1, 3]);
        $this->mock(ScheduleBoardBroadcaster::class, function ($mock) use ($trip) {
            $mock->shouldReceive('passengerTransaction')
                ->once()
                ->with([
                    'trip_id' => $trip->id,
                    'date_of_journey' => '2026-10-22',
                    'seat' => 'D1',
                    'transfer_hold_action' => 'released',
                ]);
        });
        $service = app(TransferSeatHoldService::class);

        $released = $service->release($token, $this->admin(), $this->slip());

        $this->assertTrue($released);
        $this->assertNotNull($hold->fresh()->released_at);
        $this->assertSame([], app(SeatConflictService::class)->transferHeldSeats($trip, '2026-10-22', 1, 3));
    }

    public function test_expired_hold_cannot_be_used_to_confirm_a_transfer(): void
    {
        $trip = $this->trip();
        $token = str_repeat('c', 64);
        $this->hold($trip, $token, [1, 3], now()->subSecond());

        $this->expectException(ValidationException::class);

        app(TransferSeatHoldService::class)->lockForConfirmation(
            $token,
            $this->admin(),
            $this->slip(),
            $trip,
            '2026-10-22',
            1,
            3,
            'D1'
        );
    }

    private function trip(): Trip
    {
        $route = VehicleRoute::create([
            'stoppages' => ['1', '2', '3', '4'],
            'route_stops' => null,
        ]);

        return Trip::create([
            'vehicle_route_id' => $route->id,
            'start_from' => 1,
            'end_to' => 4,
        ])->load('route');
    }

    private function hold(Trip $trip, string $token, array $segment, $expiresAt = null): TransferSeatHold
    {
        return TransferSeatHold::create([
            'slip_series_number_id' => 10,
            'booked_ticket_id' => 20,
            'trip_id' => $trip->id,
            'date_of_journey' => '2026-10-22',
            'pickup_point_id' => $segment[0],
            'dropping_point_id' => $segment[1],
            'seat_no' => 'D1',
            'token_hash' => TransferSeatHoldService::tokenHash($token),
            'held_by_admin_id' => 30,
            'expires_at' => $expiresAt ?: now()->addMinutes(15),
        ]);
    }

    private function admin(): Admin
    {
        return (new Admin())->forceFill(['id' => 30]);
    }

    private function slip(): SlipSeriesNumber
    {
        return (new SlipSeriesNumber())->forceFill(['id' => 10]);
    }
}
