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
        $at ??= now();
        $departure = $this->departureFor($ticket);
        $afterDeparture = $at->gte($departure);

        if ((int) $ticket->status === Status::BOOKED_PENDING && $afterDeparture) {
            throw ValidationException::withMessages([
                'booking' => 'A pending booking may only be rebooked before its scheduled departure.',
            ]);
        }

        if ((int) $ticket->status === Status::BOOKED_APPROVED && $at->gte($departure->copy()->addHours(self::MISSED_BOOKING_GRACE_HOURS))) {
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
            'grace_ends_at' => $departure->copy()->addHours(self::MISSED_BOOKING_GRACE_HOURS),
            'after_departure' => $afterDeparture,
        ];
    }

    public function assertReplacementTrip(
        BookedTicket $ticket,
        Trip $replacement,
        Carbon|string $date,
        ?Carbon $at = null
    ): void {
        $at ??= now();

        if ((int) $replacement->id === (int) $ticket->trip_id) {
            throw ValidationException::withMessages([
                'trip_id' => 'Select a different trip for a New Trip rebooking.',
            ]);
        }

        if (!$this->isOperational($replacement)) {
            throw ValidationException::withMessages([
                'trip_id' => 'The replacement trip is inactive, cancelled, departed, or already arrived.',
            ]);
        }

        $departureTime = $replacement->schedule?->start_from;
        if (!$departureTime || $at->gte(Carbon::parse(Carbon::parse($date)->format('Y-m-d') . ' ' . $departureTime))) {
            throw ValidationException::withMessages([
                'trip_id' => 'Select a replacement trip that has not departed.',
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
            ], true);
    }

    public function forfeitureIsDue(BookedTicket $ticket, ?Carbon $at = null): bool
    {
        $at ??= now();

        return (int) $ticket->status === Status::BOOKED_APPROVED
            && $at->gte($this->graceEndsAt($ticket));
    }
}
