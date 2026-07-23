<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\BusLayout;
use App\Models\AssignedVehicle;
use App\Models\BookedTicket;
use Illuminate\Http\Request;
use App\Models\VehicleRoute;
use App\Models\Counter;
use App\Models\FleetType;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Services\ScheduleBoardBroadcaster;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManageTripController extends Controller
{
    public function routeList(Request $request)
    {
        $pageTitle = 'All Routes';
        $routes = VehicleRoute::query()->with(['startFrom', 'endTo']);

        // 1. Dynamic Filtering (Search by Name, Distance, Time, or Related Starting/Ending Points)
        if ($request->search) {
            $search = $request->search;
            $routes->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('distance', 'like', "%$search%")
                    ->orWhere('time', 'like', "%$search%")
                    ->orWhereHas('startFrom', function ($start) use ($search) {
                        $start->where('name', 'like', "%$search%");
                    })
                    ->orWhereHas('endTo', function ($end) use ($search) {
                        $end->where('name', 'like', "%$search%");
                    });
            });
        }

        // 2. Status Filtering
        if ($request->has('status') && $request->status != 'all') {
            $routes->where('status', $request->status);
        }

        // 3. Dynamic Sorting
        $sortField = $request->get('sort_field', 'id'); // Default sort field
        $sortOrder = $request->get('sort_order', 'desc'); // Default sort order

        // Define allowable sort fields to prevent SQL injection
        $allowedSorts = ['name', 'distance', 'time', 'status', 'id'];

        if (in_array($sortField, $allowedSorts)) {
            $routes->orderBy($sortField, $sortOrder);
        }

        // Paginate and append all query parameters to keep filters/sorts active across pages
        $routes = $routes->paginate(getPaginate())->appends($request->all());
        $stoppages = Counter::active()->get();
        return view('admin.trip.route.list', compact('pageTitle', 'routes', 'stoppages'));
    }

    public function routeForm($id = null)
    {
        $pageTitle = $id ? 'Update Route' : 'Create Route';
        $allStoppages = Counter::get();

        $route = null;
        $stoppages = []; // Initialize as empty array for the "Create" view

        if ($id) {
            $route = VehicleRoute::findOrFail($id);

            $stoppages = getIntermediateStoppages($route->stoppages);
        }

        return view('admin.trip.route.form', compact('pageTitle', 'stoppages', 'route', 'allStoppages'));
    }

    public function routeStore(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'start_from' => 'required|integer|gt:0',
            'end_to' => 'required|integer|gt:0',
            'distance' => 'required',
            'time' => 'required',
            'stoppages' => 'nullable|array|min:1',
            'stoppages.*' => 'nullable|integer|gt:0',
        ], [
            'stoppages.*.integer' => 'Invalid Stoppage Field'
        ]);

        if ($request->start_from == $request->end_to) {
            $notify[] = ['error', 'Starting point and ending point can\'t be same'];
            return back()->withNotify($notify);
        }

        $stoppages = $request->stoppages ? array_filter($request->stoppages) : [];

        if (!in_array($request->start_from, $stoppages)) {
            array_unshift($stoppages, $request->start_from);
        }

        if (!in_array($request->end_to, $stoppages)) {
            array_push($stoppages, $request->end_to);
        }

        $route = new VehicleRoute();
        $route->name = $request->name;
        $route->start_from = $request->start_from;
        $route->end_to = $request->end_to;
        $route->stoppages = array_unique($stoppages);
        $route->distance = $request->distance;
        $route->time = $request->time;
        $route->save();

        $notify[] = ['success', 'Route created successfully'];
        return back()->withNotify($notify);
    }

    public function routeEdit($id)
    {
        $route = VehicleRoute::findOrFail($id);
        $pageTitle = 'Update Route - ' . $route->name;
        $allStoppages = Counter::get();

        $stoppagesArray = $route->stoppages;
        $pos = array_search($route->start_from, $stoppagesArray);
        unset($stoppagesArray[$pos]);
        $pos = array_search($route->end_to, $stoppagesArray);
        unset($stoppagesArray[$pos]);

        if (!empty($stoppagesArray)) {
            $stoppages = Counter::active()->whereIn('id', $stoppagesArray)
                ->orderByRaw("field(id," . implode(',', $stoppagesArray) . ")")
                ->get();
        } else {
            $stoppages = [];
        }

        return view('admin.trip.route.edit', compact('pageTitle', 'stoppages', 'route', 'allStoppages'));
    }

    public function routeUpdate(Request $request, $id)
    {
        // 1. Updated validation (added 'different:start_from' and made 'name' nullable to match frontend)
        $request->validate([
            'name' => 'nullable|string',
            'start_from' => 'required|integer|gt:0',
            'end_to' => 'required|integer|gt:0|different:start_from',
            'distance' => 'required',
            'time' => 'required',
            'stoppages' => 'nullable|array',
            'stoppages.*' => 'nullable|integer|gt:0',
        ], [
            'end_to.different' => 'Starting point and ending point cannot be the same.',
            'stoppages.*.integer' => 'Invalid Stoppage Field'
        ]);

        // 2. Filter out empty values from the intermediate stops
        $stoppages = $request->stoppages ? array_filter($request->stoppages) : [];

        // 3. FORCE REMOVE the start and end points from the intermediate array 
        // just in case the user accidentally selected them in the dropdowns.
        $stoppages = array_diff($stoppages, [$request->start_from, $request->end_to]);

        // 4. Safely push the Origin to the beginning and Destination to the end
        array_unshift($stoppages, $request->start_from);
        array_push($stoppages, $request->end_to);

        // 5. Remove any other accidental duplicates
        $stoppages = array_unique($stoppages);

        // 6. CRITICAL FIX: Reset array keys so Laravel saves it as a clean JSON array [1, 2, 3]
        $stoppages = array_values($stoppages);

        // 7. Ensure all IDs are strings to prevent JS/PHP strict-type lookup errors later
        $stoppages = array_map('strval', $stoppages);

        // 8. Save
        $route = VehicleRoute::findOrFail($id);
        $route->name = $request->name;
        $route->start_from = $request->start_from;
        $route->end_to = $request->end_to;
        $route->stoppages = $stoppages;
        $route->distance = $request->distance;
        $route->time = $request->time;
        $route->save();


        $notify[] = ['success', 'Route updated successfully'];
        return back()->withNotify($notify);
    }

    public function bulkStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'action_type' => 'required|in:enable,disable'
        ]);

        $status = $request->action_type == 'enable' ? 1 : 0;

        VehicleRoute::whereIn('id', $request->ids)->update(['status' => $status]);

        $notify[] = ['success', 'Selected routes have been successfully updated.'];
        return back()->withNotify($notify);
    }

    public function routeStatus($id)
    {
        return VehicleRoute::changeStatus($id);
    }

    public function schedules(Request $request)
    {
        $pageTitle = 'All Schedules';
        $schedules = Schedule::query();

        // 1. Dynamic Filtering
        if ($request->start_at) {
            $start = Carbon::parse($request->start_at)->format('H:i:s');
            $schedules->whereTime('start_from', '=', $start);
        }
        if ($request->end_at) {
            $end = Carbon::parse($request->end_at)->format('H:i:s');
            $schedules->whereTime('end_at', '=', $end);
        }

        if ($request->has('status') && $request->status != 'all') {
            $schedules->where('status', $request->status);
        }

        // 2. Dynamic Sorting
        $sortField = $request->get('sort_field', 'id'); // Default sort field
        $sortOrder = $request->get('sort_order', 'desc'); // Default sort order

        // Define allowable sort fields to prevent SQL injection (Duration is computed in Blade, so we don't sort by it via SQL)
        $allowedSorts = ['start_from', 'end_at', 'status', 'id'];

        if (in_array($sortField, $allowedSorts)) {
            $schedules->orderBy($sortField, $sortOrder);
        }

        // Paginate and append query parameters to keep filters/sorts active across pages
        $schedules = $schedules->paginate(getPaginate())->appends($request->all());

        return view('admin.trip.schedule', compact('pageTitle', 'schedules'));
    }

    // New Method for Bulk Enable/Disable
    public function bulkScheduleStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'action_type' => 'required|in:enable,disable'
        ]);

        $status = $request->action_type == 'enable' ? 1 : 0;

        Schedule::whereIn('id', $request->ids)->update(['status' => $status]);

        $notify[] = ['success', 'Selected schedules have been successfully updated.'];
        return back()->withNotify($notify);
    }

    public function scheduleStore(Request $request, $id = 0)
    {
        $request->validate([
            'start_from' => 'required',
            'end_at' => 'required',
        ]);

        $check = Schedule::where('start_from', Carbon::parse($request->start_from)->format('H:i:s'))->where('end_at', Carbon::parse($request->end_at)->format('H:i:s'))->first();
        if ($check) {
            $notify[] = ['error', 'This schedule has already added'];
            return redirect()->back()->withNotify($notify);
        }

        if ($id) {
            $schedule = Schedule::findOrFail($id);
            $message = 'Schedule updated successfully';
        } else {
            $schedule = new Schedule();
            $message = 'Schedule created successfully';
        }

        $schedule->start_from = $request->start_from;
        $schedule->end_at = $request->end_at;
        $schedule->save();

        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }

    public function scheduleStatus($id)
    {
        return Schedule::changeStatus($id);
    }

    public function trips(Request $request)
    {
        $pageTitle = "All Trip";
        $emptyMessage = "No trip found";
        $fleetTypes = FleetType::where('status', 1)->get();
        $routes = VehicleRoute::where('status', 1)->get();
        $schedules = Schedule::where('status', 1)->get();
        $stoppages = Counter::where('status', 1)->get();

        $trips = Trip::with(['fleetType', 'route', 'schedule']);

        // 1. Dynamic Filtering (Search by Title)
        if ($request->search) {
            $search = $request->search;
            $trips->where('title', 'like', "%$search%");
        }

        // 2. Status Filtering
        if ($request->has('status') && $request->status != 'all') {
            $trips->where('status', $request->status);
        }

        // 3. Dynamic Sorting
        $sortField = $request->get('sort_field', 'id'); // Default sort
        $sortOrder = $request->get('sort_order', 'desc');

        // Whitelist allowed sort columns
        $allowedSorts = ['id', 'title', 'fleet_type_id', 'schedule_id', 'trip_status', 'status'];

        if (in_array($sortField, $allowedSorts)) {
            $trips->orderBy($sortField, $sortOrder);
        }

        // Paginate and append query params
        $trips = $trips->paginate(getPaginate())->appends($request->all());

        return view('admin.trip.trip', compact('pageTitle', 'emptyMessage', 'trips', 'fleetTypes', 'routes', 'schedules', 'stoppages'));
    }

    // New Method for Bulk Enable/Disable
    public function bulkTripStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'action_type' => 'required|in:enable,disable'
        ]);

        $status = $request->action_type == 'enable' ? 1 : 0;

        Trip::whereIn('id', $request->ids)->update(['status' => $status]);

        $notify[] = ['success', 'Selected trips have been successfully updated.'];
        return back()->withNotify($notify);
    }

    public function tripStore(Request $request, $id = 0)
    {
        $request->validate([
            'schedule_id' => 'required|integer|gt:0',
            'vehicle_route_id' => 'required|integer|gt:0',
            'fleet_type_id' => 'required|integer|gt:0',
        ]);

        $route = VehicleRoute::findOrFail($request->vehicle_route_id);
        $fleetType = FleetType::findOrFail($request->fleet_type_id);

        if ($id) {
            $trip = Trip::findOrFail($id);
            $message = 'Trip updated successfully';
        } else {
            $trip = new Trip();
            $message = 'Trip created successfully';
            // Set defaults for fields removed from the UI
            $trip->trip_status = Status::TRIP_ON_TIME ?? 1;
            $trip->day_off = [];
        }

        // Auto-generate the title using Route and Bus Type
        $trip->title = $route->name . ' - ' . $fleetType->name;
        $trip->fleet_type_id = $request->fleet_type_id;
        $trip->vehicle_route_id = $request->vehicle_route_id;
        $trip->schedule_id = $request->schedule_id;
        $trip->start_from = $route->start_from;
        $trip->end_to = $route->end_to;
        $trip->save();

        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }

    public function tripStatus($id)
    {
        return Trip::changeStatus($id);
    }

    public function assignedVehicleLists(Request $request)
    {
        $pageTitle = 'Vehicle Assignment';
        $search = trim((string) $request->search);
        $dispatchStatuses = $this->vehicleDispatchStatuses();
        $trips = Trip::query()
            ->active()
            ->with([
                'route.startFrom',
                'route.endTo',
                'schedule',
                'fleetType.activeVehicles',
                'assignedVehicle.vehicle',
            ])
            ->withMin('schedule as earliest_start', 'start_from')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($tripQuery) use ($search) {
                    $tripQuery->where('title', 'like', "%{$search}%")
                        ->orWhereHas('route', fn ($route) => $route->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('fleetType', fn ($fleet) => $fleet->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('assignedVehicle.vehicle', function ($vehicle) use ($search) {
                            $vehicle->where('nick_name', 'like', "%{$search}%")
                                ->orWhere('register_no', 'like', "%{$search}%")
                                ->orWhere('bus_no', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('earliest_start')
            ->orderBy('title')
            ->paginate(getPaginate())
            ->withQueryString();

        return view('admin.trip.assigned_vehicle', compact('pageTitle', 'trips', 'search', 'dispatchStatuses'));
    }

    public function assignVehicle(Request $request, $id = 0)
    {
        $tripId = (int) ($id ?: $request->trip_id);
        $trip = Trip::with(['schedule', 'fleetType'])->findOrFail($tripId);
        $validated = $request->validate([
            'vehicle_id' => 'required|integer|gt:0',
            'dispatch_status' => ['required', Rule::in(array_keys($this->vehicleDispatchStatuses()))],
            'remarks' => 'nullable|string|max:1000',
        ]);

        if ($trip->trip_status === Status::TRIP_CANCELLED) {
            throw ValidationException::withMessages([
                'dispatch_status' => 'A cancelled trip must be managed from Trip Management.',
            ]);
        }

        $vehicle = Vehicle::where('id', $validated['vehicle_id'])
            ->where('fleet_type_id', $trip->fleet_type_id)
            ->where('status', Status::ENABLE)
            ->first();

        if (!$vehicle) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Select an active vehicle that matches the trip bus type.',
            ]);
        }

        $assignedVehicle = AssignedVehicle::firstOrNew(['trip_id' => $trip->id]);

        $start_time = Carbon::parse($trip->schedule->start_from)->format('H:i:s');
        $end_time = Carbon::parse($trip->schedule->end_at)->format('H:i:s');

        $vehicle_check = AssignedVehicle::where('vehicle_id', $vehicle->id)
            ->when($assignedVehicle->exists, fn ($query) => $query->where('id', '!=', $assignedVehicle->id))
            ->where(function ($query) use ($start_time, $end_time) {
                $query->whereBetween('start_from', [$start_time, $end_time])
                    ->orWhereBetween('end_at', [$start_time, $end_time])
                    ->orWhere(function ($overlap) use ($start_time, $end_time) {
                        $overlap->where('start_from', '<=', $start_time)
                            ->where('end_at', '>=', $end_time);
                    });
            })
            ->first();

        if ($vehicle_check) {
            $notify[] = ['error', 'This vehicle is already assigned to another trip during this schedule.'];
            return back()->withNotify($notify);
        }

        DB::transaction(function () use ($assignedVehicle, $trip, $vehicle, $validated) {
            $assignedVehicle->trip_id = $trip->id;
            $assignedVehicle->vehicle_id = $vehicle->id;
            $assignedVehicle->start_from = $trip->schedule->start_from;
            $assignedVehicle->end_at = $trip->schedule->end_at;
            $assignedVehicle->remarks = $validated['remarks'] ?? null;
            $assignedVehicle->status = Status::ENABLE;
            $assignedVehicle->save();

            $trip->trip_status = $validated['dispatch_status'];
            $trip->save();
        });

        app(ScheduleBoardBroadcaster::class)->dispatchStatusUpdated([
            'trip_id' => $trip->id,
            'counter_id' => $trip->start_from,
            'dispatch_status' => $trip->trip_status,
        ]);

        $notify[] = ['success', 'Vehicle assignment and dispatch status saved successfully.'];
        return back()->withNotify($notify);
    }

    private function vehicleDispatchStatuses(): array
    {
        return [
            Status::TRIP_ON_TIME => 'Scheduled',
            Status::TRIP_BOARDING => 'Boarding',
            Status::TRIP_DEPARTED => 'Departed',
            Status::TRIP_DELAYED => 'Delayed',
            Status::TRIP_ARRIVED => 'Arrived',
        ];
    }


    public function reservationSlip($id = null)
    {
        $ticket = BookedTicket::find($id);

        $dir = 'assets/admin/contents/';
        $file = "{$dir}reservation-slip-$ticket->pickup_point.json";
        if (!file_exists($file)) {
            if (!is_dir($dir)) {
                mkdir($dir);
            }
        }
        $fileContent = @file_get_contents($file);
        $terms_content = json_decode($fileContent);

        $pdf = Pdf::setOptions([
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
        ])->loadView('admin.pdf.reservation-slip', ['ticket' => $ticket, 'pageTitle' => "Reservation Slip", 'content' => $terms_content]);

        $pdf->setPaper([0, 0, 144, 500], 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Reservation Slip.pdf"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function manifestSeatLayout(Request $request, $trip_id)
    {
        $request->validate([
            'date_of_journey' => 'nullable|date',
            'search' => 'nullable|string|max:100',
        ]);
        $date = $request->date_of_journey
            ? Carbon::parse($request->date_of_journey)->format('Y-m-d')
            : now()->format('Y-m-d');
        $search = trim((string) $request->search);

        $trip = Trip::with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo', 'assignedVehicle.vehicle'])
            ->where('status', Status::ENABLE)->where('id', $trip_id)
            ->firstOrFail();

        $bookings = BookedTicket::where('trip_id', $trip->id)
            ->whereDate('date_of_journey', $date)
            ->whereIn('status', [Status::BOOKED_APPROVED, Status::BOOKED_PENDING])
            ->with([
                'activeSlipSeriesNumbers',
                'deposit.userDiscount',
                'user',
                'pickup',
                'drop',
            ])
            ->get();
        $seatManifest = collect();

        foreach ($bookings as $booking) {
            $passengerName = $booking->deposit?->userDiscount?->passenger_name
                ?: $booking->user?->fullname
                ?: 'Guest';
            $passengerType = getPassengerType($booking->deposit);

            foreach ($booking->activeSlipSeriesNumbers as $slip) {
                $haystack = strtolower(implode(' ', [
                    $slip->seat,
                    $slip->id,
                    $passengerName,
                    $passengerType,
                ]));
                $seatManifest->put($slip->seat, [
                    'seat' => $slip->seat,
                    'passenger_name' => $passengerName,
                    'passenger_type' => $passengerType,
                    'reference' => $slip->id,
                    'destination' => $booking->drop?->name,
                    'km_post' => $booking->drop?->km_post,
                    'blocked' => $booking->status === Status::BOOKED_PENDING,
                    'matches' => $search === '' || str_contains($haystack, strtolower($search)),
                ]);
            }
        }

        $manifestDecks = $this->manifestDecks($trip->fleetType);
        $capacity = collect($manifestDecks)
            ->flatten(1)
            ->where('type', 'seat')
            ->count();
        $disabled = array_values((array) ($trip->fleetType->disabled_seats ?? []));
        $bookedCount = $seatManifest->where('blocked', false)->count();
        $blockedCount = $seatManifest->where('blocked', true)->count();
        $stats = [
            'capacity' => $capacity,
            'booked' => $bookedCount,
            'blocked' => $blockedCount,
            'vacant' => max($capacity - $bookedCount - $blockedCount - count($disabled), 0),
            'discounted' => $seatManifest->filter(fn ($seat) => str_contains(strtolower($seat['passenger_type']), 'senior')
                || str_contains(strtolower($seat['passenger_type']), 'pwd'))->count(),
            'matches' => $seatManifest->where('matches', true)->count(),
        ];

        return view('admin.pdf.manifest-seat-layout', [
            'trip' => $trip,
            'date' => $date,
            'search' => $search,
            'seatManifest' => $seatManifest,
            'manifestDecks' => $manifestDecks,
            'disabledSeats' => $disabled,
            'stats' => $stats,
        ]);
    }

    private function manifestDecks($fleetType): array
    {
        $layout = array_map('intval', explode('x', str_replace(' ', '', (string) $fleetType->seat_layout)));
        $left = $layout[0] ?? 0;
        $center = count($layout) === 3 ? ($layout[1] ?? 0) : 0;
        $right = count($layout) === 2 ? ($layout[1] ?? 0) : ($layout[2] ?? 0);
        $seatsPerRow = $left + $center + $right;
        $crOffset = match (strtolower((string) $fleetType->cr_position)) {
            'left' => $left > 0 ? 1 : null,
            'center' => $center > 0 ? $left + 1 : null,
            'right' => $right > 0 ? $left + $center + 1 : null,
            default => null,
        };
        $crSlot = $seatsPerRow > 0 && $crOffset && (int) $fleetType->cr_row > 0
            ? (((int) $fleetType->cr_row - 1) * $seatsPerRow) + $crOffset
            : null;
        $prefixes = array_values((array) ($fleetType->prefixes ?? []));
        $decks = [];

        foreach (array_values((array) $fleetType->deck_seats) as $deckIndex => $seatCount) {
            $prefix = (string) ($prefixes[$deckIndex] ?? '');
            $cells = [];

            for ($number = 1; $number <= (int) $seatCount; $number++) {
                $label = $prefix . $number;
                $cells[] = [
                    'type' => 'seat',
                    'label' => $label,
                    'seat_id' => ($deckIndex + 1) . '-' . $label,
                ];
            }

            if ($deckIndex === 0 && $crSlot && $crSlot <= (int) $seatCount) {
                $crCell = ['type' => 'cr', 'label' => 'CR', 'seat_id' => null];

                if ($fleetType->cr_override_seat) {
                    $coveredRows = max((int) $fleetType->cr_row_covered, 1);
                    $coveredSlots = [];
                    for ($row = 0; $row < $coveredRows; $row++) {
                        $coveredSlot = $crSlot + ($row * $seatsPerRow);
                        if ($coveredSlot <= (int) $seatCount) {
                            $coveredSlots[] = $coveredSlot;
                        }
                    }

                    rsort($coveredSlots);
                    foreach ($coveredSlots as $coveredSlot) {
                        array_splice($cells, $coveredSlot - 1, 1);
                    }
                    array_splice($cells, $crSlot - 1, 0, [$crCell]);
                } else {
                    array_splice($cells, $crSlot - 1, 0, [$crCell]);
                }
            }

            $decks[] = $cells;
        }

        return $decks;
    }

    public function changeAllStatus(Request $request)
    {
        $trip = Trip::whereNot('status', $request->status)->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => "All trips have been updated successfully.",
        ]);
        // if ($trip) {
        //     return response()->json([
        //         'success' => true,
        //         'message' => "All trips have been disabled successfully.",
        //     ]);
        // }

        // return response()->json([
        //     'message' => "Updating failed.",
        // ], 500);
    }


}
