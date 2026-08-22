<?php

namespace Tests\Unit;

use App\Models\Trip;
use App\Models\TripChannelAvailability;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TripChannelAvailabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('trips', function (Blueprint $table) {
            $table->id();
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
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('performed_by_admin_id')->nullable();
            $table->unsignedBigInteger('authorized_by_admin_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_dated_channel_settings_override_defaults_only_for_the_selected_date(): void
    {
        $trip = Trip::query()->create([
            'online_booking_enabled' => true,
            'kiosk_booking_enabled' => false,
        ]);
        $trip->channelAvailabilities()->createMany([
            [
                'journey_date' => '2026-08-25',
                'channel' => TripChannelAvailability::ONLINE,
                'is_enabled' => false,
            ],
            [
                'journey_date' => '2026-08-25',
                'channel' => TripChannelAvailability::KIOSK,
                'is_enabled' => true,
            ],
        ]);

        $this->assertFalse($trip->bookingEnabledFor(null, '2026-08-25'));
        $this->assertTrue($trip->bookingEnabledFor(1, '2026-08-25'));
        $this->assertTrue($trip->bookingEnabledFor(null, '2026-08-26'));
        $this->assertFalse($trip->bookingEnabledFor(1, '2026-08-26'));
    }

    public function test_channel_scope_uses_dated_override_then_falls_back_to_trip_default(): void
    {
        $trip = Trip::query()->create([
            'online_booking_enabled' => true,
            'kiosk_booking_enabled' => false,
        ]);
        $trip->channelAvailabilities()->create([
            'journey_date' => '2026-08-25',
            'channel' => TripChannelAvailability::ONLINE,
            'is_enabled' => false,
        ]);

        $this->assertFalse(Trip::query()->forBookingChannel(null, '2026-08-25')->whereKey($trip->id)->exists());
        $this->assertTrue(Trip::query()->forBookingChannel(null, '2026-08-26')->whereKey($trip->id)->exists());
    }

    public function test_channel_scope_uses_today_when_the_trip_list_has_no_date_filter(): void
    {
        Carbon::setTestNow('2026-08-22 10:00:00');

        try {
            $trip = Trip::query()->create([
                'online_booking_enabled' => true,
                'kiosk_booking_enabled' => true,
            ]);
            $trip->channelAvailabilities()->create([
                'journey_date' => '2026-08-22',
                'channel' => TripChannelAvailability::KIOSK,
                'is_enabled' => false,
            ]);

            $this->assertFalse(
                Trip::query()->forBookingChannel(1)->whereKey($trip->id)->exists(),
                'A kiosk block for today must apply when the ticket filters are cleared.'
            );
            $this->assertTrue(
                Trip::query()->forBookingChannel(null)->whereKey($trip->id)->exists(),
                'The kiosk-only override must not block the online channel.'
            );
        } finally {
            Carbon::setTestNow();
        }
    }
}
