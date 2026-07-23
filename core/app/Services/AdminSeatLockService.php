<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\Admin;
use App\Models\AdminSeatLock;
use App\Models\CashierTransactionEvent;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminSeatLockService
{
    public const PERMISSION = 'admin.trip.seat-locks.index';

    public function __construct(
        private readonly SeatConflictService $seatConflictService,
        private readonly SeatLayoutService $seatLayoutService,
        private readonly ScheduleBoardBroadcaster $scheduleBoardBroadcaster
    ) {
    }

    public function change(
        Trip $trip,
        Carbon $date,
        string $seat,
        string $action,
        string $reason,
        string $authorizationCode,
        Admin $performedBy
    ): AdminSeatLock {
        $authorizedBy = $this->authorizedAdmin($authorizationCode);

        $seatLock = DB::transaction(function () use (
            $trip,
            $date,
            $seat,
            $action,
            $reason,
            $authorizedBy,
            $performedBy
        ) {
            $trip = Trip::query()
                ->with(['fleetType', 'route', 'schedule'])
                ->lockForUpdate()
                ->findOrFail($trip->id);
            $seat = $this->seatLayoutService->canonicalSeatId($trip->fleetType, $seat);

            if (!$seat) {
                throw ValidationException::withMessages([
                    'seat' => 'The selected seat does not exist in this fleet layout.',
                ]);
            }

            $seatLock = AdminSeatLock::query()
                ->where('trip_id', $trip->id)
                ->whereDate('date_of_journey', $date->format('Y-m-d'))
                ->where('seat_no', $seat)
                ->lockForUpdate()
                ->first();

            if ($action === 'lock') {
                $seatLock = $this->lockSeat(
                    $trip,
                    $date,
                    $seat,
                    $reason,
                    $performedBy,
                    $authorizedBy,
                    $seatLock
                );
            } else {
                $seatLock = $this->unlockSeat(
                    $trip,
                    $date,
                    $seat,
                    $reason,
                    $performedBy,
                    $authorizedBy,
                    $seatLock
                );
            }

            $this->recordAuditEvent(
                $seatLock,
                $trip,
                $date,
                $action,
                $reason,
                $performedBy,
                $authorizedBy
            );

            return $seatLock;
        });

        $this->scheduleBoardBroadcaster->passengerTransaction([
            'trip_id' => $trip->id,
            'date_of_journey' => $date->format('Y-m-d'),
            'seat' => $seatLock->seat_no,
            'seat_lock_action' => $action,
        ]);

        return $seatLock;
    }

    private function lockSeat(
        Trip $trip,
        Carbon $date,
        string $seat,
        string $reason,
        Admin $performedBy,
        Admin $authorizedBy,
        ?AdminSeatLock $seatLock
    ): AdminSeatLock {
        if ($seatLock?->is_active) {
            throw ValidationException::withMessages([
                'seat' => 'This seat is already administratively locked.',
            ]);
        }

        if (in_array($seat, $this->seatLayoutService->disabledSeatIds($trip->fleetType), true)) {
            throw ValidationException::withMessages([
                'seat' => 'This fleet seat is permanently disabled and cannot be locked.',
            ]);
        }

        $bookedSeats = $this->seatLayoutService->canonicalizeSeats(
            $trip->fleetType,
            $this->seatConflictService->bookedSeats(
                $trip,
                $date,
                $trip->start_from,
                $trip->end_to,
                lockForUpdate: true
            )
        );

        if (in_array($seat, $bookedSeats, true)) {
            throw ValidationException::withMessages([
                'seat' => 'This seat is already booked or held by an active passenger transaction.',
            ]);
        }

        $seatLock ??= new AdminSeatLock();
        $seatLock->trip_id = $trip->id;
        $seatLock->date_of_journey = $date->format('Y-m-d');
        $seatLock->seat_no = $seat;
        $seatLock->is_active = true;
        $seatLock->reason = $reason;
        $seatLock->locked_by_admin_id = $performedBy->id;
        $seatLock->lock_authorized_by_admin_id = $authorizedBy->id;
        $seatLock->locked_at = now();
        $seatLock->unlock_reason = null;
        $seatLock->unlocked_by_admin_id = null;
        $seatLock->unlock_authorized_by_admin_id = null;
        $seatLock->unlocked_at = null;
        $seatLock->save();

        return $seatLock;
    }

    private function unlockSeat(
        Trip $trip,
        Carbon $date,
        string $seat,
        string $reason,
        Admin $performedBy,
        Admin $authorizedBy,
        ?AdminSeatLock $seatLock
    ): AdminSeatLock {
        if (!$seatLock?->is_active) {
            throw ValidationException::withMessages([
                'seat' => 'This seat is not currently locked.',
            ]);
        }

        $seatLock->is_active = false;
        $seatLock->unlock_reason = $reason;
        $seatLock->unlocked_by_admin_id = $performedBy->id;
        $seatLock->unlock_authorized_by_admin_id = $authorizedBy->id;
        $seatLock->unlocked_at = now();
        $seatLock->save();

        return $seatLock;
    }

    private function authorizedAdmin(string $authorizationCode): Admin
    {
        $admin = Admin::query()
            ->with('role:id,permissions')
            ->where('status', Status::ENABLE)
            ->where('passcode', $authorizationCode)
            ->first();
        $permissions = json_decode($admin?->role?->permissions ?: '[]', true) ?: [];

        if (!$admin || !in_array(self::PERMISSION, $permissions, true)) {
            throw ValidationException::withMessages([
                'authorization_code' => 'The authorization code is invalid or the personnel account is not authorized for seat locking.',
            ]);
        }

        return $admin;
    }

    private function recordAuditEvent(
        AdminSeatLock $seatLock,
        Trip $trip,
        Carbon $date,
        string $action,
        string $reason,
        Admin $performedBy,
        Admin $authorizedBy
    ): void {
        $status = $action === 'lock' ? 'Seat Locked' : 'Seat Unlocked';

        CashierTransactionEvent::create([
            'admin_id' => $performedBy->id,
            'event_key' => 'admin-seat-lock:' . $seatLock->id . ':' . Str::uuid(),
            'status' => $status,
            'processed_at' => now(),
            'source' => 'Admin Portal',
            'journey_date' => $date->format('Y-m-d'),
            'departure_time' => $trip->schedule?->start_from,
            'trip_class' => $trip->fleetType?->name,
            'trip_route' => $trip->route?->name ?: $trip->title,
            'seat_no' => $seatLock->seat_no,
            'base_fare' => 0,
            'discount_amount' => 0,
            'surcharge_amount' => 0,
            'amount' => 0,
            'reason' => $reason,
            'snapshot' => [
                'audit_type' => 'admin_seat_lock',
                'action' => ucfirst($action),
                'seat_lock_id' => $seatLock->id,
                'trip_id' => $trip->id,
                'authorized_by_admin_id' => $authorizedBy->id,
                'authorized_by_name' => $authorizedBy->name ?: $authorizedBy->username,
                'performed_by_admin_id' => $performedBy->id,
                'performed_by_name' => $performedBy->name ?: $performedBy->username,
            ],
        ]);
    }
}
