<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SeatLayout;
use App\Models\FleetType;
use App\Models\Vehicle;
use App\Services\SeatLayoutService;

class ManageFleetController extends Controller
{
    public function layout()
    {
        $pageTitle = 'Seat Layouts';
        $layouts = SeatLayout::orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.fleet.seat_layouts', compact('pageTitle', 'layouts'));
    }

    public function seatLayoutDetails($id, SeatLayoutService $seatLayoutService)
    {
        $pageTitle = 'Seat Layout Preview';
        $fleetType = FleetType::findOrFail($id);
        $seatLayout = $seatLayoutService->layout($fleetType);

        return view('admin.fleet.seat_layout_details', compact('pageTitle', 'fleetType', 'seatLayout'));
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
                'deck' => 'required|integer|min:1',
                'deck_seats' => 'required|array|size:' . $request->integer('deck'),
                'deck_seats.*' => 'required|integer|min:1',
                'last_row' => 'nullable|array|size:' . $request->integer('deck'),
                'last_row.*' => 'nullable|integer|min:0',
                'prefixes' => 'nullable|array|size:' . $request->integer('deck'),
                'prefixes.*' => 'nullable|string|max:10',
                'cr_position' => 'nullable|in:Left,Center,Right',
                'cr_row' => 'nullable|required_with:cr_position|integer|min:1',
                'cr_row_covered' => 'nullable|required_with:cr_position|integer|min:1|max:3',
                'facilities' => 'nullable|array',
                'facilities.*' => 'string',
                'disabled_seats' => 'nullable|array',
                'disabled_seats.*' => 'string|max:30',
            ],
            [
                'deck_seats.*.required' => 'Seat number for all deck is required',
                'deck_seats.*.integer' => 'Seat number for all decks must be a number',
                'deck_seats.*.min' => 'Seat number for all decks must be greater than 0',
                'deck_seats.size' => 'Seat details are required for every deck',
                'last_row.size' => 'Last-row details are required for every deck',
                'prefixes.size' => 'A prefix value is required for every deck',
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
        $fleetType->deck = $request->integer('deck');
        $fleetType->deck_seats = array_map('intval', array_values($request->deck_seats));
        $fleetType->last_row = array_map(
            fn ($value) => max((int) ($value ?: 0), 0),
            array_values($request->last_row ?? array_fill(0, $request->integer('deck'), 0))
        );
        $fleetType->cr_row = $request->cr_position ? $request->integer('cr_row') : null;
        $fleetType->cr_position = $request->cr_position;
        $fleetType->cr_override_seat = $request->boolean('cr_override_seat');
        $fleetType->cr_row_covered = $request->cr_position ? $request->integer('cr_row_covered') : null;
        $fleetType->prefixes = array_map(
            fn ($value) => trim((string) $value),
            array_values($request->prefixes ?? array_fill(0, $request->integer('deck'), ''))
        );
        $fleetType->has_ac = $request->boolean('has_ac') ? Status::ENABLE : Status::DISABLE;
        $fleetType->facilities = $request->facilities ?? null;
        $fleetType->disabled_seats = $request->disabled_seats ?? null;
        if (!$fleetType->exists) {
            $fleetType->status = Status::ENABLE;
        }
        $fleetType->save();

        return response()->json([
            'message' => $message,
            'fleet_type' => $fleetType->fresh(),
        ]);
    }

    public function typePreview(Request $request, SeatLayoutService $seatLayoutService)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'seat_layout' => ['required', 'string', 'regex:/^\s*\d+\s*x\s*\d+(?:\s*x\s*\d+)?\s*$/i'],
            'deck_seats' => 'required|array|min:1',
            'deck_seats.*' => 'required|integer|min:1',
            'last_row' => 'nullable|array',
            'last_row.*' => 'nullable|integer|min:0',
            'prefixes' => 'nullable|array',
            'prefixes.*' => 'nullable|string|max:10',
            'disabled_seats' => 'nullable|array',
            'disabled_seats.*' => 'string|max:30',
            'cr_position' => 'nullable|in:Left,Center,Right',
            'cr_row' => 'nullable|integer|min:1',
            'cr_row_covered' => 'nullable|integer|min:1|max:3',
            'cr_override_seat' => 'nullable|boolean',
        ]);

        $seatLayout = $seatLayoutService->layout([
            ...$validated,
            'name' => $validated['name'] ?? 'Fleet Type',
            'last_row' => $validated['last_row'] ?? [],
            'prefixes' => $validated['prefixes'] ?? [],
            'disabled_seats' => $validated['disabled_seats'] ?? [],
            'cr_override_seat' => $request->boolean('cr_override_seat'),
        ]);

        return response()->json([
            'html' => view('templates.basic.partials.seat_layout', [
                'fleetType' => $validated,
                'seatLayout' => $seatLayout,
                'seatLayoutMode' => 'preview',
            ])->render(),
            'seat_ids' => $seatLayout['seat_ids'],
        ]);
    }

    public function typeStatus($id)
    {
        return FleetType::changeStatus($id);
    }


    public function vehicles()
    {
        $pageTitle = 'All Vehicles';
        $fleetType = FleetType::where('status', Status::ENABLE)->orderBy('id', 'desc')->get();
        $vehicles = Vehicle::searchable(['nick_name'])->with('fleetType')->orderBy('id', 'desc');

        $fleet_type_id = request('fleetTypeId');
        if ($fleet_type_id && $fleet_type_id != 'all') {
            $vehicles->where('fleet_type_id', $fleet_type_id);
        }
        
        $vehicles = $vehicles->paginate(getPaginate());
        return view('admin.fleet.vehicles', compact('pageTitle', 'vehicles', 'fleetType'));
    }

    public function vehiclesStore(Request $request, $id = 0)
    {

        $request->validate(
            [
                'nick_name' => 'required|string',
                'fleet_type_id' => 'required|numeric',
                'register_no' => 'required|string|unique:vehicles,register_no,' . $id,
                'model_no' => 'required|string',
                'bus_no' => 'required|string|unique:vehicles,bus_no,' . $id,
            ],
            [
                'model_no.required' => 'Bus make field is required.',
            ]
        );

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
        $vehicle->bus_no = $request->bus_no;
        $vehicle->save();

        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }

    public function vehicleStatus($id)
    {
        return Vehicle::changeStatus($id);
    }

    public function deleteVehicle($id)
    {

        $query = Vehicle::find($id);
        if ($query->delete()) {
            $notify[] = ['success', 'Vehicle was deleted.'];
            return back()->withNotify($notify);
        }

        $notify[] = ['error', 'Posting failed.'];
        return back()->withNotify($notify);
    }
}
