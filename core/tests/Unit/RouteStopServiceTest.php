<?php

namespace Tests\Unit;

use App\Services\RouteStopService;
use PHPUnit\Framework\TestCase;

class RouteStopServiceTest extends TestCase
{
    public function test_it_keeps_duplicate_locations_with_independent_km_posts(): void
    {
        $service = new RouteStopService();

        $stops = $service->normalize([
            ['counter_id' => 1, 'km_post' => 0],
            ['counter_id' => 8, 'km_post' => 150],
            ['counter_id' => 8, 'km_post' => 152],
            ['counter_id' => 12, 'km_post' => 210],
        ]);

        $this->assertSame(['1', '8', '8', '12'], $service->stoppageIds($stops));
        $this->assertSame(150, $stops[1]['km_post']);
        $this->assertSame(152, $stops[2]['km_post']);
        $this->assertSame([1, 2, 3, 4], array_column($stops, 'sequence'));
    }

    public function test_distance_follows_the_ordered_route_instead_of_only_the_endpoints(): void
    {
        $service = new RouteStopService();
        $stops = $service->normalize([
            ['counter_id' => 1, 'km_post' => 10],
            ['counter_id' => 2, 'km_post' => 45],
            ['counter_id' => 3, 'km_post' => 30],
            ['counter_id' => 4, 'km_post' => 80.5],
        ]);

        $this->assertSame(100.5, $service->distance($stops));
        $this->assertSame('100.5 km', $service->distanceLabel($stops));
    }
}
