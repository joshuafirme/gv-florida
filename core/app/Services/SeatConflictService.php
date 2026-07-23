<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SeatConflictService
{
    public function normalizeSeats(array|string|null $seats): array
    {
        if (is_string($seats)) {
            $seats = explode(',', $seats);
        }

        return collect($seats ?: [])
            ->map(fn ($seat) => strtoupper(trim((string) $seat)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function isValidSegment(Trip $trip, int|string $pickup, int|string $drop): bool
    {
        $positions = $this->segmentPositions($trip, $pickup, $drop);

        if (!$positions) {
            return false;
        }

        [$pickupPosition, $dropPosition, $tripStart, $tripEnd] = $positions;

        if ($tripStart < $tripEnd) {
            return $pickupPosition >= $tripStart
                && $dropPosition <= $tripEnd
                && $pickupPosition < $dropPosition;
        }

        return $pickupPosition <= $tripStart
            && $dropPosition >= $tripEnd
            && $pickupPosition > $dropPosition;
    }

    public function conflicts(
        Trip $trip,
        Carbon|string $dateOfJourney,
        int|string $pickup,
        int|string $drop,
        array|string $seats,
        ?int $excludeTicketId = null,
        bool $lockForUpdate = false
    ): Collection {
        $selectedSeats = $this->normalizeSeats($seats);

        if (!$selectedSeats) {
            return collect();
        }

        return $this->overlappingBookings(
            $trip,
            $dateOfJourney,
            $pickup,
            $drop,
            $excludeTicketId,
            $lockForUpdate
        )->filter(function (BookedTicket $booking) use ($selectedSeats) {
            return count(array_intersect($selectedSeats, $this->normalizeSeats($booking->seats))) > 0;
        })->values();
    }

    public function unavailableSeats(
        Trip $trip,
        Carbon|string $dateOfJourney,
        int|string $pickup,
        int|string $drop,
        ?int $excludeTicketId = null,
        bool $lockForUpdate = false
    ): array {
        return $this->overlappingBookings(
            $trip,
            $dateOfJourney,
            $pickup,
            $drop,
            $excludeTicketId,
            $lockForUpdate
        )
            ->flatMap(fn (BookedTicket $booking) => $this->normalizeSeats($booking->seats))
            ->unique()
            ->values()
            ->all();
    }

    private function overlappingBookings(
        Trip $trip,
        Carbon|string $dateOfJourney,
        int|string $pickup,
        int|string $drop,
        ?int $excludeTicketId,
        bool $lockForUpdate
    ): Collection {
        $requestedBounds = $this->segmentBounds($trip, $pickup, $drop);

        if (!$requestedBounds) {
            return collect();
        }

        $query = BookedTicket::query()
            ->where('trip_id', $trip->id)
            ->whereDate('date_of_journey', Carbon::parse($dateOfJourney)->format('Y-m-d'))
            ->where(function ($statusQuery) {
                $statusQuery->where('status', Status::BOOKED_APPROVED)
                    ->orWhere(function ($pendingQuery) {
                        $pendingQuery->where('status', Status::BOOKED_PENDING)
                            ->where(function ($activeQuery) {
                                $activeQuery->where('created_at', '>=', Carbon::now()->subMinutes(15))
                                    ->orWhereHas('deposit', function ($depositQuery) {
                                        $depositQuery->where('created_at', '>=', Carbon::now()->subMinutes(15));
                                    });
                            });
                    });
            });

        if ($excludeTicketId) {
            $query->where('id', '!=', $excludeTicketId);
        }

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get()->filter(function (BookedTicket $booking) use ($trip, $requestedBounds) {
            $bookedBounds = $this->segmentBounds($trip, $booking->pickup_point, $booking->dropping_point);

            if (!$bookedBounds || !$this->segmentsOverlap($requestedBounds, $bookedBounds)) {
                return false;
            }

            return true;
        })->values();
    }

    public function conflictingSeats(Collection $conflicts, array|string $selectedSeats): array
    {
        $selectedSeats = $this->normalizeSeats($selectedSeats);

        return $conflicts
            ->flatMap(fn (BookedTicket $booking) => array_intersect($selectedSeats, $this->normalizeSeats($booking->seats)))
            ->unique()
            ->values()
            ->all();
    }

    private function segmentPositions(Trip $trip, int|string $pickup, int|string $drop): ?array
    {
        $trip->loadMissing('route');
        $stoppages = array_map('strval', array_values($trip->route?->stoppages ?: []));
        $pickupPosition = array_search((string) $pickup, $stoppages, true);
        $dropPosition = array_search((string) $drop, $stoppages, true);
        $tripStart = array_search((string) $trip->start_from, $stoppages, true);
        $tripEnd = array_search((string) $trip->end_to, $stoppages, true);

        if (in_array(false, [$pickupPosition, $dropPosition, $tripStart, $tripEnd], true)) {
            return null;
        }

        return [$pickupPosition, $dropPosition, $tripStart, $tripEnd];
    }

    private function segmentBounds(Trip $trip, int|string $pickup, int|string $drop): ?array
    {
        $positions = $this->segmentPositions($trip, $pickup, $drop);

        if (!$positions) {
            return null;
        }

        [$pickupPosition, $dropPosition] = $positions;

        return [min($pickupPosition, $dropPosition), max($pickupPosition, $dropPosition)];
    }

    private function segmentsOverlap(array $first, array $second): bool
    {
        return max($first[0], $second[0]) < min($first[1], $second[1]);
    }
}
