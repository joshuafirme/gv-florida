<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Models\TicketPrice;
use App\Models\TicketPriceByStoppage;
use App\Models\Trip;
use App\Models\VehicleRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RouteStopService
{
    public function normalize(array $stops): array
    {
        return collect(array_values($stops))
            ->map(fn ($stop, $index) => [
                'counter_id' => (string) $stop['counter_id'],
                'km_post' => $this->numericValue($stop['km_post']),
                'sequence' => $index + 1,
            ])
            ->all();
    }

    public function stoppageIds(array $stops): array
    {
        return collect($stops)
            ->pluck('counter_id')
            ->map(fn ($counterId) => (string) $counterId)
            ->values()
            ->all();
    }

    public function distance(array $stops): float
    {
        return collect($stops)
            ->pluck('km_post')
            ->map(fn ($kmPost) => (float) $kmPost)
            ->sliding(2)
            ->sum(fn ($pair) => abs($pair->last() - $pair->first()));
    }

    public function distanceLabel(array $stops): string
    {
        $distance = $this->distance($stops);
        $formatted = fmod($distance, 1.0) === 0.0
            ? number_format($distance, 0, '.', '')
            : rtrim(rtrim(number_format($distance, 2, '.', ''), '0'), '.');

        return $formatted . ' km';
    }

    public function update(VehicleRoute $route, array $attributes, array $stops): int
    {
        $oldIds = array_map('strval', array_values($route->stoppages ?: []));
        $newIds = $this->stoppageIds($stops);

        $this->assertRelatedTripsRemainValid($route, $oldIds, $newIds);
        $this->assertFutureBookingsRemainValid($route, $newIds);

        return DB::transaction(function () use ($route, $attributes, $stops, $oldIds, $newIds) {
            $route->fill($attributes);
            $route->start_from = $newIds[0];
            $route->end_to = $newIds[count($newIds) - 1];
            $route->stoppages = $newIds;
            $route->route_stops = $stops;
            $route->distance = $this->distanceLabel($stops);
            $route->save();

            $this->syncFullRouteTripEndpoints($route, $oldIds, $newIds);

            return $this->pruneObsoleteFares($route, $newIds);
        });
    }

    protected function assertRelatedTripsRemainValid(VehicleRoute $route, array $oldIds, array $newIds): void
    {
        if (!$route->exists || !$oldIds) {
            return;
        }

        $oldOrigin = $oldIds[0];
        $oldDestination = $oldIds[count($oldIds) - 1];
        $allowedIds = array_values(array_unique($newIds));

        $invalidTrip = Trip::query()
            ->where('vehicle_route_id', $route->id)
            ->where(function ($query) use ($allowedIds, $oldOrigin, $oldDestination) {
                $query->where(function ($start) use ($allowedIds, $oldOrigin) {
                    $start->where('start_from', '!=', $oldOrigin)
                        ->whereNotIn('start_from', $allowedIds);
                })->orWhere(function ($end) use ($allowedIds, $oldDestination) {
                    $end->where('end_to', '!=', $oldDestination)
                        ->whereNotIn('end_to', $allowedIds);
                });
            })
            ->first();

        if ($invalidTrip) {
            throw ValidationException::withMessages([
                'route_stops' => 'A removed location is still used as an endpoint by trip #' . $invalidTrip->id . '. Update that trip before removing the location.',
            ]);
        }
    }

    protected function assertFutureBookingsRemainValid(VehicleRoute $route, array $newIds): void
    {
        if (!$route->exists) {
            return;
        }

        $allowedIds = array_values(array_unique($newIds));
        $booking = BookedTicket::query()
            ->whereHas('trip', fn ($trip) => $trip->where('vehicle_route_id', $route->id))
            ->whereDate('date_of_journey', '>=', today())
            ->whereIn('status', [Status::BOOKED_APPROVED, Status::BOOKED_PENDING])
            ->where(function ($query) use ($allowedIds) {
                $query->whereNotIn('pickup_point', $allowedIds)
                    ->orWhereNotIn('dropping_point', $allowedIds);
            })
            ->first();

        if ($booking) {
            throw ValidationException::withMessages([
                'route_stops' => 'A removed location is used by future booking ' . ($booking->pnr_number ?: '#' . $booking->id) . '. Rebook or transfer that ticket before changing the route.',
            ]);
        }
    }

    protected function syncFullRouteTripEndpoints(VehicleRoute $route, array $oldIds, array $newIds): void
    {
        if (!$oldIds) {
            return;
        }

        $oldOrigin = $oldIds[0];
        $oldDestination = $oldIds[count($oldIds) - 1];
        $newOrigin = $newIds[0];
        $newDestination = $newIds[count($newIds) - 1];

        Trip::query()
            ->where('vehicle_route_id', $route->id)
            ->where('start_from', $oldOrigin)
            ->update(['start_from' => $newOrigin]);

        Trip::query()
            ->where('vehicle_route_id', $route->id)
            ->where('end_to', $oldDestination)
            ->update(['end_to' => $newDestination]);
    }

    protected function pruneObsoleteFares(VehicleRoute $route, array $newIds): int
    {
        $validIds = array_values(array_unique(array_map('strval', $newIds)));
        $ticketPriceIds = TicketPrice::query()
            ->where('vehicle_route_id', $route->id)
            ->pluck('id');

        if ($ticketPriceIds->isEmpty()) {
            return 0;
        }

        $obsoleteIds = TicketPriceByStoppage::query()
            ->whereIn('ticket_price_id', $ticketPriceIds)
            ->get(['id', 'source_destination'])
            ->filter(function ($fare) use ($validIds) {
                $segment = array_map('strval', $fare->source_destination ?: []);

                return count($segment) !== 2
                    || !in_array($segment[0], $validIds, true)
                    || !in_array($segment[1], $validIds, true);
            })
            ->pluck('id');

        if ($obsoleteIds->isNotEmpty()) {
            TicketPriceByStoppage::query()->whereIn('id', $obsoleteIds)->delete();
        }

        return $obsoleteIds->count();
    }

    protected function numericValue(mixed $value): int|float
    {
        $number = (float) $value;

        return fmod($number, 1.0) === 0.0 ? (int) $number : $number;
    }
}
