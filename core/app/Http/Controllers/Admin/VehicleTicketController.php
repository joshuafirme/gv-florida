<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use App\Models\FleetType;
use App\Models\VehicleRoute;
use App\Models\TicketPrice;
use App\Models\TicketPriceByStoppage;
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
        $prices = TicketPrice::with(['fleetType', 'route'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.trip.ticket.price_list', compact('pageTitle', 'prices', 'fleetTypes', 'routes'));
    }

    public function ticketPriceCreate()
    {
        $pageTitle = "Add Ticket Price";
        $fleetTypes = FleetType::active()->get();
        $routes = VehicleRoute::active()->get();
        return view('admin.trip.ticket.add_price', compact('pageTitle', 'fleetTypes', 'routes'));
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
        $route      = VehicleRoute::active()->where('id', $request->vehicle_route_id)->first();
        $check      = TicketPrice::where('vehicle_route_id', $request->vehicle_route_id)->where('fleet_type_id', $request->fleet_type_id)->first();

        if ($check) {
            return response()->json(['error' => trans('You have added prices for this fleet type on this route')]);
        }

        $stoppages  = array_values($route->stoppages);
        $stoppages  = stoppageCombination($stoppages, 2);
        return view('admin.trip.ticket.route_data', compact('stoppages', 'route'));
    }



    public function ticketPriceStore(Request $request)
    {
        $validation_rule = [
            'fleet_type'    => 'required|integer|gt:0',
            'route'         => 'required|integer|gt:0',
            'main_price'    => 'required|numeric',
            'price'         => 'sometimes|required|array|min:1',
            'price.*'       => 'sometimes|required|numeric',
        ];
        $messages = [
            'main_price'            => 'Price for Source to Destination',
            'price.*.required'      => 'All Price Fields are Required',
            'price.*.numeric'       => 'All Price Fields Should Be a Number',
        ];

        $validator = Validator::make($request->except('_token'), $validation_rule, $messages);
        $validator->validate();

        $check = TicketPrice::where('fleet_type_id', $request->fleet_type)->where('vehicle_route_id', $request->route)->first();
        if ($check) {
            $notify[] = ['error', 'Duplicate fleet type and route can\'t be allowed'];
            return back()->withNotify($notify);
        }

        $create = new TicketPrice();
        $create->fleet_type_id = $request->fleet_type;
        $create->vehicle_route_id = $request->route;
        $create->price = $request->main_price;
        $create->save();

        foreach ($request->price as $key => $val) {
            $idArray = explode('-', $key);
            $priceByStoppage = new TicketPriceByStoppage();
            $priceByStoppage->ticket_price_id = $create->id;
            $priceByStoppage->source_destination = $idArray;
            $priceByStoppage->price = $val;
            $priceByStoppage->save();
        }
        $notify[] = ['success', 'Ticket price added successfully'];
        return back()->withNotify($notify);
    }

    public function ticketPriceUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'price'   => 'required|numeric|gte:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        if ($id == 0) {
            $sourceDestination[0] = $request->source;
            $sourceDestination[1] = $request->destination;
            $ticketPrice = TicketPriceByStoppage::whereJsonContains('source_destination', $sourceDestination)->where('ticket_price_id', $request->ticket_price)->first();
            if ($ticketPrice) {
                $ticketPrice->price = $request->price;
                $ticketPrice->save();
            } else {
                $ticketPrice = new TicketPriceByStoppage();
                $ticketPrice->ticket_price_id = $request->ticket_price;
                $ticketPrice->source_destination = $sourceDestination;
                $ticketPrice->price = $request->price;
                $ticketPrice->save();
            }
        } else {
            $prices = TicketPriceByStoppage::findOrFail($id);
            $prices->price = $request->price;
            $prices->save();
        }

        $notify = ['success' => true, 'message' => 'Price Updated Successfully'];
        return response()->json($notify);
    }

    public function ticketPriceDelete($id)
    {

        $data = TicketPrice::findOrFail($id);
        $data->prices()->delete();
        $data->delete();

        $notify[] = ['success', 'Price Deleted Successfully'];
        return redirect()->back()->withNotify($notify);
    }

    public function checkTicketPrice(Request $request)
    {
        $check = TicketPrice::where('vehicle_route_id', $request->vehicle_route_id)->where('fleet_type_id', $request->fleet_type_id)->first();

        if (!$check) {
            return response()->json(['error' => 'Ticket price not added for this fleet-route combination yet. Please add ticket price before creating a trip.']);
        }
    }
}
