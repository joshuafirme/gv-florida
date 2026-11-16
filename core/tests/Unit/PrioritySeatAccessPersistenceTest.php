<?php

namespace Tests\Unit;

use App\Models\Admin;
use App\Models\CashierTransactionEvent;
use App\Models\FleetType;
use App\Models\Trip;
use App\Models\TripPrioritySeatAccess;
use App\Services\PrioritySeatAccessService;
use App\Services\ScheduleBoardBroadcaster;
use App\Services\TransactionAuthorizationService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PrioritySeatAccessPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->unsignedBigInteger('fleet_type_id');
            $table->unsignedBigInteger('vehicle_route_id')->nullable();
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->string('title')->nullable();
            $table->timestamps();
        });
        Schema::create('trip_priority_seat_accesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id');
            $table->date('journey_date');
            $table->boolean('online_enabled')->default(false);
            $table->boolean('walk_in_enabled')->default(true);
            $table->text('reason');
            $table->unsignedBigInteger('updated_by_admin_id')->nullable();
            $table->unsignedBigInteger('authorized_by_admin_id')->nullable();
            $table->timestamp('authorized_at');
            $table->timestamps();
            $table->unique(['trip_id', 'journey_date']);
        });
        Schema::create('cashier_transaction_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('event_key', 191)->unique();
            $table->string('status', 50);
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

    public function test_authorized_walk_in_access_is_saved_audited_and_broadcast(): void
    {
        $trip = $this->createTrip();
        $performedBy = (new Admin())->forceFill(['id' => 11, 'name' => 'Dispatcher']);
        $authorizedBy = (new Admin())->forceFill(['id' => 22, 'name' => 'Supervisor']);

        $authorization = $this->mock(TransactionAuthorizationService::class);
        $authorization->shouldReceive('authorize')
            ->once()
            ->with('valid-code', TransactionAuthorizationService::PRIORITY_SEAT_ACCESS, [
                'channel' => PrioritySeatAccessService::WALK_IN,
                'reason' => 'Priority passenger at terminal',
            ])
            ->andReturn($authorizedBy);

        $broadcaster = $this->mock(ScheduleBoardBroadcaster::class);
        $broadcaster->shouldReceive('passengerTransaction')
            ->once()
            ->with([
                'trip_id' => $trip->id,
                'date_of_journey' => '2026-11-15',
                'priority_seat_channel' => PrioritySeatAccessService::WALK_IN,
                'priority_seat_enabled' => true,
            ]);

        $record = app(PrioritySeatAccessService::class)->change(
            $trip,
            Carbon::parse('2026-11-15'),
            PrioritySeatAccessService::WALK_IN,
            true,
            'Priority passenger at terminal',
            'valid-code',
            $performedBy
        );

        $this->assertTrue($record->walk_in_enabled);
        $this->assertSame(11, $record->updated_by_admin_id);
        $this->assertSame(22, $record->authorized_by_admin_id);
        $this->assertSame(
            [],
            app(PrioritySeatAccessService::class)->restrictedSeatIds($trip, '2026-11-15', true)
        );
        $this->assertNotEmpty(
            app(PrioritySeatAccessService::class)->restrictedSeatIds($trip, '2026-11-15', false)
        );

        $event = CashierTransactionEvent::query()->sole();
        $this->assertSame('Walk-In Priority Seats Enabled', $event->status);
        $this->assertSame(TripPrioritySeatAccess::class, $record::class);
        $this->assertSame(PrioritySeatAccessService::WALK_IN, $event->snapshot['channel']);
        $this->assertSame(22, $event->snapshot['authorized_by_admin_id']);
    }

    public function test_authorized_online_access_preserves_default_walk_in_access(): void
    {
        $trip = $this->createTrip();
        $performedBy = (new Admin())->forceFill(['id' => 11, 'name' => 'Dispatcher']);
        $authorizedBy = (new Admin())->forceFill(['id' => 22, 'name' => 'Supervisor']);

        $authorization = $this->mock(TransactionAuthorizationService::class);
        $authorization->shouldReceive('authorize')
            ->once()
            ->with('valid-code', TransactionAuthorizationService::PRIORITY_SEAT_ACCESS, [
                'channel' => PrioritySeatAccessService::ONLINE,
                'reason' => 'Online priority seat release',
            ])
            ->andReturn($authorizedBy);

        $broadcaster = $this->mock(ScheduleBoardBroadcaster::class);
        $broadcaster->shouldReceive('passengerTransaction')
            ->once()
            ->with([
                'trip_id' => $trip->id,
                'date_of_journey' => '2026-11-15',
                'priority_seat_channel' => PrioritySeatAccessService::ONLINE,
                'priority_seat_enabled' => true,
            ]);

        $record = app(PrioritySeatAccessService::class)->change(
            $trip,
            Carbon::parse('2026-11-15'),
            PrioritySeatAccessService::ONLINE,
            true,
            'Online priority seat release',
            'valid-code',
            $performedBy
        );

        $this->assertTrue($record->online_enabled);
        $this->assertTrue($record->walk_in_enabled);
        $this->assertSame(
            [],
            app(PrioritySeatAccessService::class)->restrictedSeatIds($trip, '2026-11-15', false)
        );
        $this->assertSame(
            [],
            app(PrioritySeatAccessService::class)->restrictedSeatIds($trip, '2026-11-15', true)
        );

        $event = CashierTransactionEvent::query()->sole();
        $this->assertSame('Online Priority Seats Enabled', $event->status);
        $this->assertSame(PrioritySeatAccessService::ONLINE, $event->snapshot['channel']);
    }

    private function createTrip(): Trip
    {
        $fleetType = FleetType::query()->create([
            'name' => 'Executive Sleeper',
            'seat_layout' => '2x1',
            'deck_seats' => [9],
            'last_row' => [0],
            'prefixes' => ['D'],
            'disabled_seats' => [],
            'cr_override_seat' => false,
        ]);

        return Trip::query()->create([
            'fleet_type_id' => $fleetType->id,
            'title' => 'Sampaloc to Tuguegarao',
        ]);
    }
}
