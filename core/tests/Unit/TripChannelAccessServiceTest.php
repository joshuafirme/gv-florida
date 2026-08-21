<?php

namespace Tests\Unit;

use App\Models\Admin;
use App\Models\CashierTransactionEvent;
use App\Models\Trip;
use App\Models\TripChannelAvailability;
use App\Services\TransactionAuthorizationService;
use App\Services\TripChannelAccessService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TripChannelAccessServiceTest extends TestCase
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
            $table->string('channel', 20);
            $table->boolean('is_enabled');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('performed_by_admin_id')->nullable();
            $table->unsignedBigInteger('authorized_by_admin_id')->nullable();
            $table->timestamps();
            $table->unique(['trip_id', 'journey_date', 'channel']);
        });

        Schema::create('cashier_transaction_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('event_key', 191)->unique();
            $table->string('status', 30);
            $table->timestamp('processed_at');
            $table->string('source', 30)->nullable();
            $table->date('journey_date')->nullable();
            $table->time('departure_time')->nullable();
            $table->string('trip_class')->nullable();
            $table->string('trip_route')->nullable();
            $table->decimal('base_fare', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('surcharge_amount', 14, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->text('reason')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_saves_dated_channel_overrides_and_records_the_authorizer(): void
    {
        $trip = Trip::query()->create([
            'online_booking_enabled' => true,
            'kiosk_booking_enabled' => true,
        ]);
        $trip->setRelation('route', null);
        $trip->setRelation('schedule', null);
        $trip->setRelation('fleetType', null);

        $performedBy = (new Admin())->forceFill(['id' => 11, 'name' => 'Dispatcher']);
        $authorizedBy = (new Admin())->forceFill(['id' => 22, 'name' => 'Supervisor']);

        $authorization = $this->mock(TransactionAuthorizationService::class);
        $authorization->shouldReceive('authorize')
            ->once()
            ->with('valid-code', TransactionAuthorizationService::CHANNEL_ACCESS, ['reason' => 'Online maintenance'])
            ->andReturn($authorizedBy);

        $records = app(TripChannelAccessService::class)->apply(
            $trip,
            collect([Carbon::parse('2026-08-25'), Carbon::parse('2026-08-26')]),
            [
                TripChannelAvailability::ONLINE => false,
                TripChannelAvailability::KIOSK => true,
            ],
            'Online maintenance',
            'valid-code',
            $performedBy
        );

        $this->assertCount(4, $records);
        $onlineBlock = TripChannelAvailability::query()
            ->where('trip_id', $trip->id)
            ->whereDate('journey_date', '2026-08-25')
            ->where('channel', TripChannelAvailability::ONLINE)
            ->sole();
        $this->assertFalse($onlineBlock->is_enabled);
        $this->assertSame(11, $onlineBlock->performed_by_admin_id);
        $this->assertSame(22, $onlineBlock->authorized_by_admin_id);

        $kioskOverride = TripChannelAvailability::query()
            ->where('trip_id', $trip->id)
            ->whereDate('journey_date', '2026-08-26')
            ->where('channel', TripChannelAvailability::KIOSK)
            ->sole();
        $this->assertTrue($kioskOverride->is_enabled);

        $event = CashierTransactionEvent::query()->sole();
        $this->assertSame('Channel Access Updated', $event->status);
        $this->assertSame(22, $event->snapshot['authorized_by_admin_id']);
        $this->assertSame(['2026-08-25', '2026-08-26'], $event->snapshot['dates']);
    }

    public function test_a_dated_block_overrides_an_enabled_trip_default(): void
    {
        $trip = Trip::query()->create([
            'online_booking_enabled' => true,
            'kiosk_booking_enabled' => true,
        ]);
        $trip->channelAvailabilities()->create([
            'journey_date' => '2026-08-25',
            'channel' => TripChannelAvailability::ONLINE,
            'is_enabled' => false,
        ]);

        $this->assertFalse($trip->bookingEnabledFor(null, '2026-08-25'));
        $this->assertFalse(
            Trip::query()->forBookingChannel(null, '2026-08-25')->whereKey($trip->id)->exists()
        );
        $this->assertTrue($trip->bookingEnabledFor(null, '2026-08-26'));
    }

    public function test_it_removes_a_dated_override_and_records_the_authorized_action(): void
    {
        $trip = Trip::query()->create([
            'online_booking_enabled' => true,
            'kiosk_booking_enabled' => true,
        ]);
        $trip->setRelation('route', null);
        $trip->setRelation('schedule', null);
        $trip->setRelation('fleetType', null);
        $availability = $trip->channelAvailabilities()->create([
            'journey_date' => '2026-08-27',
            'channel' => TripChannelAvailability::ONLINE,
            'is_enabled' => false,
        ]);

        $performedBy = (new Admin())->forceFill(['id' => 11, 'name' => 'Dispatcher']);
        $authorizedBy = (new Admin())->forceFill(['id' => 22, 'name' => 'Supervisor']);
        $change = 'Removed Online Blocked override for 2026-08-27';

        $authorization = $this->mock(TransactionAuthorizationService::class);
        $authorization->shouldReceive('authorize')
            ->once()
            ->with('valid-code', TransactionAuthorizationService::CHANNEL_ACCESS, ['reason' => $change])
            ->andReturn($authorizedBy);

        app(TripChannelAccessService::class)->remove(
            $trip,
            $availability,
            'valid-code',
            $performedBy
        );

        $this->assertDatabaseMissing('trip_channel_availabilities', ['id' => $availability->id]);
        $event = CashierTransactionEvent::query()->sole();
        $this->assertSame($change, $event->reason);
        $this->assertSame([$change], $event->snapshot['changes']);
        $this->assertSame(22, $event->snapshot['authorized_by_admin_id']);
    }
}
