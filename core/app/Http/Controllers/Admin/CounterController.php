<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use App\Models\AdminSeatLock;
use App\Models\ScheduleBoardAdvertisement;
use App\Models\ScheduleBoardNotice;
use App\Models\ScheduleBoardSetting;
use App\Models\ScheduleBoardTrip;
use App\Models\Trip;
use App\Services\SeatConflictService;
use App\Services\SeatLayoutService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Counter;

class CounterController extends Controller
{
    public function counters(Request $request)
    {
        $pageTitle = 'All Counters';
        $emptyMessage = 'No counters found';

        $query = Counter::query();

        // 1. Dynamic Filtering (Name, City, Mobile)
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                    ->orWhere('city', 'LIKE', "%$search%")
                    ->orWhere('mobile', 'LIKE', "%$search%");
            });
        }

        // 2. Status Filtering
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        // 3. Dynamic Sorting
        $sortField = $request->get('sort_field', 'id');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['id', 'name', 'mobile', 'city', 'status'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        }

        $counters = $query->paginate(getPaginate())->appends($request->all());

        return view('admin.counter.list', compact('pageTitle', 'counters', 'emptyMessage'));
    }

    public function remove($id)
    {
        $counter = Counter::findOrFail($id);
        
        if($counter->trips()->count() > 0) {
            $notify[] = ['error', 'Cannot delete this counter because it has associated trips.'];
            return back()->withNotify($notify);
        }

        $counter->delete();

        $notify[] = ['success', 'Counter deleted successfully.'];
        return back()->withNotify($notify);
    }

    // New Method for Bulk Enable/Disable
    public function bulkStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'action_type' => 'required|in:enable,disable'
        ]);

        $status = $request->action_type == 'enable' ? 1 : 0;

        Counter::whereIn('id', $request->ids)->update(['status' => $status]);

        $notify[] = ['success', 'Selected counters have been successfully updated.'];
        return back()->withNotify($notify);
    }

    public function scheduleBoard(Request $request, $counter_id)
    {
        $counter = Counter::findOrFail($counter_id);

        return view('admin.counter.board', [
            'counter' => $counter,
            'pusherKey' => config('services.pusher.key'),
            'pusherCluster' => config('services.pusher.cluster', 'ap1'),
        ]);
    }

    public function scheduleBoardJSON($counter_id)
    {
        $counter = Counter::findOrFail($counter_id);
        $setting = $counter->scheduleBoardSetting()->first() ?? new ScheduleBoardSetting([
            'show_all_trips' => true,
            'departure_grace_minutes' => 60,
        ]);
        $tripQuery = $this->getTripQuery($counter->id);
        $configuredTrips = $counter->scheduleBoardTrips()->orderBy('sort_order')->get();

        if (!$setting->show_all_trips) {
            $tripQuery->whereIn('id', $configuredTrips->where('is_visible', true)->pluck('trip_id'));
        }

        $configuredOrder = $configuredTrips->pluck('sort_order', 'trip_id');
        $graceMinutes = (int) $setting->departure_grace_minutes;
        $trips = $tripQuery->get()
            ->filter(function (Trip $trip) use ($graceMinutes) {
                if (!$trip->schedule?->start_from) {
                    return false;
                }

                $departure = Carbon::today()->setTimeFromTimeString($trip->schedule->start_from);

                return now()->lessThanOrEqualTo($departure->addMinutes($graceMinutes));
            })
            ->sortBy(function (Trip $trip) use ($configuredOrder) {
                return sprintf(
                    '%010d-%s',
                    $configuredOrder->get($trip->id, PHP_INT_MAX),
                    $trip->schedule?->start_from ?: '99:99:99'
                );
            })
            ->values();
        $journeyDate = now()->toDateString();
        $seatConflicts = app(SeatConflictService::class);
        $seatLayouts = app(SeatLayoutService::class);

        $data = [];

        foreach ($trips as $trip) {
            $unavailableSeats = $seatConflicts->unavailableSeats(
                $trip,
                $journeyDate,
                $trip->start_from,
                $trip->end_to
            );
            $deck_seats = $seatLayouts->seatIds($trip->fleetType)->count();
            $available_seats_ctr = $seatLayouts->availableSeatCount(
                $trip->fleetType,
                ['booked' => $unavailableSeats]
            );
            $occupied_seats_ctr = $deck_seats - $available_seats_ctr;

            $trip['deck_seats'] = $deck_seats;
            $trip['occupied_seats'] = $occupied_seats_ctr;
            $trip['available_seats'] = $available_seats_ctr;
            $trip['is_fully_booked'] = $available_seats_ctr < 1;
            $trip['trip_status'] = $trip->trip_status ?: Status::TRIP_ON_TIME;

            $data[] = $trip;
        }

        $advertisements = $counter->scheduleBoardAdvertisements()
            ->currentlyVisible()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ScheduleBoardAdvertisement $advertisement) => [
                'id' => $advertisement->id,
                'title' => $advertisement->title,
                'image_url' => asset('assets/images/schedule-board/' . $advertisement->image),
            ]);
        $notices = $counter->scheduleBoardNotices()
            ->currentlyVisible()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ScheduleBoardNotice $notice) => [
                'id' => $notice->id,
                'title' => $notice->title,
                'content' => $notice->content,
            ]);

        return response()->json([
            'res' => $data,
            'counter' => [
                'id' => $counter->id,
                'name' => $counter->name,
                'city' => $counter->city,
            ],
            'settings' => [
                'show_all_trips' => (bool) $setting->show_all_trips,
                'departure_grace_minutes' => $graceMinutes,
            ],
            'advertisements' => $advertisements,
            'notices' => $notices,
            'last_updated' => max(
                (string) Trip::max('updated_at'),
                (string) BookedTicket::whereDate('date_of_journey', date('Y-m-d'))->max('updated_at'),
                (string) AdminSeatLock::whereDate('date_of_journey', date('Y-m-d'))->max('updated_at'),
                (string) ScheduleBoardSetting::where('counter_id', $counter->id)->max('updated_at'),
                (string) ScheduleBoardTrip::where('counter_id', $counter->id)->max('updated_at'),
                (string) ScheduleBoardAdvertisement::where('counter_id', $counter->id)->max('updated_at'),
                (string) ScheduleBoardNotice::where('counter_id', $counter->id)->max('updated_at')
            )
        ]);
    }

    public function getTripQuery($counter_id)
    {
        return Trip::with([
            'route' => function ($query) {
                $query->with(['startFrom', 'endTo']);
            },
            'fleetType',
            'schedule',
            'assignedVehicle' => function ($query) {
                $query->with(['vehicle']);
            },
        ])
            ->withMin('schedule as earliest_start', 'start_from')
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
        $counter->km_post = $request->km_post;
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
