<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use App\Models\Trip;
use Illuminate\Http\Request;
use App\Models\Counter;

class CounterController extends Controller
{
    public function counters()
    {
        $pageTitle = 'All Counter';
        $counters = Counter::searchable(['name', 'city', 'mobile'])->paginate(getPaginate());
        return view('admin.counter.list', compact('pageTitle', 'counters'));
    }

    public function scheduleBoard(Request $request, $counter_id)
    {
        $trips = Trip::with([
            'route' => function ($query) {
                $query->with(['startFrom', 'endTo']);
            },
            'fleetType',
            'schedule'
        ])->where('start_from', $counter_id)->get();

        $data = [];

        foreach ($trips as $key => $trip) {
            $tickets = BookedTicket::where('trip_id', $trip->id)
                ->wheredate('date_of_journey', date('Y-m-d'))
                ->where('status', Status::BOOKED_APPROVED)
                ->get();

            $occupied_seats_ctr = 0;

            foreach ($tickets as $key => $ticket) {
                $occupied_seats_ctr += count($ticket->seats);
            }

            $available_seats_ctr = 0;
            $deck_seats = $trip->fleetType->deck_seats;
            $deck_seats = (int) $deck_seats[$trip->fleetType->deck - 1];
            $available_seats_ctr = $deck_seats - $occupied_seats_ctr;

            $trip['deck_seats'] = $deck_seats;
            $trip['occupied_seats'] = $occupied_seats_ctr;
            $trip['available_seats'] = $available_seats_ctr;

            $data[] = $trip;
        }
       // return $data;
        return view('admin.counter.board', compact('data'));
    }

    public function scheduleBoardJSON($counter_id)
    {
        $trips = Trip::with([
            'route' => function ($query) {
                $query->with(['startFrom', 'endTo']);
            },
            'fleetType',
            'schedule'
        ])->where('start_from', $counter_id)->get();

        $data = [];

        foreach ($trips as $key => $trip) {
            $tickets = BookedTicket::where('trip_id', $trip->id)
                ->wheredate('date_of_journey', date('Y-m-d'))
                ->where('status', Status::BOOKED_APPROVED)
                ->get();

            $occupied_seats_ctr = 0;

            foreach ($tickets as $key => $ticket) {
                $occupied_seats_ctr += count($ticket->seats);
            }

            $available_seats_ctr = 0;
            $deck_seats = $trip->fleetType->deck_seats;
            $deck_seats = (int) $deck_seats[$trip->fleetType->deck - 1];
            $available_seats_ctr = $deck_seats - $occupied_seats_ctr;

            $trip['deck_seats'] = $deck_seats;
            $trip['occupied_seats'] = $occupied_seats_ctr;
            $trip['available_seats'] = $available_seats_ctr;

            $data[] = $trip;
        }

        return response()->json([
            'res' => $data,
            'last_updated' => Trip::max('updated_at')
        ]);
    }

    public function counterStore(Request $request, $id = 0)
    {

        $request->validate([
            'name' => 'required|unique:counters,name,' . $id,
            'city' => 'required',
            'mobile' => 'required|numeric|unique:counters,mobile,' . $id
        ]);

        if ($id) {
            $counter = Counter::findOrFail($id);
            $message = 'Counter updated successfully';
        } else {
            $counter = new Counter();
            $message = 'Counter added successfully';
        }

        $counter->name = $request->name;
        $counter->city = $request->city;
        $counter->location = $request->location;
        $counter->mobile = $request->mobile;
        $counter->save();

        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }

    public function status($id)
    {
        return Counter::changeStatus($id);
    }
}
