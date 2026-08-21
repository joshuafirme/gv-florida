<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\CashierTransactionEvent;
use App\Models\Trip;
use App\Models\TripChannelAvailability;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TripChannelAccessService
{
    public function apply(
        Trip $trip,
        Collection $dates,
        array $channelStates,
        ?string $reason,
        string $authorizationCode,
        Admin $performedBy
    ): Collection {
        $authorizedBy = app(TransactionAuthorizationService::class)->authorize(
            $authorizationCode,
            TransactionAuthorizationService::CHANNEL_ACCESS,
            [
                'reason' => $reason,
            ]
        );

        $dates = $dates
            ->map(fn ($date) => Carbon::parse($date)->startOfDay())
            ->unique(fn (Carbon $date) => $date->format('Y-m-d'))
            ->sortBy(fn (Carbon $date) => $date->format('Y-m-d'))
            ->values();

        $records = DB::transaction(function () use (
            $trip,
            $dates,
            $channelStates,
            $reason,
            $performedBy,
            $authorizedBy
        ) {
            Trip::query()->whereKey($trip->id)->lockForUpdate()->firstOrFail();
            $records = collect();

            foreach ($dates as $date) {
                foreach ($channelStates as $channel => $isEnabled) {
                    if ($isEnabled === null) {
                        continue;
                    }

                    $records->push(TripChannelAvailability::query()->updateOrCreate(
                        [
                            'trip_id' => $trip->id,
                            'journey_date' => $date->format('Y-m-d'),
                            'channel' => $channel,
                        ],
                        [
                            'is_enabled' => $isEnabled,
                            'reason' => $reason,
                            'performed_by_admin_id' => $performedBy->id,
                            'authorized_by_admin_id' => $authorizedBy->id,
                        ]
                    ));
                }
            }

            $this->recordAuditEvent(
                $trip,
                $dates,
                $channelStates,
                $reason,
                $performedBy,
                $authorizedBy
            );

            return $records;
        });

        return $records;
    }

    public function remove(
        Trip $trip,
        TripChannelAvailability $availability,
        string $authorizationCode,
        Admin $performedBy
    ): void {
        $change = sprintf(
            'Removed %s %s override for %s',
            ucfirst($availability->channel),
            $availability->is_enabled ? 'Available' : 'Blocked',
            $availability->journey_date->format('Y-m-d')
        );
        $authorizedBy = app(TransactionAuthorizationService::class)->authorize(
            $authorizationCode,
            TransactionAuthorizationService::CHANNEL_ACCESS,
            ['reason' => $change]
        );

        DB::transaction(function () use ($trip, $availability, $change, $performedBy, $authorizedBy) {
            Trip::query()->whereKey($trip->id)->lockForUpdate()->firstOrFail();
            $lockedAvailability = TripChannelAvailability::query()
                ->whereKey($availability->id)
                ->where('trip_id', $trip->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->recordAuditEvent(
                $trip,
                collect([$lockedAvailability->journey_date]),
                [],
                $change,
                $performedBy,
                $authorizedBy,
                [$change]
            );

            $lockedAvailability->delete();
        });
    }

    private function recordAuditEvent(
        Trip $trip,
        Collection $dates,
        array $channelStates,
        ?string $reason,
        Admin $performedBy,
        Admin $authorizedBy,
        ?array $changeLabels = null
    ): void {
        $changes = $changeLabels !== null
            ? collect($changeLabels)->values()
            : collect($channelStates)
                ->reject(fn ($state) => $state === null)
                ->map(fn ($state, $channel) => ucfirst($channel) . ': ' . ($state ? 'Available' : 'Blocked'))
                ->values();
        $dateLabels = $dates->map(fn (Carbon $date) => $date->format('Y-m-d'))->values();

        CashierTransactionEvent::create([
            'admin_id' => $performedBy->id,
            'event_key' => 'trip-channel-access:' . $trip->id . ':' . Str::uuid(),
            'status' => 'Channel Access Updated',
            'processed_at' => now(),
            'source' => 'Admin Portal',
            'journey_date' => $dates->first()?->format('Y-m-d'),
            'departure_time' => $trip->schedule?->start_from,
            'trip_class' => $trip->fleetType?->name,
            'trip_route' => $trip->route?->name ?: $trip->title,
            'base_fare' => 0,
            'discount_amount' => 0,
            'surcharge_amount' => 0,
            'amount' => 0,
            'reason' => $reason ?: $changes->implode('; '),
            'snapshot' => [
                'audit_type' => 'trip_channel_access',
                'trip_id' => $trip->id,
                'dates' => $dateLabels->all(),
                'changes' => $changes->all(),
                'authorized_by_admin_id' => $authorizedBy->id,
                'authorized_by_name' => $authorizedBy->name ?: $authorizedBy->username,
                'performed_by_admin_id' => $performedBy->id,
                'performed_by_name' => $performedBy->name ?: $performedBy->username,
            ],
        ]);
    }
}
