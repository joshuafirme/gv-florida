<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Builder;

class TripProvinceFilterService
{
    public function apply(Builder $query, ?string $province, int|string|null $pickup = null): Builder
    {
        $province = trim((string) $province);
        if ($province === '') {
            return $query;
        }

        $counterIds = Counter::active()
            ->where('location', $province)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (!$counterIds) {
            return $query->whereIntegerInRaw('id', [0]);
        }

        $tripIds = (clone $query)
            ->with('route:id,stoppages')
            ->get(['id', 'vehicle_route_id', 'start_from', 'end_to'])
            ->filter(fn (Trip $trip) => $this->hasDroppingPoint($trip, $counterIds, $pickup))
            ->pluck('id')
            ->all();

        return $query->whereIntegerInRaw('id', $tripIds ?: [0]);
    }

    public function hasDroppingPoint(Trip $trip, array $provinceCounterIds, int|string|null $pickup = null): bool
    {
        $stops = array_values(array_map('strval', (array) ($trip->route?->stoppages ?? [])));
        $start = array_search((string) $trip->start_from, $stops, true);
        $end = array_search((string) $trip->end_to, $stops, true);

        if ($start === false || $end === false || $start === $end) {
            return false;
        }

        $tripStops = $start < $end
            ? array_slice($stops, $start, $end - $start + 1)
            : array_reverse(array_slice($stops, $end, $start - $end + 1));
        $pickupIndex = $pickup === null || $pickup === ''
            ? 0
            : array_search((string) $pickup, $tripStops, true);

        if ($pickupIndex === false || $pickupIndex >= count($tripStops) - 1) {
            return false;
        }

        $droppingPointIds = array_slice($tripStops, $pickupIndex + 1);
        $provinceCounterIds = array_values(array_unique(array_map('strval', $provinceCounterIds)));

        return (bool) array_intersect($droppingPointIds, $provinceCounterIds);
    }
}
