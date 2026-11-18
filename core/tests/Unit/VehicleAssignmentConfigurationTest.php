<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Http\Controllers\Admin\ManageTripController;
use ReflectionMethod;
use Tests\TestCase;

class VehicleAssignmentConfigurationTest extends TestCase
{
    public function test_dispatch_statuses_are_available_in_the_required_order(): void
    {
        $method = new ReflectionMethod(ManageTripController::class, 'vehicleDispatchStatuses');
        $statuses = $method->invoke(app(ManageTripController::class));

        $this->assertSame([
            Status::TRIP_ON_TIME => 'Scheduled',
            Status::TRIP_PREPARING => 'Preparing',
            Status::TRIP_BOARDING => 'Boarding',
            Status::TRIP_DEPARTED => 'Departed',
            Status::TRIP_CANCELLED => 'Cancelled',
            Status::TRIP_BREAKDOWN => 'Breakdown',
        ], $statuses);
    }

    public function test_assignment_accepts_active_vehicles_from_any_bus_class(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Admin/ManageTripController.php'));
        $view = file_get_contents(resource_path('views/admin/trip/assigned_vehicle.blade.php'));

        $this->assertStringContainsString('Vehicle::query()', $controller);
        $this->assertStringContainsString("->active()", $controller);
        $this->assertStringNotContainsString("->where('fleet_type_id', \$trip->fleet_type_id)", $controller);
        $this->assertStringContainsString("'vehicle_id' => 'Select an active vehicle.'", $controller);
        $this->assertStringContainsString('vehicleSelect.select2({', $view);
        $this->assertStringContainsString("minimumResultsForSearch: 0", $view);
        $this->assertStringContainsString('$vehicle->fleetType?->name', $view);
    }
}
