<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\AdminSeatLock;
use App\Models\BookedTicket;
use App\Models\Trip;
use App\Models\TripChannelAvailability;
use App\Support\BusinessTime;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TripDateAvailabilityService
{
    public const AVAILABLE = 'available';
    public const FULLY_BOOKED = 'fully_booked';
    public const NOT_OPEN = 'not_open';
    public const NO_TRIP = 'no_trip';

    public function __construct(
        private readonly SeatConflictService $seatConflictService,
        private readonly SeatLayoutService $seatLayoutService
    ) {
    }

    public function statuses(
        ?int $kioskId,
        array $kioskTripIds,
        int|string|null $pickup,
        int|string|null $destination,
        int $allowedDays,
        array $fleetTypeIds = [],
        ?string $province = null
    ): array {
        $today = BusinessTime::today();
        $lastDate = $today->copy()->addDays($allowedDays);
        $query = Trip::query()
            ->with([
                'route',
                'schedule',
                'fleetType',
                'channelAvailabilities' => fn ($availability) => $availability
                    ->whereBetween('journey_date', [$today->format('Y-m-d'), $lastDate->format('Y-m-d')]),
            ])
            ->whereHas('schedule');

        if ($kioskId) {
            $query->whereIntegerInRaw('id', $kioskTripIds ?: [0]);
        }
        if ($pickup) {
            $query->where('start_from', (int) $pickup);
        }
        if ($fleetTypeIds) {
            $query->whereIn('fleet_type_id', $fleetTypeIds);
        }
        if ($province) {
            $query->whereHas('endTo', fn ($counter) => $counter->where('location', $province));
        }

        $trips = $query->get()
            ->filter(fn (Trip $trip) => $this->tripMatchesJourney($trip, $pickup, $destination))
            ->values();
        [$bookings, $seatLocks] = $this->seatOccupancy($trips, $today, $lastDate);
        $cutoffMinutes = getBookingCutoffMinutes($kioskId);

        return collect(range(0, $allowedDays))
            ->mapWithKeys(function (int $offset) use (
                $today,
                $trips,
                $bookings,
                $seatLocks,
                $kioskId,
                $pickup,
                $destination,
                $cutoffMinutes
            ) {
                $date = $today->copy()->addDays($offset);
                $dateKey = $date->format('Y-m-d');
                $scheduledTrips = $trips->reject(fn (Trip $trip) => $this->isDayOff($trip, $date));

                if ($scheduledTrips->isEmpty()) {
                    return [$dateKey => self::NO_TRIP];
                }

                $openTrips = $scheduledTrips->filter(
                    fn (Trip $trip) => $this->isOpenForBooking($trip, $date, $kioskId, $cutoffMinutes)
                );

                if ($openTrips->isEmpty()) {
                    return [$dateKey => self::NOT_OPEN];
                }

                $hasAvailableSeat = $openTrips->contains(function (Trip $trip) use (
                    $dateKey,
                    $bookings,
                    $seatLocks,
                    $pickup,
                    $destination
                ) {
                    $occupancyKey = $trip->id . ':' . $dateKey;
                    $tripBookings = $bookings->get($occupancyKey, collect());
                    $bookedSeats = $tripBookings
                        ->filter(fn (BookedTicket $booking) => $this->seatConflictService->segmentsOverlap(
                            $trip,
                            $pickup ?: $trip->start_from,
                            $destination ?: $trip->end_to,
                            $booking->pickup_point,
                            $booking->dropping_point
                        ))
                        ->flatMap(fn (BookedTicket $booking) => $this->seatConflictService->normalizeSeats($booking->seats))
                        ->merge($seatLocks->get($occupancyKey, collect())->pluck('seat_no'))
                        ->unique()
                        ->values()
                        ->all();

                    return $trip->fleetType
                        && $this->seatLayoutService->availableSeatCount(
                            $trip->fleetType,
                            ['booked' => $bookedSeats]
                        ) > 0;
                });

                return [$dateKey => $hasAvailableSeat ? self::AVAILABLE : self::FULLY_BOOKED];
            })
            ->all();
    }

    public function tripStatus(
        Trip $trip,
        Carbon|string $journeyDate,
        ?int $kioskId,
        int|string|null $pickup = null,
        int|string|null $destination = null
    ): string {
        try {
            $date = BusinessTime::parse($journeyDate)->startOfDay();
        } catch (\Throwable $exception) {
            return self::NOT_OPEN;
        }
        $trip->loadMissing(['route', 'schedule', 'fleetType', 'channelAvailabilities']);
        $today = BusinessTime::today();

        if ($date->lt($today) || $date->gt($today->copy()->addDays(getAllowedAdvanceBookingDays($kioskId)))) {
            return self::NOT_OPEN;
        }

        if ($this->isDayOff($trip, $date)) {
            return self::NO_TRIP;
        }

        if (!$this->isOpenForBooking($trip, $date, $kioskId, getBookingCutoffMinutes($kioskId))) {
            return self::NOT_OPEN;
        }

        $unavailableSeats = $this->seatConflictService->unavailableSeats(
            $trip,
            $date,
            $pickup ?: $trip->start_from,
            $destination ?: $trip->end_to
        );

        return $trip->fleetType && $this->seatLayoutService->availableSeatCount(
            $trip->fleetType,
            ['booked' => $unavailableSeats]
        ) > 0
            ? self::AVAILABLE
            : self::FULLY_BOOKED;
    }

    public function message(string $status): string
    {
        return match ($status) {
            self::FULLY_BOOKED => 'All seats for this trip and travel date are already booked.',
            self::NOT_OPEN => 'This trip is closed or not open for booking through this channel.',
            self::NO_TRIP => 'This trip is not scheduled for the selected travel date.',
            default => '',
        };
    }

    private function seatOccupancy(Collection $trips, Carbon $firstDate, Carbon $lastDate): array
    {
        $tripIds = $trips->pluck('id')->filter()->values();

        if ($tripIds->isEmpty()) {
            return [collect(), collect()];
        }

        $bookings = BookedTicket::query()
            ->whereIntegerInRaw('trip_id', $tripIds->all())
            ->whereBetween('date_of_journey', [$firstDate->format('Y-m-d'), $lastDate->format('Y-m-d')])
            ->holdingSeats()
            ->get(['id', 'trip_id', 'date_of_journey', 'pickup_point', 'dropping_point', 'seats'])
            ->groupBy(fn (BookedTicket $booking) => $booking->trip_id . ':' . BusinessTime::parse(
                $booking->date_of_journey
            )->format('Y-m-d'));
        $seatLocks = AdminSeatLock::query()
            ->active()
            ->whereIntegerInRaw('trip_id', $tripIds->all())
            ->whereBetween('date_of_journey', [$firstDate->format('Y-m-d'), $lastDate->format('Y-m-d')])
            ->get(['trip_id', 'date_of_journey', 'seat_no'])
            ->groupBy(fn (AdminSeatLock $lock) => $lock->trip_id . ':' . $lock->date_of_journey->format('Y-m-d'));

        return [$bookings, $seatLocks];
    }

    private function isOpenForBooking(Trip $trip, Carbon $date, ?int $kioskId, int $cutoffMinutes): bool
    {
        if ((int) $trip->status !== Status::ENABLE
            || !$trip->schedule
            || !$trip->schedule->start_from
            || (int) ($trip->schedule->status ?? Status::ENABLE) !== Status::ENABLE
            || in_array($trip->trip_status, [
                Status::TRIP_CANCELLED,
                Status::TRIP_DEPARTED,
                Status::TRIP_ARRIVED,
            ], true)) {
            return false;
        }

        $channel = $kioskId ? TripChannelAvailability::KIOSK : TripChannelAvailability::ONLINE;
        $defaultColumn = $kioskId ? 'kiosk_booking_enabled' : 'online_booking_enabled';
        $dateKey = $date->format('Y-m-d');
        $override = $trip->channelAvailabilities->first(
            fn (TripChannelAvailability $availability) => $availability->channel === $channel
                && $availability->journey_date?->format('Y-m-d') === $dateKey
        );

        if (!($override ? (bool) $override->is_enabled : (bool) $trip->{$defaultColumn})) {
            return false;
        }

        if ($date->isToday()) {
            $departure = BusinessTime::parse($dateKey . ' ' . $trip->schedule->start_from);

            return $departure->gt(BusinessTime::now()->addMinutes($cutoffMinutes));
        }

        return true;
    }

    private function isDayOff(Trip $trip, Carbon $date): bool
    {
        return collect($trip->day_off ?: [])
            ->map(fn ($day) => (string) $day)
            ->contains((string) $date->dayOfWeek);
    }

    private function tripMatchesJourney(Trip $trip, int|string|null $pickup, int|string|null $destination): bool
    {
        if (!$destination) {
            return true;
        }

        $stoppages = array_values(array_map('strval', (array) ($trip->route?->stoppages ?? [])));
        $destinationId = (string) $destination;

        if (!$pickup) {
            return in_array($destinationId, $stoppages, true);
        }

        $orderedStops = $stoppages;
        $tripStart = array_search((string) $trip->start_from, $orderedStops, true);
        $tripEnd = array_search((string) $trip->end_to, $orderedStops, true);
        if ($tripStart === false || $tripEnd === false) {
            return false;
        }
        if ($tripStart > $tripEnd) {
            $orderedStops = array_reverse($orderedStops);
            $tripStart = array_search((string) $trip->start_from, $orderedStops, true);
            $tripEnd = array_search((string) $trip->end_to, $orderedStops, true);
        }

        $pickupIndex = array_search((string) $pickup, $orderedStops, true);
        $destinationIndex = array_search($destinationId, $orderedStops, true);

        return $pickupIndex !== false
            && $destinationIndex !== false
            && $pickupIndex >= $tripStart
            && $pickupIndex < $tripEnd
            && $destinationIndex > $pickupIndex
            && $destinationIndex <= $tripEnd;
    }
}
