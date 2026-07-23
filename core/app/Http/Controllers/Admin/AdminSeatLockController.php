<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSeatLock;
use App\Models\Trip;
use App\Services\AdminSeatLockService;
use App\Services\SeatConflictService;
use App\Services\SeatLayoutService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminSeatLockController extends Controller
{
    public function index(
        Request $request,
        Trip $trip,
        SeatConflictService $seatConflictService,
        SeatLayoutService $seatLayoutService
    ) {
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $pageTitle = 'Manage Seats';
        $date = Carbon::parse($request->date ?: now())->startOfDay();
        $trip->load(['fleetType', 'route', 'schedule', 'startFrom', 'endTo']);
        $decks = $seatLayoutService->decks($trip->fleetType);
        $seatIds = $seatLayoutService->seatIds($trip->fleetType);
        $bookedSeats = $seatLayoutService->canonicalizeSeats(
            $trip->fleetType,
            $seatConflictService->bookedSeats(
                $trip,
                $date,
                $trip->start_from,
                $trip->end_to
            )
        );
        $lockedSeats = AdminSeatLock::query()
            ->active()
            ->where('trip_id', $trip->id)
            ->whereDate('date_of_journey', $date->format('Y-m-d'))
            ->with(['lockedBy:id,name,username', 'lockAuthorizedBy:id,name,username'])
            ->get()
            ->keyBy('seat_no');
        $disabledSeats = $seatLayoutService->disabledSeatIds($trip->fleetType);
        $unavailable = collect($bookedSeats)
            ->merge($lockedSeats->keys())
            ->merge($disabledSeats)
            ->unique();
        $stats = [
            'capacity' => $seatIds->count(),
            'booked' => count($bookedSeats),
            'locked' => $lockedSeats->count(),
            'available' => max($seatIds->count() - $unavailable->count(), 0),
        ];

        return view('admin.trip.seat-locks', compact(
            'pageTitle',
            'trip',
            'date',
            'decks',
            'bookedSeats',
            'lockedSeats',
            'disabledSeats',
            'stats'
        ));
    }

    public function change(
        Request $request,
        Trip $trip,
        AdminSeatLockService $seatLockService
    ) {
        $validated = $request->validate([
            'date' => 'required|date',
            'seat' => 'required|string|max:30',
            'action' => 'required|in:lock,unlock',
            'reason' => 'required|string|max:1000',
            'authorization_code' => 'required|string|max:100',
        ]);

        $seatLock = $seatLockService->change(
            $trip,
            Carbon::parse($validated['date'])->startOfDay(),
            $validated['seat'],
            $validated['action'],
            trim($validated['reason']),
            $validated['authorization_code'],
            $request->user('admin')
        );

        return response()->json([
            'success' => true,
            'message' => $validated['action'] === 'lock'
                ? 'Seat ' . formatSeatLabel($seatLock->seat_no) . ' was locked successfully.'
                : 'Seat ' . formatSeatLabel($seatLock->seat_no) . ' was unlocked successfully.',
        ]);
    }
}
