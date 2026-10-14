<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Models\TicketPrice;
use App\Models\TicketPriceByStoppage;
use App\Models\Trip;
use App\Models\VehicleRoute;
use App\Services\RouteStopService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RouteStopSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('vehicle_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('start_from');
            $table->unsignedBigInteger('end_to');
            $table->json('stoppages');
            $table->json('route_stops')->nullable();
            $table->string('distance')->nullable();
            $table->string('time')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });

        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_route_id');
            $table->unsignedBigInteger('start_from');
            $table->unsignedBigInteger('end_to');
            $table->timestamps();
        });

        Schema::create('booked_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id');
            $table->date('date_of_journey');
            $table->unsignedBigInteger('pickup_point');
            $table->unsignedBigInteger('dropping_point');
            $table->unsignedTinyInteger('status')->default(1);
            $table->string('pnr_number')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_route_id');
            $table->unsignedBigInteger('fleet_type_id')->default(1);
            $table->decimal('price', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('ticket_price_by_stoppages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_price_id');
            $table->json('source_destination');
            $table->decimal('price', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function test_route_update_syncs_full_route_trip_endpoints_and_prunes_removed_fares(): void
    {
        $route = VehicleRoute::create([
            'name' => 'Old Route',
            'start_from' => 1,
            'end_to' => 3,
            'stoppages' => ['1', '2', '3'],
            'route_stops' => [
                ['counter_id' => '1', 'km_post' => 0, 'sequence' => 1],
                ['counter_id' => '2', 'km_post' => 50, 'sequence' => 2],
                ['counter_id' => '3', 'km_post' => 100, 'sequence' => 3],
            ],
            'distance' => '100 km',
            'time' => '2 hrs',
        ]);
        $trip = Trip::create([
            'vehicle_route_id' => $route->id,
            'start_from' => 1,
            'end_to' => 3,
        ]);
        $ticketPrice = TicketPrice::create([
            'vehicle_route_id' => $route->id,
            'fleet_type_id' => 1,
            'price' => 500,
        ]);
        TicketPriceByStoppage::create([
            'ticket_price_id' => $ticketPrice->id,
            'source_destination' => ['1', '2'],
            'price' => 250,
        ]);
        TicketPriceByStoppage::create([
            'ticket_price_id' => $ticketPrice->id,
            'source_destination' => ['1', '3'],
            'price' => 500,
        ]);

        $service = app(RouteStopService::class);
        $stops = $service->normalize([
            ['counter_id' => 4, 'km_post' => 10],
            ['counter_id' => 3, 'km_post' => 100],
        ]);
        $removed = $service->update($route, ['name' => 'New Route', 'time' => '2 hrs'], $stops);

        $this->assertSame(2, $removed);
        $this->assertSame(['4', '3'], $route->fresh()->stoppages);
        $this->assertSame('90 km', $route->fresh()->distance);
        $this->assertSame(4, (int) $trip->fresh()->start_from);
        $this->assertSame(3, (int) $trip->fresh()->end_to);
        $this->assertSame(0, TicketPriceByStoppage::query()->count());
    }

    public function test_route_update_cannot_remove_a_stop_used_by_a_future_booking(): void
    {
        $route = VehicleRoute::create([
            'name' => 'Protected Route',
            'start_from' => 1,
            'end_to' => 3,
            'stoppages' => ['1', '2', '3'],
            'route_stops' => [
                ['counter_id' => '1', 'km_post' => 0, 'sequence' => 1],
                ['counter_id' => '2', 'km_post' => 50, 'sequence' => 2],
                ['counter_id' => '3', 'km_post' => 100, 'sequence' => 3],
            ],
            'distance' => '100 km',
            'time' => '2 hrs',
        ]);
        $trip = Trip::create([
            'vehicle_route_id' => $route->id,
            'start_from' => 1,
            'end_to' => 3,
        ]);

        DB::table('booked_tickets')->insert([
            'trip_id' => $trip->id,
            'date_of_journey' => today()->addDay()->format('Y-m-d'),
            'pickup_point' => 2,
            'dropping_point' => 3,
            'status' => Status::BOOKED_APPROVED,
            'pnr_number' => 'ROUTE-SAFETY-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(RouteStopService::class);
        $stops = $service->normalize([
            ['counter_id' => 1, 'km_post' => 0],
            ['counter_id' => 3, 'km_post' => 100],
        ]);

        $this->expectException(ValidationException::class);
        $service->update($route, ['name' => $route->name, 'time' => $route->time], $stops);
    }
}
