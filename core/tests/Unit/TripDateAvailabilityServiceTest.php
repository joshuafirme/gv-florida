<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Services\TripDateAvailabilityService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TripDateAvailabilityServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->time('start_from');
            $table->time('end_at')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('vehicle_routes', function (Blueprint $table) {
            $table->id();
            $table->json('stoppages');
            $table->timestamps();
        });
        Schema::create('fleet_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('seat_layout');
            $table->json('deck_seats');
            $table->json('last_row')->nullable();
            $table->json('prefixes')->nullable();
            $table->json('disabled_seats')->nullable();
            $table->string('cr_position')->nullable();
            $table->unsignedInteger('cr_row')->nullable();
            $table->unsignedInteger('cr_row_covered')->nullable();
            $table->unsignedInteger('cr_column_covered')->nullable();
            $table->boolean('cr_override_seat')->default(false);
            $table->timestamps();
        });
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('vehicle_route_id');
            $table->unsignedBigInteger('fleet_type_id');
            $table->unsignedBigInteger('start_from');
            $table->unsignedBigInteger('end_to');
            $table->boolean('status')->default(true);
            $table->string('trip_status')->nullable();
            $table->json('day_off')->nullable();
            $table->boolean('online_booking_enabled')->default(true);
            $table->boolean('kiosk_booking_enabled')->default(true);
            $table->timestamps();
        });
        Schema::create('trip_channel_availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id');
            $table->date('journey_date');
            $table->string('channel');
            $table->boolean('is_enabled');
            $table->timestamps();
        });
        Schema::create('booked_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id');
            $table->date('date_of_journey');
            $table->unsignedBigInteger('pickup_point');
            $table->unsignedBigInteger('dropping_point');
            $table->json('seats');
            $table->unsignedInteger('status');
            $table->timestamps();
        });
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booked_ticket_id')->nullable();
            $table->unsignedInteger('status')->default(0);
            $table->dateTime('expiry_limit')->nullable();
            $table->dateTime('processing_lease_expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('admin_seat_locks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id');
            $table->date('date_of_journey');
            $table->string('seat_no');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function test_it_distinguishes_available_full_closed_and_unscheduled_dates(): void
    {
        Carbon::setTestNow('2026-10-21 09:00:00');

        try {
            DB::table('schedules')->insert([
                'id' => 1,
                'start_from' => '23:00:00',
                'end_at' => '23:30:00',
                'status' => 1,
            ]);
            DB::table('vehicle_routes')->insert([
                'id' => 1,
                'stoppages' => json_encode(['1', '2']),
            ]);
            DB::table('fleet_types')->insert([
                'id' => 1,
                'name' => 'Test Bus',
                'seat_layout' => '1x1',
                'deck_seats' => json_encode([2]),
                'last_row' => json_encode([0]),
                'prefixes' => json_encode(['']),
                'disabled_seats' => json_encode([]),
                'cr_override_seat' => 0,
            ]);
            DB::table('trips')->insert([
                'id' => 1,
                'schedule_id' => 1,
                'vehicle_route_id' => 1,
                'fleet_type_id' => 1,
                'start_from' => 1,
                'end_to' => 2,
                'status' => 1,
                'trip_status' => Status::TRIP_ON_TIME,
                'day_off' => json_encode(['6']),
                'online_booking_enabled' => 1,
                'kiosk_booking_enabled' => 1,
            ]);
            DB::table('booked_tickets')->insert([
                'trip_id' => 1,
                'date_of_journey' => '2026-10-22',
                'pickup_point' => 1,
                'dropping_point' => 2,
                'seats' => json_encode(['1-1', '1-2']),
                'status' => Status::BOOKED_APPROVED,
            ]);
            DB::table('trip_channel_availabilities')->insert([
                'trip_id' => 1,
                'journey_date' => '2026-10-23',
                'channel' => 'online',
                'is_enabled' => 0,
            ]);

            $statuses = app(TripDateAvailabilityService::class)->statuses(
                null,
                [],
                1,
                2,
                3
            );

            $this->assertSame(TripDateAvailabilityService::AVAILABLE, $statuses['2026-10-21']);
            $this->assertSame(TripDateAvailabilityService::FULLY_BOOKED, $statuses['2026-10-22']);
            $this->assertSame(TripDateAvailabilityService::NOT_OPEN, $statuses['2026-10-23']);
            $this->assertSame(TripDateAvailabilityService::NO_TRIP, $statuses['2026-10-24']);
        } finally {
            Carbon::setTestNow();
        }
    }
}
