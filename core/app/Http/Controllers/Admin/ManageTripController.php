<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssignedVehicle;
use Illuminate\Http\Request;
use App\Models\VehicleRoute;
use App\Models\Counter;
use App\Models\FleetType;
use App\Models\Schedule;
use App\Models\Trip;
use Carbon\Carbon;

class ManageTripController extends Controller
{
    public function routeList()
    {
        $pageTitle = 'All Routes';
        $routes = VehicleRoute::searchable(['name'])->with(['startFrom', 'endTo'])->orderBy('id', 'desc')->paginate(getPaginate());
        $stoppages = Counter::active()->get();
        return view('admin.trip.route.list', compact('pageTitle', 'routes', 'stoppages'));
    }

    public function routeCreate()
    {
        $pageTitle = 'Create Route';
        $stoppages = Counter::active()->get();
        return view('admin.trip.route.create', compact('pageTitle', 'stoppages'));
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
        $route->stoppages  = array_unique($stoppages);
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
        $allStoppages = Counter::active()->get();

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

        $route = VehicleRoute::findOrFail($id);
        $route->name = $request->name;
        $route->start_from = $request->start_from;
        $route->end_to = $request->end_to;
        $route->stoppages  = array_unique($stoppages);
        $route->distance = $request->distance;
        $route->time = $request->time;
        $route->save();

        $notify[] = ['success', 'Route update successfully'];
        return back()->withNotify($notify);
    }

    public function routeStatus($id)
    {
        return VehicleRoute::changeStatus($id);
    }

    public function schedules()
    {
        $pageTitle = 'All Schedules';
        $schedules = Schedule::orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.trip.schedule', compact('pageTitle', 'schedules'));
    }

    public function scheduleStore(Request $request, $id = 0)
    {
        $request->validate([
            'start_from'   => 'required',
            'end_at'       => 'required',
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

    public function trips()
    {
        $pageTitle = "All Trip";
        $emptyMessage = "No trip found";
        $fleetTypes = FleetType::where('status', 1)->get();
        $routes = VehicleRoute::where('status', 1)->get();
        $schedules = Schedule::where('status', 1)->get();
        $stoppages = Counter::where('status', 1)->get();

        $trips = Trip::with(['fleetType', 'route', 'schedule'])->searchable(['title'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.trip.trip', compact('pageTitle', 'emptyMessage', 'trips', 'fleetTypes', 'routes', 'schedules', 'stoppages'));
    }

    public function tripStore(Request $request, $id = 0)
    {
        $request->validate([
            'title'             => 'required',
            'fleet_type_id'     => 'required|integer|gt:0',
            'vehicle_route_id'  => 'required|integer|gt:0',
            'schedule_id'       => 'required|integer|gt:0',
            'start_from'        => 'required|integer|gt:0',
            'end_to'            => 'required|integer|gt:0',
            'day_off'           => 'nullable|array|min:1'
        ]);

        if ($id) {
            $trip = Trip::findOrFail($id);
            $message = 'Trip updated successfully';
        } else {
            $trip = new Trip();
            $message = 'Trip created successfully';
        }

        $trip->title            = $request->title;
        $trip->fleet_type_id    = $request->fleet_type_id;
        $trip->vehicle_route_id = $request->vehicle_route_id;
        $trip->schedule_id      = $request->schedule_id;
        $trip->start_from       = $request->start_from;
        $trip->end_to           = $request->end_to;
        $trip->day_off          = $request->day_off ?? [];
        $trip->save();

        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }

    public function tripStatus($id)
    {
        return Trip::changeStatus($id);
    }

    public function assignedVehicleLists()
    {
        $pageTitle = "All Assigned Vehicles";
        $trips = Trip::with('fleetType.activeVehicles')->where('status', 1)->get();
        $assignedVehicles = AssignedVehicle::with(['trip', 'vehicle'])->searchable(['trip:title', 'vehicle:nick_name'])->orderBy('id', 'desc')->paginate(getPaginate());

        return view('admin.trip.assigned_vehicle', compact('pageTitle', 'trips', 'assignedVehicles'));
    }

    public function assignVehicle(Request $request, $id = 0)
    {
        $request->validate([
            'trip_id'      => 'required|integer|gt:0',
            'vehicle_id' => 'required|integer|gt:0'
        ]);

        //Check if the trip has already a assigned vehicle;
        $trip_check = AssignedVehicle::where('trip_id', $request->trip_id)->first();

        if ($trip_check) {
            $notify[] = ['error', 'A vehicle had already been assigned to this trip'];
            return back()->withNotify($notify);
        }

        $trip = Trip::where('id', $request->trip_id)->with('schedule')->firstOrFail();

        $start_time = Carbon::parse($trip->schedule->start_from)->format('H:i:s');
        $end_time   = Carbon::parse($trip->schedule->end_at)->format('H:i:s');

        //Check if the vehicle assigned to another vehicle on this time
        $vehicle_check = AssignedVehicle::where(function ($q) use ($start_time, $end_time, $request) {
            $q->where('start_from', '>=', $start_time)
                ->where('start_from', '<=', $end_time)
                ->where('vehicle_id', $request->vehicle_id);
        })
            ->orWhere(function ($q) use ($start_time, $end_time, $request) {
                $q->where('end_at', '>=', $start_time)
                    ->where('end_at', '<=', $end_time)
                    ->where('vehicle_id', $request->vehicle_id);
            })
            ->first();


        if ($vehicle_check) {
            $notify[] = ['error', 'This vehicle had already been assigned to another trip on this time'];
            return back()->withNotify($notify);
        }

        if ($id) {
            $assignedVehicle = AssignedVehicle::findOrFail($id);
        } else {
            $assignedVehicle = new AssignedVehicle();
        }

        $assignedVehicle->trip_id = $request->trip_id;
        $assignedVehicle->vehicle_id = $request->vehicle_id;
        $assignedVehicle->start_from = $trip->schedule->start_from;
        $assignedVehicle->end_at = $trip->schedule->end_at;
        $assignedVehicle->save();

        $notify[] = ['success', 'Vehicle assigned successfully.'];
        return back()->withNotify($notify);
    }


    public function assignedVehicleStatus($id)
    {
        return AssignedVehicle::changeStatus($id);
    }
}
