<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\BusLayout;
use App\Models\BookedTicket;
use App\Models\Trip;
use App\Services\PendingPaymentExpirationService;
use App\Services\SeatConflictService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookedTicketController extends Controller
{
    public function updateExpiredTicket(PendingPaymentExpirationService $expirationService)
    {
        $expiredCount = $expirationService->expireDue();

        return response()->json([
            'success' => true,
            'expired_count' => $expiredCount,
            'message' => "Expired {$expiredCount} pending payment(s).",
        ]);
    }

    public function getSeatPlan(Request $request)
    {
        $tripId = $request->trip_id;
        $date = $request->date;

        // 1. Fetch your Trip, FleetType, and BusLayout based on $tripId
        $trip = Trip::findOrFail($tripId);
        $fleetType = $trip->fleetType;
        $busLayout = new BusLayout($fleetType); // Or however you instantiate this in your system

        // 2. Check for seats already booked on this NEW date
        $bookedSeats = BookedTicket::query()
            ->whereIn('status', [
                Status::BOOKED_APPROVED,
                Status::BOOKED_PENDING
            ])
            ->whereDate('date_of_journey', Carbon::parse($request->date)->format('Y-m-d'))

            // Filter BookedTicket by Trip + Schedule conditions
            ->whereHas('trip', function ($query) use ($request) {
                $query->where('fleet_type_id', $request->fleet_type_id)
                    ->where('start_from', $request->source_id);

                // If you need to filter by destination as well, uncomment the line below:
                // ->where('end_to', $request->destination_id);
    
                // Filter by schedule start_from_time
                $query->whereHas('schedule', function ($q) use ($request) {
                    $q->where('start_from', $request->start_from_time);
                });
            })

            // Eager load relationships properly
            ->with([
                'trip' => function ($q) use ($request) {
                    // Load the trip and restrict its loaded schedule to the specific time
                    $q->with([
                        'schedule' => function ($sq) use ($request) {
                        $sq->where('start_from', $request->start_from_time);
                    }
                    ]);
                }
            ])
            ->get()->toArray();

        // 3. Customer-facing channels treat booked and administratively locked seats the same.
        $bookedSeatLabels = collect($bookedSeats)
            ->flatMap(fn ($booking) => $booking['seats'] ?? [])
            ->map(fn ($seat) => preg_replace('/^\d+-/', '', (string) $seat));
        $lockedSeatLabels = collect(app(SeatConflictService::class)->lockedSeats($trip, $date))
            ->map(fn ($seat) => preg_replace('/^\d+-/', '', (string) $seat));
        $disabled_seats = collect((array) ($fleetType->disabled_seats ?? []))
            ->merge($bookedSeatLabels)
            ->merge($lockedSeatLabels)
            ->unique()
            ->values()
            ->all();

        // 4. Render the Blade partial
        // Note: Save the Blade code you provided in my prompt into a file called 'seat_layout_partial.blade.php'
        $html = view('admin.partials.seat_layout_partial', compact(
            'fleetType',
            'busLayout',
            'disabled_seats'
        ))->render();

        return response()->json(['html' => $html]);
    }
}
