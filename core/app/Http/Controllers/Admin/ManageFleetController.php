<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\BusLayout;
use Illuminate\Http\Request;
use App\Models\SeatLayout;
use App\Models\FleetType;
use App\Models\Vehicle;

class ManageFleetController extends Controller
{
    public function layout()
    {
        $pageTitle = 'Seat Layouts';
        $layouts = SeatLayout::orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.fleet.seat_layouts', compact('pageTitle', 'layouts'));
    }

    public function seatLayoutDetails($id)
    {
        $pageTitle = 'Seat Layout Preview';
        $fleetType = FleetType::find($id);
        // return $fleetType;
        $busLayout = new BusLayout(null, $fleetType);
        return view('admin.fleet.seat_layout_details', compact('pageTitle', 'fleetType', 'busLayout'));
    }

    public function layoutStore(Request $request, $id = 0)
    {

        $request->validate([
            'layout' => 'required|unique:seat_layouts,layout,' . $id
        ]);

        if ($id) {
            $layout = SeatLayout::findOrFail($id);
            $message = "Seat layout updated successfully";
        } else {
            $layout = new SeatLayout();
            $message = "Seat layout created successfully";
        }

        $layout->layout = $request->layout;
        $layout->save();

        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }

    public function removeLayout($id)
    {

        $layout = SeatLayout::findOrFail($id);
        $layout->delete();

        $notify[] = ['success', 'Seat layout removed successfully'];
        return back()->withNotify($notify);
    }

    public function type()
    {
        $pageTitle = 'Fleet Type';
        $seatLayouts = SeatLayout::all();
        $fleetType = FleetType::orderBy('id', 'desc')->paginate(getPaginate());
        $facilities = getContent('amenities.element');
        return view('admin.fleet.type', compact('pageTitle', 'fleetType', 'seatLayouts', 'facilities'));
    }

    public function typeStore(Request $request, $id = 0)
    {
        $request->validate(
            [
                'name' => 'required|unique:fleet_types,name,' . $id,
                'seat_layout' => 'required',
                'deck' => 'required|numeric|gt:0',
                'deck_seats' => 'required|array|min:1',
                'deck_seats.*' => 'required|numeric|gt:0',
                'last_row.*' => 'numeric',
                'prefixes' => 'array',
                'facilities.*' => 'string'
            ],
            [
                'deck_seats.*.required' => 'Seat number for all deck is required',
                'deck_seats.*.numeric' => 'Seat number for all deck is must be a number',
                'deck_seats.*.gt:0' => 'Seat number for all deck is must be greater than 0',
            ],
            [
                'last_row.*.numeric' => 'Last Row number for all deck is must be a number',
            ],
        );

        if ($id) {
            $fleetType = FleetType::findOrFail($id);
            $message = "Fleet type updated successfully";
        } else {
            $fleetType = new FleetType();
            $message = "Fleet type added successfully";
        }

        $fleetType->name = $request->name;
        $fleetType->seat_layout = $request->seat_layout;
        $fleetType->deck = $request->deck;
        $fleetType->deck_seats = $request->deck_seats;
        $fleetType->last_row = $request->last_row;
        $fleetType->cr_row = $request->cr_row;
        $fleetType->cr_position = $request->cr_position;
        $fleetType->prefixes = $request->prefixes;
        $fleetType->has_ac = $request->has_ac ? Status::ENABLE : Status::DISABLE;
        $fleetType->facilities = $request->facilities ?? null;
        $fleetType->disabled_seats = $request->disabled_seats ?? null;
        $fleetType->status = Status::ENABLE;
        $fleetType->save();

        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }

    public function typeStatus($id)
    {
        return FleetType::changeStatus($id);
    }


    public function vehicles()
    {
        $pageTitle = 'All Vehicles';
        $fleetType = FleetType::where('status', Status::ENABLE)->orderBy('id', 'desc')->get();
        $vehicles = Vehicle::searchable(['nick_name'])->with('fleetType')->orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.fleet.vehicles', compact('pageTitle', 'vehicles', 'fleetType'));
    }

    public function vehiclesStore(Request $request, $id = 0)
    {

        $request->validate([
            'nick_name' => 'required|string',
            'fleet_type_id' => 'required|numeric',
            'register_no' => 'required|string|unique:vehicles,register_no,' . $id,
            'engine_no' => 'required|string|unique:vehicles,engine_no,' . $id,
            'model_no' => 'required|string',
            'chasis_no' => 'required|string|unique:vehicles,chasis_no,' . $id,
        ]);

        if ($id) {
            $vehicle = Vehicle::findOrFail($id);
            $message = "Vehicle updated successfully";
        } else {
            $vehicle = new Vehicle();
            $message = "Vehicle added successfully";
        }

        $vehicle->nick_name = $request->nick_name;
        $vehicle->fleet_type_id = $request->fleet_type_id;
        $vehicle->register_no = $request->register_no;
        $vehicle->engine_no = $request->engine_no;
        $vehicle->chasis_no = $request->chasis_no;
        $vehicle->model_no = $request->model_no;
        $vehicle->save();

        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }

    public function vehicleStatus($id)
    {
        return Vehicle::changeStatus($id);
    }
}
