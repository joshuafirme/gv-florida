<?php

namespace Tests\Unit;

use App\Models\Counter;
use App\Models\Trip;
use App\Models\VehicleRoute;
use App\Services\TripProvinceFilterService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TripProvinceFilterServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('vehicle_routes', function (Blueprint $table) {
            $table->id();
            $table->json('stoppages');
            $table->timestamps();
        });
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_route_id');
            $table->unsignedBigInteger('start_from');
            $table->unsignedBigInteger('end_to');
            $table->timestamps();
        });

        foreach ([
            1 => ['Sampaloc', 'METRO MANILA'],
            2 => ['San Fernando', 'LA UNION'],
            3 => ['Cauayan', 'ISABELA'],
            4 => ['Aparri', 'CAGAYAN'],
        ] as $id => [$name, $location]) {
            Counter::create(compact('id', 'name', 'location'));
        }
    }

    public function test_it_matches_endpoint_and_intermediate_dropping_points_but_not_the_origin(): void
    {
        $trip = $this->trip(1, 4);
        $service = app(TripProvinceFilterService::class);

        $this->assertTrue($service->hasDroppingPoint($trip, [3], 1));
        $this->assertTrue($service->hasDroppingPoint($trip, [4], 1));
        $this->assertFalse($service->hasDroppingPoint($trip, [1], 1));
    }

    public function test_it_respects_reverse_direction_and_the_actual_trip_segment(): void
    {
        $service = app(TripProvinceFilterService::class);

        $this->assertTrue($service->hasDroppingPoint($this->trip(4, 1), [3], 4));
        $this->assertFalse($service->hasDroppingPoint($this->trip(1, 3), [4], 1));
    }

    public function test_query_filter_includes_trips_with_an_intermediate_stop_in_the_province(): void
    {
        $matchingTrip = $this->trip(1, 4);
        $nonMatchingTrip = $this->trip(1, 2);
        $query = Trip::query();

        app(TripProvinceFilterService::class)->apply($query, 'ISABELA', 1);

        $this->assertSame([$matchingTrip->id], $query->pluck('id')->all());
        $this->assertNotSame($matchingTrip->id, $nonMatchingTrip->id);
    }

    private function trip(int $start, int $end): Trip
    {
        $route = VehicleRoute::create(['stoppages' => ['1', '2', '3', '4']]);

        return Trip::create([
            'vehicle_route_id' => $route->id,
            'start_from' => $start,
            'end_to' => $end,
        ])->load('route');
    }
}
