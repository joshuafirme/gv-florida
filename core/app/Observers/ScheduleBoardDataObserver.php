<?php

namespace App\Observers;

use App\Models\AssignedVehicle;
use App\Models\Counter;
use App\Models\FleetType;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\VehicleRoute;
use App\Services\ScheduleBoardBroadcaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ScheduleBoardDataObserver
{
    private const WATCHED_ATTRIBUTES = [
        Trip::class => ['schedule_id', 'vehicle_route_id', 'fleet_type_id', 'start_from', 'end_to'],
        Schedule::class => ['start_from'],
        VehicleRoute::class => ['end_to'],
        AssignedVehicle::class => ['vehicle_id'],
        Vehicle::class => ['bus_no'],
        FleetType::class => ['name'],
        Counter::class => ['name', 'city'],
    ];

    public function saved(Model $model): void
    {
        $attributes = self::WATCHED_ATTRIBUTES[$model::class] ?? [];

        if (!$attributes || (!$model->wasRecentlyCreated && !$model->wasChanged($attributes))) {
            return;
        }

        $this->broadcast($model, $model->wasRecentlyCreated ? 'created' : 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->broadcast($model, 'deleted');
    }

    private function broadcast(Model $model, string $action): void
    {
        $payload = [
            'source' => class_basename($model),
            'source_id' => $model->getKey(),
            'action' => $action,
        ];

        $callback = static function () use ($payload): void {
            app(ScheduleBoardBroadcaster::class)->scheduleDataUpdated($payload);
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($callback);
            return;
        }

        $callback();
    }
}
