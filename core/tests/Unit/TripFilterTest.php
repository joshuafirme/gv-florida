<?php

namespace Tests\Unit;

use App\Http\Controllers\SiteController;
use Illuminate\Http\Request;
use Tests\TestCase;

class TripFilterTest extends TestCase
{
    public function test_empty_bus_class_filter_does_not_filter_out_trips(): void
    {
        $this->setRequest(['fleetType' => ['']]);
        $query = new TripFilterQueryFake();

        (new SiteController())->filterTrip($query);

        $this->assertSame([], $query->integerFilters);
    }

    public function test_bus_class_filter_keeps_only_unique_positive_ids(): void
    {
        $this->setRequest(['fleetType' => ['', '2', 'invalid', '2', '3', '0']]);
        $query = new TripFilterQueryFake();

        (new SiteController())->filterTrip($query);

        $this->assertSame([
            ['fleet_type_id', [2, 3]],
        ], $query->integerFilters);
    }

    private function setRequest(array $query): void
    {
        $this->app->instance('request', Request::create('/tickets', 'GET', $query));
    }
}

class TripFilterQueryFake
{
    public array $integerFilters = [];

    public function whereIntegerInRaw(string $column, array $values): self
    {
        $this->integerFilters[] = [$column, $values];

        return $this;
    }

    public function with(array $relations): self
    {
        return $this;
    }

    public function where(string $column, mixed $value): self
    {
        return $this;
    }
}
