<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Counter;
use App\Models\Kiosk;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    public function kiosks()
    {
        $pageTitle = 'All Kiosk';
        $kiosks = Kiosk::searchable(['name', 'uid'])->paginate(getPaginate());
        $counters = Counter::where('status', Status::ENABLE)->get();
        return view('admin.kiosk.list', compact('pageTitle', 'kiosks', 'counters'));
    }

    public function kioskStore(Request $request, $id = 0)
    {

        $request->validate([
            'uid' => 'required|unique:kiosks,uid,' . $id,
        ]);

        if ($id) {
            $kiosk = Kiosk::findOrFail($id);
            $message = 'Kiosk updated successfully';
        } else {
            $kiosk = new Kiosk();
            $message = 'Kiosk added successfully';
        }

        $kiosk->uid = $request->uid;
        $kiosk->name = $request->name;
        $kiosk->counter_id = $request->counter_id;
        $kiosk->save();

        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }

    public function openKiosk(Request $request, $id)
    {
        if ($request->pickup && $request->destination && $request->pickup == $request->destination) {
            $notify[] = ['error', 'Please select pickup point and destination point properly'];
            return redirect()->back()->withNotify($notify);
        }
        if ($request->date_of_journey && Carbon::parse($request->date_of_journey)->format('Y-m-d') < Carbon::now()->format('Y-m-d')) {
            $notify[] = ['error', 'Date of journey can\'t be less than today.'];
            return redirect()->back()->withNotify($notify);
        }

        $trips = Trip::active();

        if ($request->pickup && $request->destination) {
            Session::flash('pickup', $request->pickup);
            Session::flash('destination', $request->destination);

            $pickup = $request->pickup;
            $destination = $request->destination;
            $trips = $trips->with('route')->get();
            $tripArray = array();

            foreach ($trips as $trip) {
                $startPoint = array_search($trip->start_from, array_values($trip->route->stoppages));
                $endPoint = array_search($trip->end_to, array_values($trip->route->stoppages));
                $pickup_point = array_search($pickup, array_values($trip->route->stoppages));
                $destination_point = array_search($destination, array_values($trip->route->stoppages));
                if ($startPoint < $endPoint) {
                    if ($pickup_point >= $startPoint && $pickup_point < $endPoint && $destination_point > $startPoint && $destination_point <= $endPoint) {
                        array_push($tripArray, $trip->id);
                    }
                } else {
                    $revArray = array_reverse($trip->route->stoppages);
                    $startPoint = array_search($trip->start_from, array_values($revArray));
                    $endPoint = array_search($trip->end_to, array_values($revArray));
                    $pickup_point = array_search($pickup, array_values($revArray));
                    $destination_point = array_search($destination, array_values($revArray));
                    if ($pickup_point >= $startPoint && $pickup_point < $endPoint && $destination_point > $startPoint && $destination_point <= $endPoint) {
                        array_push($tripArray, $trip->id);
                    }
                }
            }

            $trips = Trip::active()->whereIn('id', $tripArray);
        } else {
            if ($request->pickup) {
                Session::flash('pickup', $request->pickup);
                $pickup = $request->pickup;
                $trips = $trips->whereHas('route', function ($route) use ($pickup) {
                    $route->whereJsonContains('stoppages', $pickup);
                });
            }

            if ($request->destination) {
                Session::flash('destination', $request->destination);
                $destination = $request->destination;
                $trips = $trips->whereHas('route', function ($route) use ($destination) {
                    $route->whereJsonContains('stoppages', $destination);
                });
            }
        }

        if ($request->fleetType) {
            $trips = $trips->whereIn('fleet_type_id', $request->fleetType);
        }

        if ($request->routes) {
            $trips = $trips->whereIn('vehicle_route_id', $request->routes);
        }

        if ($request->schedules) {
            $trips = $trips->whereIn('schedule_id', $request->schedules);
        }

        if ($request->date_of_journey) {
            Session::flash('date_of_journey', $request->date_of_journey);
            $dayOff = Carbon::parse($request->date_of_journey)->format('w');
            $trips = $trips->whereJsonDoesntContain('day_off', $dayOff);
        }

        $trips = $trips->with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo'])->where('status', Status::ENABLE)->paginate(getPaginate());

        $pageTitle = 'Search Result';
        $emptyMessage = 'There is no trip available';
        $fleetType = FleetType::active()->get();
        $schedules = Schedule::all();
        $routes = VehicleRoute::active()->get();

        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }
        return view('Template::kiosk_booking', compact('pageTitle', 'fleetType', 'trips', 'routes', 'schedules', 'emptyMessage', 'layout'));
    }

    public function status($id)
    {
        return Kiosk::changeStatus($id);
    }
}
