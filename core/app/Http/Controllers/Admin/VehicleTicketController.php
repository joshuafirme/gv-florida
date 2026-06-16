<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\BusLayout;
use App\Models\Admin;
use App\Models\BookedTicket;
use App\Models\Counter;
use App\Models\FleetType;
use App\Models\SlipSeriesNumber;
use App\Models\Trip;
use App\Models\VehicleRoute;
use App\Models\TicketPrice;
use App\Models\TicketPriceByStoppage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleTicketController extends Controller
{
    public function booked()
    {
        $pageTitle = 'Booked Ticket';
        $tickets = BookedTicket::booked()->with(['trip', 'pickup', 'drop', 'user'])->paginate(getPaginate());
        return view('admin.ticket.log', compact('pageTitle', 'tickets'));
    }

    public function pending()
    {
        $pageTitle = 'Pending Ticket';
        $tickets = BookedTicket::pending()->with(['trip', 'pickup', 'drop', 'user'])->paginate(getPaginate());
        return view('admin.ticket.log', compact('pageTitle', 'tickets'));
    }

    public function rejected()
    {
        $pageTitle = 'Rejected Ticket';
        $tickets = BookedTicket::rejected()->with(['trip', 'pickup', 'drop', 'user'])->paginate(getPaginate());
        return view('admin.ticket.log', compact('pageTitle', 'tickets'));
    }

    public function list()
    {
        $pageTitle = 'All Ticket';
        $tickets = BookedTicket::with(['trip', 'pickup', 'drop', 'user'])->paginate(getPaginate());
        return view('admin.ticket.log', compact('pageTitle', 'tickets'));
    }

    public function search(Request $request, $scope)
    {
        $search = $request->search;
        $pageTitle = '';

        $ticket = BookedTicket::where('pnr_number', $search);
        switch ($scope) {
            case 'pending':
                $pageTitle .= 'Pending Ticket Search';
                break;
            case 'booked':
                $pageTitle .= 'Booked Ticket Search';
                break;
            case 'rejected':
                $pageTitle .= 'Rejected Ticket Search';
                break;
            case 'list':
                $pageTitle .= 'Ticket Booking History Search';
                break;
        }
        $tickets = $ticket->with(['trip', 'pickup', 'drop', 'user'])->paginate(getPaginate());
        $pageTitle .= ' - ' . $search;

        return view('admin.ticket.log', compact('pageTitle', 'search', 'scope', 'tickets'));
    }

    public function ticketPriceList()
    {
        $pageTitle = "All Ticket Price";
        $fleetTypes = FleetType::active()->get();
        $routes = VehicleRoute::active()->get();
        $query = TicketPrice::with(['fleetType', 'route.startFrom']);

        $searchTerm = request('search');

        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {

                // Search route start location
                $q->whereHas('route.startFrom', function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%");
                });

                // OPTIONAL: search route end location
                $q->orWhereHas('route.endTo', function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%");
                });

                // OPTIONAL: search fleet type
                $q->orWhereHas('fleetType', function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%");
                });

            });
        }

        $prices = $query
            ->orderByDesc('id')
            ->paginate(getPaginate());

        return view('admin.trip.ticket.price_list', compact('pageTitle', 'prices', 'fleetTypes', 'routes'));
    }

   public function ticketPriceForm($id = null)
{
    $pageTitle = $id ? "Update Ticket Price Configuration" : "Ticket Price Configuration";
    
    $fleetTypes = FleetType::active()->get();
    
    // Fetch routes and ensure stoppages are available for JavaScript
    $routes = VehicleRoute::active()->get();
    
    // Fetch all counters to map stoppage IDs to Names and KM Posts in the frontend
    $counters = Counter::active()->get(); 
    
    // If an ID is passed, fetch the existing ticket price and its relationships
    $ticketPrice = null;
    $existingPrices = [];
    if ($id) {
        $ticketPrice = TicketPrice::findOrFail($id);
        
        // Map existing prices into a key-value pair: ['start-end' => price] for easy JS lookup
        $prices = TicketPriceByStoppage::where('ticket_price_id', $id)->get();
        foreach ($prices as $p) {
            $key = $p->source_destination[0] . '-' . $p->source_destination[1];
            $existingPrices[$key] = $p->price;
        }
    }

    return view('admin.trip.ticket.price_form', compact(
        'pageTitle', 
        'fleetTypes', 
        'routes', 
        'counters', 
        'ticketPrice',
        'existingPrices'
    ));
}

    public function ticketPriceEdit($id)
    {
        $pageTitle = "Update Ticket Price";
        $ticketPrice = TicketPrice::with(['prices', 'route.startFrom', 'route.endTo'])->findOrFail($id);
        $stoppageArr = $ticketPrice->route->stoppages;
        $stoppages = stoppageCombination($stoppageArr, 2);
        return view('admin.trip.ticket.edit_price', compact('pageTitle', 'ticketPrice', 'stoppages'));
    }

    public function getRouteData(Request $request)
    {
        $route = VehicleRoute::active()->where('id', $request->vehicle_route_id)->first();
        $check = TicketPrice::where('vehicle_route_id', $request->vehicle_route_id)->where('fleet_type_id', $request->fleet_type_id)->first();

        if ($check) {
            return response()->json(['error' => trans('You have added prices for this fleet type on this route')]);
        }

        $stoppages = array_values($route->stoppages);
        $stoppages = stoppageCombination($stoppages, 2);
        return view('admin.trip.ticket.route_data', compact('stoppages', 'route'));
    }



    public function ticketPriceStore(Request $request)
    {
        $request->validate([
            'fleet_type' => 'required|integer|gt:0',
            'route'      => 'required|integer|gt:0',
            'main_price' => 'required|numeric|min:0',
            'price'      => 'required|array|min:1',
            'price.*'    => 'required|numeric|min:0',
        ], [
            'main_price.required' => 'Price for Source to Destination is required.',
            'price.*.required'    => 'All Stoppage Ticket Prices are required.',
            'price.*.numeric'     => 'All Stoppage Ticket Prices must be a valid number.',
        ]);

        // Duplicate Check
        $check = TicketPrice::where('fleet_type_id', $request->fleet_type)
                            ->where('vehicle_route_id', $request->route)
                            ->exists();
                            
        if ($check) {
            $notify[] = ['error', 'Ticket price for this Bus Type and Route already exists.'];
            return back()->withNotify($notify)->withInput();
        }

        // 1. Create Main Ticket Price
        $ticketPrice = new TicketPrice();
        $ticketPrice->fleet_type_id = $request->fleet_type;
        $ticketPrice->vehicle_route_id = $request->route;
        $ticketPrice->price = $request->main_price;
        $ticketPrice->save();

        // 2. Loop through dynamic table and create Stoppage Prices
        foreach ($request->price as $key => $val) {
            $idArray = explode('-', $key);
            
            $priceByStoppage = new TicketPriceByStoppage();
            $priceByStoppage->ticket_price_id = $ticketPrice->id;
            // Ensure IDs are strictly stored as strings in the JSON array to match your schema
            $priceByStoppage->source_destination = [(string)$idArray[0], (string)$idArray[1]]; 
            $priceByStoppage->price = $val;
            $priceByStoppage->save();
        }

        $notify[] = ['success', 'Ticket price configured successfully'];
        return back()->withNotify($notify);
    }

    public function ticketPriceUpdate(Request $request, $id)
    {
        $request->validate([
            'fleet_type' => 'required|integer|gt:0',
            'route'      => 'required|integer|gt:0',
            'main_price' => 'required|numeric|min:0',
            'price'      => 'required|array|min:1',
            'price.*'    => 'required|numeric|min:0',
        ], [
            'main_price.required' => 'Price for Source to Destination is required.',
            'price.*.required'    => 'All Stoppage Ticket Prices are required.',
            'price.*.numeric'     => 'All Stoppage Ticket Prices must be a valid number.',
        ]);

        $ticketPrice = TicketPrice::findOrFail($id);

        // Duplicate Check (Must exclude the current ticket price ID)
        $check = TicketPrice::where('fleet_type_id', $request->fleet_type)
                            ->where('vehicle_route_id', $request->route)
                            ->where('id', '!=', $id)
                            ->exists();
                            
        if ($check) {
            $notify[] = ['error', 'Ticket price for this Bus Type and Route already exists.'];
            return back()->withNotify($notify)->withInput();
        }

        // 1. Update Main Ticket Price
        $ticketPrice->fleet_type_id = $request->fleet_type;
        $ticketPrice->vehicle_route_id = $request->route;
        $ticketPrice->price = $request->main_price; // Now correctly takes the mirrored destination price
        $ticketPrice->save();

        // 2. Sync Stoppage Prices (Fixes the "Ghost Record" & "0 Price" bugs)
        // By wiping the old records and replacing them, we ensure perfect synchronization 
        // in case the Admin previously removed an intermediate stop from the Route.
        TicketPriceByStoppage::where('ticket_price_id', $ticketPrice->id)->delete();

        foreach ($request->price as $key => $val) {
            $idArray = explode('-', $key);
            
            $priceByStoppage = new TicketPriceByStoppage();
            $priceByStoppage->ticket_price_id = $ticketPrice->id;
            $priceByStoppage->source_destination = [(string)$idArray[0], (string)$idArray[1]];
            $priceByStoppage->price = $val; // Now safely extracts the numeric value out of the array
            $priceByStoppage->save();
        }

        $notify[] = ['success', 'Ticket price updated successfully'];
        return back()->withNotify($notify);
    }

    public function ticketPriceDelete($id)
    {

        $data = TicketPrice::findOrFail($id);
        $data->prices()->delete();
        $data->delete();

        $notify[] = ['success', 'Price Deleted Successfully'];
        return redirect()->back()->withNotify($notify);
    }

    public function updateBookingDate(Request $request, $id)
    {

        $admin = Admin::where('username', $request->username)
            ->where('passcode', $request->passcode)
            ->first();

        $is_authorized = isset($admin->id) ? true : false;
        $message = $is_authorized ? 'Authorization success!' : 'Invalid username or passcode!';

        if ($is_authorized) {
            $request->validate([
                'date_of_journey' => 'required|date|after_or_equal:today',
            ]);
        } else {
            return redirect()->back()->withErrors(['authorization' => $message]);
        }
        $request->validate([
            'date_of_journey' => 'required|date|after_or_equal:today',
            'seats' => 'required|string', // Comma-separated string from JS hidden input
        ]);

        $data = BookedTicket::with([
            'trip' => function ($q) {
                $q->with('schedule');
            }
        ])->findOrFail($id);

        $requestedSeats = explode(',', $request->seats);

        $originalSeatCount = is_array($data->seats) ? count($data->seats) : 1;

        if (count($requestedSeats) !== $originalSeatCount) {
            return redirect()->back()->withErrors(['seats' => "You must select exactly {$originalSeatCount} seat(s)."]);
        }

        // B. Fetch already booked seats for the new date and same schedule
        $bookedTicketsData = BookedTicket::query()
            ->where('id', '!=', $id) // Exclude current ticket to avoid self-conflict
            ->whereIn('status', [Status::BOOKED_APPROVED, Status::BOOKED_PENDING])
            ->whereDate('date_of_journey', Carbon::parse($request->date_of_journey)->format('Y-m-d'))
            ->whereHas('trip', function ($query) use ($data) {
                $query->where('fleet_type_id', $data->trip->fleet_type_id)
                    ->where('start_from', $data->trip->start_from);

                $query->whereHas('schedule', function ($q) use ($data) {
                    $q->where('start_from', $data->trip->schedule->start_from);
                });
            })
            ->get(['seats']);

        // Flatten all booked seats into a single array
        $bookedSeatsArray = [];
        foreach ($bookedTicketsData as $bookedTicket) {
            $seats = is_string($bookedTicket->seats) ? json_decode($bookedTicket->seats, true) : $bookedTicket->seats;
            if (is_array($seats)) {
                foreach ($seats as $seat) {
                    if (str_contains($seat, '-')) {
                        $seat_parts = explode('-', $seat);
                        $bookedSeatsArray[] = $seat_parts[1];
                    } else {
                        $bookedSeatsArray[] = $seat;
                    }
                }
            }
        }
        $bookedSeatsArray = array_unique($bookedSeatsArray);

        // C. Fetch permanently disabled seats for this fleet
        $fleetType = FleetType::find($data->trip->fleet_type_id);
        $disabledSeats = $fleetType->disabled_seats ? $fleetType->disabled_seats : [];

        // Combine booked and disabled seats to check against
        $unavailableSeats = array_merge($bookedSeatsArray, $disabledSeats);

        // D. Check for overlaps (if any requested seat is inside the unavailable array)
        $conflict = array_intersect($requestedSeats, $unavailableSeats);
        if (!empty($conflict)) {
            $conflictStr = implode(', ', $conflict);
            return redirect()->back()->withErrors(['seats' => "The following seats are already booked or unavailable on this date: {$conflictStr}"]);
        }
        // ----------------------------------------

        // 4. Save the update
        $data->date_of_journey = $request->date_of_journey;
        $data->is_rebooked = 1;
        $data->seats = $requestedSeats;
        $data->save();

        $slips = $data->slipSeriesNumbers;

        foreach ($slips as $key => $slip) {
            if (isset($requestedSeats[$key])) {
                $slip->seat = $requestedSeats[$key];
                $slip->save();
            }
        }

        $notify[] = ['success', "Booking Date and Seats Updated Successfully"];
        return redirect()->back()->withNotify($notify);
    }

    public function cancelBooking($id)
    {
        $data = BookedTicket::findOrFail($id);
        $data->status = Status::BOOKED_REJECTED;
        $data->save();

        $notify[] = ['success', "Booking Cancelled Successfully"];
        return redirect()->back()->withNotify($notify);
    }

    public function getSeatLayout(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|integer',
            'date' => 'required|date'
        ]);

        // 1. Fetch the original ticket to get trip and schedule parameters
        $ticket = BookedTicket::with([
            'trip' => function ($q) {
                $q->with('schedule');
            }
        ])->findOrFail($request->ticket_id);

        // How many seats does the passenger need to rebook?
        $requiredSeatsCount = is_array($ticket->seats) ? count($ticket->seats) : 1;

        // 2. Run your existing checker for the NEW date
        $bookedTicketsData = BookedTicket::whereIn('status', [Status::BOOKED_APPROVED, Status::BOOKED_PENDING])
            ->where('id', '!=', $request->ticket_id)
            ->whereDate('date_of_journey', Carbon::parse($request->date)->format('Y-m-d'))
            ->where('trip_id', $ticket->trip_id)
            ->get(['seats']);

        // 3. Extract and flatten all booked seat numbers into a single 1D array
        $bookedSeatsArray = [];
        foreach ($bookedTicketsData as $bookedTicket) {
            $seats = is_string($bookedTicket->seats) ? json_decode($bookedTicket->seats, true) : $bookedTicket->seats;
            if (is_array($seats)) {
                $bookedSeatsArray = array_merge($bookedSeatsArray, $seats);
            }
        }

        // 4. Fetch dependencies for the Blade partial
        $fleetType = FleetType::findOrFail($ticket->trip->fleet_type_id);
        // Instantiate your BusLayout service here so the blade template can use it
        $trip = Trip::with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo', 'assignedVehicle.vehicle', 'bookedTickets'])
            ->where('status', Status::ENABLE)
            ->where('id', $ticket->trip_id)
            ->firstOrFail();

        $busLayout = new BusLayout($trip); // Adjust namespace based on your app

        // 5. Render the HTML view
        $html = view('templates.basic.partials.seat_layout', compact('fleetType', 'busLayout'))->render();

        $disabled_seats = $fleetType->disabled_seats ? $fleetType->disabled_seats : [];
        $seats = [];

        foreach ($bookedSeatsArray as $seat) {
            if (str_contains($seat, '-')) {
                $seat_parts = explode('-', $seat);
                $seats[] = $seat_parts[1];
            } else {
                $seats[] = $seat; // Fallback just in case
            }
        }

        return response()->json([
            'status' => 'success',
            'html' => $html,
            'booked_seats' => array_unique($seats),
            'disabled_seats' => $disabled_seats,
            'required_seats' => $requiredSeatsCount
        ]);
    }

    public function checkTicketPrice(Request $request)
    {
        $check = TicketPrice::where('vehicle_route_id', $request->vehicle_route_id)->where('fleet_type_id', $request->fleet_type_id)->first();

        if (!$check) {
            return response()->json(['error' => 'Ticket price not added for this fleet-route combination yet. Please add ticket price before creating a trip.']);
        }
    }
}
