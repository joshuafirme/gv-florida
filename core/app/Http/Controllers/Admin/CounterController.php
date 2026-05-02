<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use App\Models\Trip;
use Carbon\Carbon;
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

        // return $data;
        return view('admin.counter.board');
    }

    public function scheduleBoardJSON($counter_id)
    {
        $trips = $this->getTripQuery($counter_id)->get();

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

            $deck_seats = $trip->fleetType->deck_seats;
            $deck_seats = (int) $deck_seats[$trip->fleetType->deck - 1];

            $available_seats_ctr = 0;
            $deck_seats = $trip->fleetType->deck_seats;
            $deck_seats = (int) $deck_seats[0];
            if ($trip->fleetType->deck == 2) {
                $deck_seats += (int) $trip->fleetType->deck_seats[1];
            }
            $available_seats_ctr = $deck_seats - $occupied_seats_ctr;
            if ($trip->fleetType->cr_position) {
                $available_seats_ctr -= (int) $trip->fleetType->cr_row_covered;
            }
            if ($available_seats_ctr < 1) {
                continue;
            }

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

    public function getTripQuery($counter_id)
    {
        $now = Carbon::now();
        $request = request();
        $mins_value = 15;

        return Trip::with([
            'route' => function ($query) {
                $query->with(['startFrom', 'endTo']);
            },
            'fleetType',
            'schedule'
        ])
            ->withMin('schedule as earliest_start', 'start_from')
            ->whereHas('schedule', function ($q) use ($now) {
                $q->whereRaw("
                      DATE_SUB(
                          STR_TO_DATE(CONCAT(?, ' ', start_from), '%Y-%m-%d %H:%i:%s'),
                          INTERVAL 15 MINUTE
                      ) > ?
                  ", [
                    Carbon::parse($now)->format('Y-m-d'),
                    $now->format('Y-m-d H:i:s')
                ]);
            })
            ->where('start_from', $counter_id)
            ->orderBy('earliest_start')
            ->active();
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

    public function reservationSlip(Request $request)
    {
        $counter = Counter::find($request->counter_id);
        $pageTitle = "$counter->name Reservation Slip Content";

        $dir = 'assets/admin/contents/';
        $file = "{$dir}reservation-slip-$request->counter_id.json";
        $content['data'] = '';

        if (!file_exists($file)) {
            if (!is_dir($dir)) {
                mkdir($dir);
            }
            file_put_contents($file, json_encode($content));
        }
        $fileContent = @file_get_contents($file);
        $data = json_decode($fileContent);
        return view('admin.counter.contents.reservation-slip', compact('pageTitle', 'data', 'counter'));
    }

    public function updateReservationSlip(Request $request)
    {
        $file = "assets/admin/contents/reservation-slip-$request->counter_id.json";
        if (!file_exists($file)) {
            fopen($file, "w");
        }
        $data['heading'] = $request->heading;
        $data['subheading'] = $request->subheading;
        $data['terms_and_conditions'] = $request->terms_and_conditions;
        file_put_contents($file, json_encode($data));
        $notify[] = ['success', 'Content updated successfully'];
        return back()->withNotify($notify);
    }
}
