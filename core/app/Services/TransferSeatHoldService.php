<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\BookedTicket;
use App\Models\SlipSeriesNumber;
use App\Models\TransferSeatHold;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransferSeatHoldService
{
    public const HOLD_MINUTES = 15;

    public function __construct(
        private readonly SeatConflictService $seatConflicts,
        private readonly ScheduleBoardBroadcaster $broadcaster
    ) {
    }

    public function create(
        Admin $admin,
        BookedTicket $ticket,
        SlipSeriesNumber $slip,
        Trip $trip,
        string $date,
        int $pickupPoint,
        int $droppingPoint,
        string $seat
    ): array {
        $result = DB::transaction(function () use (
            $admin,
            $ticket,
            $slip,
            $trip,
            $date,
            $pickupPoint,
            $droppingPoint,
            $seat
        ) {
            $lockedTrip = Trip::query()->with(['route', 'fleetType'])->whereKey($trip->id)->lockForUpdate()->firstOrFail();
            $now = now();

            $previousHolds = TransferSeatHold::query()
                ->active($now)
                ->where('slip_series_number_id', $slip->id)
                ->where('held_by_admin_id', $admin->id)
                ->lockForUpdate()
                ->get();
            $previousHolds->each(function (TransferSeatHold $hold) use ($now) {
                $hold->released_at = $now;
                $hold->save();
            });

            $unavailable = collect($this->seatConflicts->unavailableSeats(
                $lockedTrip,
                $date,
                $pickupPoint,
                $droppingPoint,
                $ticket->id,
                true
            ));

            $ticketDate = Carbon::parse($ticket->date_of_journey)->format('Y-m-d');
            if ((int) $lockedTrip->id === (int) $ticket->trip_id && $date === $ticketDate) {
                $unavailable = $unavailable->merge(
                    $ticket->activeSlipSeriesNumbers
                        ->where('id', '!=', $slip->id)
                        ->pluck('seat')
                );
            }

            $normalizedUnavailable = $this->seatConflicts->normalizeSeats($unavailable->all());
            if (in_array($seat, $normalizedUnavailable, true)) {
                throw ValidationException::withMessages([
                    'seat' => 'This seat was just selected by another customer or staff member. Choose another seat.',
                ]);
            }

            $token = Str::random(64);
            $hold = TransferSeatHold::create([
                'slip_series_number_id' => $slip->id,
                'booked_ticket_id' => $ticket->id,
                'trip_id' => $lockedTrip->id,
                'date_of_journey' => $date,
                'pickup_point_id' => $pickupPoint,
                'dropping_point_id' => $droppingPoint,
                'seat_no' => $seat,
                'token_hash' => self::tokenHash($token),
                'held_by_admin_id' => $admin->id,
                'expires_at' => $now->copy()->addMinutes(self::HOLD_MINUTES),
            ]);

            return [
                'hold' => $hold,
                'token' => $token,
                'released_holds' => $previousHolds,
            ];
        }, 3);

        DB::afterCommit(function () use ($result) {
            $result['released_holds']->each(
                fn (TransferSeatHold $hold) => $this->broadcastHoldChange($hold, 'released')
            );
            $this->broadcastHoldChange($result['hold'], 'held');
        });

        return $result;
    }

    public function lockForConfirmation(
        string $token,
        Admin $admin,
        SlipSeriesNumber $slip,
        Trip $trip,
        string $date,
        int $pickupPoint,
        int $droppingPoint,
        string $seat
    ): TransferSeatHold {
        $hold = TransferSeatHold::query()
            ->where('token_hash', self::tokenHash($token))
            ->where('held_by_admin_id', $admin->id)
            ->where('slip_series_number_id', $slip->id)
            ->lockForUpdate()
            ->first();

        $matchesSelection = $hold
            && (int) $hold->trip_id === (int) $trip->id
            && $hold->date_of_journey?->format('Y-m-d') === $date
            && (int) $hold->pickup_point_id === $pickupPoint
            && (int) $hold->dropping_point_id === $droppingPoint
            && strtoupper((string) $hold->seat_no) === strtoupper($seat);

        if (!$matchesSelection || $hold->released_at || $hold->consumed_at || !$hold->expires_at?->isFuture()) {
            throw ValidationException::withMessages([
                'hold_token' => 'The 15-minute seat hold has expired. Return to seat selection and hold the seat again.',
            ]);
        }

        return $hold;
    }

    public function consume(TransferSeatHold $hold): void
    {
        $hold->consumed_at = now();
        $hold->save();
    }

    public function release(string $token, Admin $admin, SlipSeriesNumber $slip): bool
    {
        $hold = DB::transaction(function () use ($token, $admin, $slip) {
            $hold = TransferSeatHold::query()
                ->where('token_hash', self::tokenHash($token))
                ->where('held_by_admin_id', $admin->id)
                ->where('slip_series_number_id', $slip->id)
                ->whereNull('released_at')
                ->whereNull('consumed_at')
                ->lockForUpdate()
                ->first();

            if (!$hold) {
                return null;
            }

            $hold->released_at = now();
            $hold->save();

            return $hold;
        }, 3);

        if ($hold) {
            DB::afterCommit(fn () => $this->broadcastHoldChange($hold, 'released'));
        }

        return (bool) $hold;
    }

    public static function tokenHash(string $token): string
    {
        return hash('sha256', trim($token));
    }

    private function broadcastHoldChange(TransferSeatHold $hold, string $action): void
    {
        $this->broadcaster->passengerTransaction([
            'trip_id' => $hold->trip_id,
            'date_of_journey' => $hold->date_of_journey->format('Y-m-d'),
            'seat' => $hold->seat_no,
            'transfer_hold_action' => $action,
        ]);
    }
}
