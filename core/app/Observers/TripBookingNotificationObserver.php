<?php

namespace App\Observers;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Models\Schedule;
use App\Models\Trip;
use App\Services\BookingNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TripBookingNotificationObserver
{
    public function updated(Model $model): void
    {
        if ($model instanceof Trip) {
            $this->tripUpdated($model);
        }

        if ($model instanceof Schedule) {
            $this->scheduleUpdated($model);
        }
    }

    private function tripUpdated(Trip $trip): void
    {
        $eventType = null;
        $intro = null;

        if ($trip->wasChanged('trip_status') && $trip->trip_status === Status::TRIP_CANCELLED) {
            $eventType = 'trip_cancelled';
        } elseif ($trip->wasChanged(['schedule_id', 'start_from', 'end_to', 'vehicle_route_id', 'trip_status'])) {
            $eventType = 'trip_schedule_changed';
            $intro = $trip->trip_status === Status::TRIP_DELAYED
                ? 'Your trip has been delayed and its schedule was updated.'
                : null;
        }

        if (!$eventType) {
            return;
        }

        $this->notifyTrip(
            (int) $trip->id,
            $eventType,
            "trip:{$trip->id}:{$eventType}:" . ($trip->updated_at?->format('YmdHisv') ?: now()->format('YmdHisv')),
            $intro
        );
    }

    private function scheduleUpdated(Schedule $schedule): void
    {
        if (!$schedule->wasChanged(['start_from', 'end_at'])) {
            return;
        }

        $version = $schedule->updated_at?->format('YmdHisv') ?: now()->format('YmdHisv');
        Trip::query()->where('schedule_id', $schedule->id)->pluck('id')->each(function ($tripId) use ($schedule, $version) {
            $this->notifyTrip(
                (int) $tripId,
                'trip_schedule_changed',
                "schedule:{$schedule->id}:trip:{$tripId}:{$version}"
            );
        });
    }

    private function notifyTrip(int $tripId, string $eventType, string $keyPrefix, ?string $intro = null): void
    {
        $callback = static function () use ($tripId, $eventType, $keyPrefix, $intro): void {
            BookedTicket::query()
                ->where('trip_id', $tripId)
                ->whereNotNull('user_id')
                ->whereIn('status', [Status::BOOKED_APPROVED, Status::BOOKED_PENDING])
                ->whereDate('date_of_journey', '>=', today())
                ->orderBy('id')
                ->chunkById(100, function ($tickets) use ($eventType, $keyPrefix, $intro) {
                    foreach ($tickets as $ticket) {
                        app(BookingNotificationService::class)->send(
                            $ticket,
                            $eventType,
                            "{$keyPrefix}:ticket:{$ticket->id}",
                            null,
                            null,
                            $intro
                        );
                    }
                });
        };

        DB::transactionLevel() > 0 ? DB::afterCommit($callback) : $callback();
    }
}
