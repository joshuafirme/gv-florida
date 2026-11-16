<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\CashierTransactionEvent;
use App\Models\FleetType;
use App\Models\Trip;
use App\Models\TripPrioritySeatAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrioritySeatAccessService
{
    public const ONLINE = 'online';
    public const WALK_IN = 'walk_in';

    public function __construct(
        private readonly SeatLayoutService $seatLayoutService,
        private readonly ScheduleBoardBroadcaster $scheduleBoardBroadcaster
    ) {
    }

    public function prioritySeatIds(FleetType|array $fleetType): array
    {
        return collect($this->seatLayoutService->layout($fleetType)['decks'])
            ->flatMap(fn (array $deck) => collect($deck['rows'])->flatMap(
                fn (array $row) => collect($row['groups'])->flatMap(fn (array $group) => $group['cells'])
            ))
            ->where('type', 'seat')
            ->where('is_sc_pwd', true)
            ->pluck('seat_id')
            ->values()
            ->all();
    }

    public function accessFor(Trip $trip, Carbon|string $journeyDate): ?TripPrioritySeatAccess
    {
        $date = Carbon::parse($journeyDate)->format('Y-m-d');

        if ($trip->relationLoaded('prioritySeatAccesses')) {
            return $trip->prioritySeatAccesses->first(
                fn (TripPrioritySeatAccess $access) => $access->journey_date?->format('Y-m-d') === $date
            );
        }

        return TripPrioritySeatAccess::query()
            ->where('trip_id', $trip->id)
            ->whereDate('journey_date', $date)
            ->first();
    }

    public function walkInEnabled(Trip $trip, Carbon|string $journeyDate): bool
    {
        $access = $this->accessFor($trip, $journeyDate);

        return $access ? (bool) $access->walk_in_enabled : true;
    }

    public function onlineEnabled(Trip $trip, Carbon|string $journeyDate): bool
    {
        return (bool) $this->accessFor($trip, $journeyDate)?->online_enabled;
    }

    public function restrictedSeatIds(
        Trip $trip,
        Carbon|string $journeyDate,
        bool $isWalkIn
    ): array {
        $trip->loadMissing('fleetType');
        $prioritySeats = $trip->fleetType ? $this->prioritySeatIds($trip->fleetType) : [];

        $channelEnabled = $isWalkIn
            ? $this->walkInEnabled($trip, $journeyDate)
            : $this->onlineEnabled($trip, $journeyDate);

        if (!$prioritySeats || $channelEnabled) {
            return [];
        }

        return $prioritySeats;
    }

    public function change(
        Trip $trip,
        Carbon $journeyDate,
        string $channel,
        bool $enabled,
        string $reason,
        string $authorizationCode,
        Admin $performedBy
    ): TripPrioritySeatAccess {
        if (!in_array($channel, [self::ONLINE, self::WALK_IN], true)) {
            throw ValidationException::withMessages([
                'channel' => 'Select a valid booking channel.',
            ]);
        }

        $authorizedBy = app(TransactionAuthorizationService::class)->authorize(
            $authorizationCode,
            TransactionAuthorizationService::PRIORITY_SEAT_ACCESS,
            ['channel' => $channel, 'reason' => $reason]
        );

        $access = DB::transaction(function () use (
            $trip,
            $journeyDate,
            $channel,
            $enabled,
            $reason,
            $authorizedBy,
            $performedBy
        ) {
            $trip = Trip::query()
                ->with(['fleetType', 'route', 'schedule'])
                ->lockForUpdate()
                ->findOrFail($trip->id);

            if (!$trip->fleetType || !$this->prioritySeatIds($trip->fleetType)) {
                throw ValidationException::withMessages([
                    'priority_seats' => 'This trip does not have any Priority Seats configured in its fleet layout.',
                ]);
            }

            $access = TripPrioritySeatAccess::query()
                ->where('trip_id', $trip->id)
                ->whereDate('journey_date', $journeyDate->format('Y-m-d'))
                ->lockForUpdate()
                ->first() ?? new TripPrioritySeatAccess();

            $access->trip_id = $trip->id;
            $access->journey_date = $journeyDate->format('Y-m-d');
            if (!$access->exists) {
                $access->online_enabled = false;
                $access->walk_in_enabled = true;
            }
            $access->{$channel === self::ONLINE ? 'online_enabled' : 'walk_in_enabled'} = $enabled;
            $access->reason = $reason;
            $access->updated_by_admin_id = $performedBy->id;
            $access->authorized_by_admin_id = $authorizedBy->id;
            $access->authorized_at = now();
            $access->save();

            $this->recordAuditEvent(
                $access,
                $trip,
                $journeyDate,
                $channel,
                $enabled,
                $reason,
                $performedBy,
                $authorizedBy
            );

            return $access;
        });

        $this->scheduleBoardBroadcaster->passengerTransaction([
            'trip_id' => $trip->id,
            'date_of_journey' => $journeyDate->format('Y-m-d'),
            'priority_seat_channel' => $channel,
            'priority_seat_enabled' => $enabled,
        ]);

        return $access;
    }

    private function recordAuditEvent(
        TripPrioritySeatAccess $access,
        Trip $trip,
        Carbon $journeyDate,
        string $channel,
        bool $enabled,
        string $reason,
        Admin $performedBy,
        Admin $authorizedBy
    ): void {
        $channelLabel = $channel === self::ONLINE ? 'Online' : 'Walk-In';

        CashierTransactionEvent::create([
            'admin_id' => $performedBy->id,
            'event_key' => 'priority-seat-access:' . $access->id . ':' . Str::uuid(),
            'status' => $channelLabel . ' Priority Seats ' . ($enabled ? 'Enabled' : 'Disabled'),
            'processed_at' => now(),
            'source' => 'Admin Portal',
            'journey_date' => $journeyDate->format('Y-m-d'),
            'departure_time' => $trip->schedule?->start_from,
            'trip_class' => $trip->fleetType?->name,
            'trip_route' => $trip->route?->name ?: $trip->title,
            'base_fare' => 0,
            'discount_amount' => 0,
            'surcharge_amount' => 0,
            'amount' => 0,
            'reason' => $reason,
            'snapshot' => [
                'audit_type' => 'priority_seat_access',
                'trip_id' => $trip->id,
                'channel' => $channel,
                'enabled' => $enabled,
                'online_enabled' => (bool) $access->online_enabled,
                'walk_in_enabled' => (bool) $access->walk_in_enabled,
                'authorized_by_admin_id' => $authorizedBy->id,
                'authorized_by_name' => $authorizedBy->name ?: $authorizedBy->username,
                'performed_by_admin_id' => $performedBy->id,
                'performed_by_name' => $performedBy->name ?: $performedBy->username,
            ],
        ]);
    }
}
