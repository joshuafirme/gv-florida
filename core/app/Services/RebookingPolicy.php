<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class RebookingPolicy
{
    public const MISSED_BOOKING_GRACE_HOURS = 24;

    public function departureFor(BookedTicket $ticket): Carbon
    {
        $departureTime = $ticket->trip?->schedule?->start_from;

        if (!$ticket->date_of_journey || !$departureTime) {
            throw ValidationException::withMessages([
                'booking' => 'The current booking does not have a valid scheduled departure.',
            ]);
        }

        return Carbon::parse(
            Carbon::parse($ticket->date_of_journey)->format('Y-m-d') . ' ' . $departureTime
        );
    }

    public function graceEndsAt(BookedTicket $ticket): Carbon
    {
        return $this->departureFor($ticket)->addHours(self::MISSED_BOOKING_GRACE_HOURS);
    }

    public function assertEligible(BookedTicket $ticket, ?Carbon $at = null): array
    {
        return $this->assertAdminEligible($ticket, null, $at);
    }

    public function assertAdminEligible(
        BookedTicket $ticket,
        ?Carbon $originalDeparture = null,
        ?Carbon $at = null
    ): array
    {
        $at ??= now();
        $departure = $this->departureFor($ticket);
        $originalDeparture ??= $departure->copy();
        $afterDeparture = $at->gte($departure);

        if ((int) $ticket->status === Status::BOOKED_PENDING && $afterDeparture) {
            throw ValidationException::withMessages([
                'booking' => 'A pending booking may only be rebooked before its scheduled departure.',
            ]);
        }

        if ((int) $ticket->status === Status::BOOKED_APPROVED && $at->gte($originalDeparture->copy()->addHours(self::MISSED_BOOKING_GRACE_HOURS))) {
            throw ValidationException::withMessages([
                'booking' => 'The 24-hour rebooking grace period has expired. Apply the existing cancellation or forfeiture process.',
            ]);
        }

        if (!in_array((int) $ticket->status, [Status::BOOKED_APPROVED, Status::BOOKED_PENDING], true)) {
            throw ValidationException::withMessages([
                'booking' => 'Only active paid or pending bookings may be rebooked.',
            ]);
        }

        return [
            'departure_at' => $departure,
            'original_departure_at' => $originalDeparture,
            'grace_ends_at' => $originalDeparture->copy()->addHours(self::MISSED_BOOKING_GRACE_HOURS),
            'after_departure' => $afterDeparture,
            'after_original_departure' => $at->gte($originalDeparture),
        ];
    }

    public function assertReplacementTrip(
        BookedTicket $ticket,
        Trip $replacement,
        Carbon|string $date,
        ?Carbon $at = null
    ): void {
        if ((int) $replacement->id === (int) $ticket->trip_id) {
            throw ValidationException::withMessages([
                'trip_id' => 'Select a different trip for a New Trip rebooking.',
            ]);
        }

        $this->assertAdminTargetTrip($replacement, $date, $at);
    }

    public function assertAdminTargetTrip(
        Trip $trip,
        Carbon|string $date,
        ?Carbon $at = null
    ): void {
        $at ??= now();

        if (!$this->isOperational($trip)) {
            throw ValidationException::withMessages([
                'trip_id' => 'The selected trip is inactive, cancelled, departed, already arrived, or broken down.',
            ]);
        }

        if (!$trip->schedule?->start_from) {
            throw ValidationException::withMessages([
                'trip_id' => 'The selected trip does not have a valid scheduled departure.',
            ]);
        }

        // Admin approval follows actual dispatch status, even after the scheduled time.
        if (Carbon::parse($date)->startOfDay()->lt($at->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'date' => 'Select today or a future travel date.',
            ]);
        }
    }

    public function assertEnoughSeats(int $requiredSeats, int $availableSeats): void
    {
        if ($availableSeats < $requiredSeats) {
            throw ValidationException::withMessages([
                'seats' => "The replacement trip has only {$availableSeats} available seat(s); {$requiredSeats} are required.",
            ]);
        }
    }

    public function isOperational(Trip $trip): bool
    {
        return (int) $trip->status === Status::ENABLE
            && (int) ($trip->schedule?->status ?? Status::ENABLE) === Status::ENABLE
            && !in_array($trip->trip_status, [
                Status::TRIP_CANCELLED,
                Status::TRIP_DEPARTED,
                Status::TRIP_ARRIVED,
                Status::TRIP_BREAKDOWN,
            ], true);
    }

    public function forfeitureIsDue(BookedTicket $ticket, ?Carbon $at = null): bool
    {
        $at ??= now();

        return (int) $ticket->status === Status::BOOKED_APPROVED
            && $at->gte($this->graceEndsAt($ticket));
    }
}
