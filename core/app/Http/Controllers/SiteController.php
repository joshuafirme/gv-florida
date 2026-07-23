<?php

namespace App\Http\Controllers;

use App\Constants\Status;
use App\Lib\BusLayout;
use App\Models\AdminNotification;
use App\Models\FleetType;
use App\Models\Frontend;
use App\Models\Kiosk;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\TicketPrice;
use App\Models\BookedTicket;
use App\Models\VehicleRoute;
use App\Models\Counter;
use App\Models\Language;
use App\Models\Page;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\SeatConflictService;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;


class SiteController extends Controller
{
    public function index()
    {
        $pageTitle = 'Home';
        $sections = Page::where('tempname', activeTemplate())->where('slug', '/')->first();
        $seoContents = $sections->seo_content;
        $seoImage = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        $counters = Counter::active()->get();
        return view('Template::home', compact('pageTitle', 'sections', 'seoContents', 'seoImage', 'counters'));
    }

    public function pages($slug)
    {
        $page = Page::where('tempname', activeTemplate())->where('slug', $slug)->firstOrFail();
        $pageTitle = $page->name;
        $sections = $page->secs;
        $seoContents = $page->seo_content;
        $seoImage = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        return view('Template::pages', compact('pageTitle', 'sections', 'seoContents', 'seoImage'));
    }

    public function contact()
    {
        $pageTitle = "Contact Us";
        $user = auth()->user();
        $sections = Page::where('tempname', activeTemplate())->where('slug', 'contact')->first();
        $seoContents = $sections->seo_content;
        $seoImage = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        return view('Template::contact', compact('pageTitle', 'user', 'sections', 'seoContents', 'seoImage'));
    }


    public function contactSubmit(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'subject' => 'required|string|max:255',
            'message' => 'required',
        ]);

        $request->session()->regenerateToken();

        if (!verifyCaptcha()) {
            $notify[] = ['error', 'Invalid captcha provided'];
            return back()->withNotify($notify);
        }

        $random = getNumber();

        $ticket = new SupportTicket();
        $ticket->user_id = auth()->id() ?? 0;
        $ticket->name = $request->name;
        $ticket->email = $request->email;
        $ticket->priority = Status::PRIORITY_MEDIUM;


        $ticket->ticket = $random;
        $ticket->subject = $request->subject;
        $ticket->last_reply = Carbon::now();
        $ticket->status = Status::TICKET_OPEN;
        $ticket->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = auth()->user() ? auth()->user()->id : 0;
        $adminNotification->title = 'A new contact message has been submitted';
        $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
        $adminNotification->save();

        $message = new SupportMessage();
        $message->support_ticket_id = $ticket->id;
        $message->message = $request->message;
        $message->save();

        $notify[] = ['success', 'Ticket created successfully!'];

        return to_route('ticket.view', [$ticket->ticket])->withNotify($notify);
    }

    public function policyPages($slug)
    {
        $policy = Frontend::where('slug', $slug)->where('data_keys', 'policy_pages.element')->firstOrFail();
        $pageTitle = $policy->data_values->title;
        $seoContents = $policy->seo_content;
        $seoImage = @$seoContents->image ? frontendImage('policy_pages', $seoContents->image, getFileSize('seo'), true) : null;
        return view('Template::policy', compact('policy', 'pageTitle', 'seoContents', 'seoImage'));
    }

    public function changeLanguage($lang = null)
    {
        $language = Language::where('code', $lang)->first();
        if (!$language)
            $lang = 'en';
        session()->put('lang', $lang);
        return back();
    }

    public function blog()
    {
        $pageTitle = 'Blogs';
        $blogs = Frontend::where('data_keys', 'blog.element')->where('tempname', activeTemplateName())->orderBy('id', 'DESC')->paginate(getPaginate());
        $sections = Page::where('tempname', activeTemplate())->where('slug', 'blog')->first();
        return view('Template::blog', compact('pageTitle', 'blogs', 'sections'));
    }

    public function blogDetails($slug)
    {

        $blog = Frontend::where('slug', $slug)->where('data_keys', 'blog.element')->firstOrFail();
        $latestPost = Frontend::where('data_keys', 'blog.element')->where('slug', '!=', $slug)->orderBy('id', 'desc')->take(10)->get();
        $pageTitle = $blog->data_values->title;
        $seoContents = $blog->seo_content;
        $seoImage = @$seoContents->image ? frontendImage('blog', $seoContents->image, getFileSize('seo'), true) : null;
        return view('Template::blog_details', compact('blog', 'pageTitle', 'seoContents', 'latestPost', 'seoImage'));
    }


    public function cookieAccept()
    {
        Cookie::queue('gdpr_cookie', gs('site_name'), 43200);
    }

    public function cookiePolicy()
    {
        $cookieContent = Frontend::where('data_keys', 'cookie.data')->first();
        abort_if($cookieContent->data_values->status != Status::ENABLE, 404);
        $pageTitle = 'Cookie Policy';
        $cookie = Frontend::where('data_keys', 'cookie.data')->first();
        return view('Template::cookie', compact('pageTitle', 'cookie'));
    }

    public function ticket(Request $request)
    {
        if ($request->filled('date_of_journey')) {
            try {
                $formattedDate = Carbon::parse($request->date_of_journey)->format('m/d/Y');
            } catch (\Exception $exception) {
                $formattedDate = null;
            }

            if ($formattedDate && $request->date_of_journey !== $formattedDate) {
                $query = $request->query();
                $query['date_of_journey'] = $formattedDate;

                return redirect()->to($request->url() . '?' . urldecode(http_build_query($query)));
            }
        }

        // -------------------------------
        // 1. VALIDATIONS (If searching)
        // -------------------------------
        if ($request->has('pickup') || $request->has('destination') || $request->has('date_of_journey')) {
            if ($request->pickup && $request->destination && $request->pickup == $request->destination) {
                $notify[] = ['error', 'Please select pickup point and destination point properly'];
                return redirect()->back()->withNotify($notify);
            }

            if ($request->date_of_journey && Carbon::parse($request->date_of_journey)->format('Y-m-d') < Carbon::now()->format('Y-m-d')) {
                $notify[] = ['error', 'Date of journey can\'t be less than today.'];
                return redirect()->back()->withNotify($notify);
            }

            if ($request->date_of_journey && ($message = $this->advanceBookingDateError($request->date_of_journey, $request->kiosk_id))) {
                $notify[] = ['error', $message];
                return redirect()->back()->withNotify($notify);
            }
        }

        // Set dynamic title
        $pageTitle = ($request->pickup || $request->destination || $request->date_of_journey) ? 'Search Result' : 'Book Ticket';
        $emptyMessage = 'There is no trip available';

        // -------------------------------
        // 2. KIOSK FILTER
        // -------------------------------
        $tripIds = [];
        if ($request->kiosk_id) {
            $ksk_trips = Kiosk::where('id', $request->kiosk_id)->with([
                'counter.trips' => function ($q) {
                    $q->select('id', 'start_from');
                }
            ])->get();

            foreach ($ksk_trips as $kiosk) {
                if (isset($kiosk['counter']['trips'])) {
                    foreach ($kiosk['counter']['trips'] as $trip) {
                        $tripIds[] = $trip['id'];
                    }
                }
            }
        }

        // -------------------------------
        // 3. BASE QUERY
        // -------------------------------
        $trips_query = $this->getTripQuery();

        if ($request->kiosk_id) {
            $trips_query->whereIntegerInRaw('id', $tripIds);
        }

        $pickup = $request->pickup ?: $request->counter_id;
        $destination = $request->destination ?: $request->selected_destination;

        // -------------------------------
        // 4. MAIN FILTER LOGIC
        // -------------------------------
        if ($pickup && $destination) {
            Session::flash('pickup', $pickup);
            Session::flash('destination', $destination);

            // Fetch temp collection to resolve route logic
            $tripsCollection = (clone $trips_query)->with('route')->get();
            $tripIdsFiltered = [];

            foreach ($tripsCollection as $trip) {
                $stoppages = array_values($trip->route->stoppages);

                $startPoint = array_search($trip->start_from, $stoppages);
                $endPoint = array_search($trip->end_to, $stoppages);
                $pickup_point = array_search($pickup, $stoppages);
                $destination_point = array_search($destination, $stoppages);

                if ($startPoint < $endPoint) {
                    if (
                        $pickup_point !== false && $destination_point !== false &&
                        $pickup_point >= $startPoint &&
                        $pickup_point < $endPoint &&
                        $destination_point > $startPoint &&
                        $destination_point <= $endPoint &&
                        $pickup_point < $destination_point
                    ) {
                        $tripIdsFiltered[] = $trip->id;
                    }
                } else {
                    $revArray = array_reverse($stoppages);

                    $startPoint = array_search($trip->start_from, $revArray);
                    $endPoint = array_search($trip->end_to, $revArray);
                    $pickup_point = array_search($pickup, $revArray);
                    $destination_point = array_search($destination, $revArray);

                    if (
                        $pickup_point !== false && $destination_point !== false &&
                        $pickup_point >= $startPoint &&
                        $pickup_point < $endPoint &&
                        $destination_point > $startPoint &&
                        $destination_point <= $endPoint &&
                        $pickup_point < $destination_point
                    ) {
                        $tripIdsFiltered[] = $trip->id;
                    }
                }
            }

            // Rebuild Query with Filtered IDs
            $trips_query->whereIn('id', $tripIdsFiltered);

        } else {
            // Partial Filters (Only pickup or only destination)
            if ($pickup) {
                Session::flash('pickup', $pickup);
                $trips_query->whereHas('route', function ($route) use ($pickup) {
                    $route->whereJsonContains('stoppages', (string) $pickup)
                        ->orWhereJsonContains('stoppages', (int) $pickup);
                });
            }

            if ($destination) {
                Session::flash('destination', $destination);
                $trips_query->whereHas('route', function ($route) use ($destination) {
                    $route->whereJsonContains('stoppages', (string) $destination)
                        ->orWhereJsonContains('stoppages', (int) $destination);
                });
            }
        }

        // Apply remaining generic filters (e.g., date of journey)
        if (method_exists($this, 'filterTrip')) {
            $trips_query = $this->filterTrip($trips_query);
        }

        // -------------------------------
        // 5. FINALIZE & PAGINATE
        // -------------------------------
        $trips = $trips_query->paginate(getPaginate());
        $ticketPrices = TicketPrice::with('prices')
            ->whereIn('vehicle_route_id', $trips->getCollection()->pluck('vehicle_route_id')->unique())
            ->whereIn('fleet_type_id', $trips->getCollection()->pluck('fleet_type_id')->unique())
            ->get()
            ->keyBy(fn ($price) => $price->vehicle_route_id . ':' . $price->fleet_type_id);

        // Prepare View Data
        $fleetType = FleetType::active()->get();
        $schedules = Schedule::all();
        $routes = VehicleRoute::active()->get();

        $counters = Counter::active();
        if ($request->kiosk_id && $request->counter_id) {
            $counters->where('id', $request->counter_id);
        }
        $counters = $counters->get();

        $layout = auth()->check() ? 'layouts.master' : 'layouts.frontend';

        return view("Template::ticket", compact(
            'pageTitle',
            'fleetType',
            'trips',
            'routes',
            'counters',
            'schedules',
            'emptyMessage',
            'ticketPrices',
            'layout'
        ));
    }

    public function showSeat(Request $request, $id)
    {
        $trip = Trip::with([
            'fleetType',
            'route',
            'schedule',
            'startFrom',
            'endTo',
            'assignedVehicle.vehicle',
            'bookedTickets'
        ])->where('status', Status::ENABLE)->where('id', $id)->firstOrFail();

        $journeyDate = $request->date_of_journey ?: now()->format('m/d/Y');
        if ($message = $this->bookingWindowError($trip, $journeyDate, $request->kiosk_id)) {
            $notify[] = ['error', $message];
            $query = array_filter($request->only('pickup', 'destination', 'date_of_journey', 'kiosk_id', 'counter_id'));

            return redirect()->route('ticket', $query)->withNotify($notify);
        }

        $pageTitle = $trip->route->name;
        $route = $trip->route;

        // Fetch stoppages safely
        $stoppageArr = $trip->route->stoppages ?? [];
        $stoppages = Counter::routeStoppages($stoppageArr);

        // Define routeSequence for the JavaScript Fare Preview & Dropping Points Engine
        $routeSequence = Counter::routeStoppages($stoppageArr);

        $busLayout = new BusLayout($trip);

        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }

        return view("Template::book_ticket", compact(
            'pageTitle',
            'trip',
            'stoppages',
            'routeSequence', // Added here
            'busLayout',
            'layout'
        ));
    }

    public function bookedQuery($request)
    {
        $dateOfJourney = $request->date ?: $request->date_of_journey;

        return BookedTicket::where('trip_id', $request->trip_id)
            ->whereDate('date_of_journey', Carbon::parse($dateOfJourney)->format('Y-m-d'))
            ->where(function ($query) {
                $query->where('status', Status::BOOKED_APPROVED)
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('status', Status::BOOKED_PENDING)
                            ->where(function ($activeQuery) {
                                $activeQuery->where('created_at', '>=', Carbon::now()->subMinutes(15))
                                    ->orWhereHas('deposit', function ($depositQuery) {
                                        $depositQuery->where('created_at', '>=', Carbon::now()->subMinutes(15));
                                    });
                            });
                    });
            })
            // Select only the columns needed by the frontend to save memory
            ->get(['pickup_point', 'dropping_point', 'seats', 'gender', 'pnr_number']);
    }

    public function getTicketPrice(Request $request)
    {
        // 1. Fetch Ticket Price Configuration
        $ticketPrice = TicketPrice::with('route')
            ->where('vehicle_route_id', $request->vehicle_route_id)
            ->where('fleet_type_id', $request->fleet_type_id)
            ->first();

        if (!$ticketPrice) {
            return response()->json([
                'price' => ['error' => 'Ticket price configuration not found for this bus type and route.']
            ]);
        }

        // 2. Normalize Stoppages (Ensure it's a flat array of strings for JS `indexOf`)
        $route = $ticketPrice->route;
        $stoppages = is_string($route->stoppages) ? json_decode($route->stoppages, true) : $route->stoppages;
        $stoppages = array_map('strval', $stoppages ?? []);

        // 3. Determine Travel Direction (Reverse Logic)
        $trip = Trip::find($request->trip_id);
        $reverse = false;

        if ($trip) {
            $startIndex = array_search((string) $trip->start_from, $stoppages);
            $endIndex = array_search((string) $trip->end_to, $stoppages);

            if ($startIndex !== false && $endIndex !== false) {
                // If the Trip's Start Point comes AFTER the End Point in the Route's master array, it's traveling in reverse
                $reverse = ($startIndex > $endIndex);
            }
        }

        // 4. Fetch Booked Tickets
        $bookedTicket = $this->bookedQuery($request)->toArray();

        // 5. Fetch Ticket Price (Using Laravel's safe whereJsonContains with fallback)
        $sdArray = [(string) $request->source_id, (string) $request->destination_id];

        $getPrice = $ticketPrice->prices()
            ->where(function ($query) use ($sdArray) {
                // whereJsonContains handles array matching automatically regardless of DB type
                $query->whereJsonContains('source_destination', $sdArray)
                    // Fallbacks just in case the DB is using strict string columns instead of JSON
                    ->orWhere('source_destination', json_encode($sdArray))
                    ->orWhere('source_destination', json_encode(array_reverse($sdArray)));
            })->first();

        if ($getPrice) {
            $price = $getPrice->price;
        } else {
            $price = [
                'error' => 'Admin has not set prices for this specific pickup and dropping point.'
            ];
        }

        // 6. Return standard JSON
        return response()->json([
            'bookedSeats' => $bookedTicket,
            'reqSource' => (string) $request->source_id,
            'reqDestination' => (string) $request->destination_id,
            'reverse' => $reverse,
            'stoppages' => $stoppages,
            'price' => $price
        ]);
    }

    public function validateSeats(Request $request, $id)
    {
        $request->validate([
            'pickup_point' => 'required|integer|gt:0',
            'dropping_point' => 'required|integer|gt:0',
            'date_of_journey' => 'required|date',
            'seats' => 'required|string',
        ], [
            'seats.required' => 'Please select at least one seat.',
        ]);

        $trip = Trip::with(['route', 'schedule'])->where('status', Status::ENABLE)->findOrFail($id);
        if ($message = $this->bookingWindowError($trip, $request->date_of_journey, $request->kiosk_id)) {
            return response()->json([
                'available' => false,
                'message' => $message,
            ], 422);
        }
        $seatConflicts = app(SeatConflictService::class);
        $submittedSeats = collect(explode(',', $request->seats))
            ->map(fn ($seat) => trim((string) $seat))
            ->filter()
            ->values();
        $seats = $seatConflicts->normalizeSeats($submittedSeats->all());

        if ($submittedSeats->count() !== count($seats)) {
            return response()->json([
                'available' => false,
                'message' => 'A seat cannot be assigned more than once in the same booking.',
            ], 422);
        }

        if (!$seatConflicts->isValidSegment($trip, $request->pickup_point, $request->dropping_point)) {
            return response()->json([
                'available' => false,
                'message' => 'Select the pickup and dropping points in the correct trip order.',
            ], 422);
        }

        $conflicts = $seatConflicts->conflicts(
            $trip,
            $request->date_of_journey,
            $request->pickup_point,
            $request->dropping_point,
            $seats
        );
        $unavailableSeats = $seatConflicts->conflictingSeats($conflicts, $seats);

        if ($unavailableSeats) {
            return response()->json([
                'available' => false,
                'conflicting_seats' => $unavailableSeats,
                'message' => 'Seat(s) ' . formatSeatLabel($unavailableSeats) . ' are already assigned on an overlapping trip segment.',
            ], 409);
        }

        return response()->json([
            'available' => true,
            'seats' => $seats,
        ]);
    }

    public function bookTicket(Request $request, $id)
    {
        try {
            if (app()->isProduction() && !$request->kiosk_id) {
                $notify[] = ['error', 'This feature is currently unavailable.'];
                return redirect()->back()->withNotify($notify);
            }

            $trip = Trip::with('schedule')->findOrFail($id);
            if ($message = $this->bookingWindowError($trip, $request->date_of_journey, $request->kiosk_id)) {
                $notify[] = ['error', $message];
                return redirect()->back()->withNotify($notify);
            }

            $request->validate([
                "pickup_point" => "required|integer|gt:0",
                "dropping_point" => "required|integer|gt:0",
                "date_of_journey" => "required|date",
                "seats" => "required|string",
            ], [
                "seats.required" => "Please Select at Least One Seat"
            ]);

            session()->put('kiosk_id', request('kiosk_id'));

            $kiosk = null;

            $kiosk = $request->kiosk_id ? Kiosk::find($request->kiosk_id) : null;

            if (!auth()->user() && !$kiosk) {
                $notify[] = ['error', 'Without login you can\'t book any tickets'];
                return redirect()->route('user.login')->withNotify($notify);
            }

            $date_of_journey = Carbon::parse($request->date_of_journey);
            $today = Carbon::today()->format('Y-m-d');
            if ($date_of_journey->format('Y-m-d') < $today) {
                $notify[] = ['error', 'Date of journey cant\'t be less than today'];
                return redirect()->back()->withNotify($notify);
            }

            $dayOff = $date_of_journey->format('w');
            $trip = Trip::findOrFail($id);
            $route = $trip->route;

            if (!empty($trip->day_off)) {
                if (in_array($dayOff, $trip->day_off)) {
                    $notify[] = ['error', 'The trip is not available for ' . $date_of_journey->format('l')];
                    return redirect()->back()->withNotify($notify);
                }
            }

            $seatConflicts = app(SeatConflictService::class);
            $submittedSeats = collect(explode(',', $request->seats))
                ->map(fn ($seat) => trim((string) $seat))
                ->filter()
                ->values();
            $seats = $seatConflicts->normalizeSeats($submittedSeats->all());

            if ($submittedSeats->count() !== count($seats)) {
                $notify[] = ['error', 'A seat cannot be assigned more than once in the same booking.'];
                return redirect()->back()->withNotify($notify);
            }

            if (!$seatConflicts->isValidSegment($trip, $request->pickup_point, $request->dropping_point)) {
                $notify[] = ['error', 'Select Pickup Point & Dropping Point Properly'];
                return redirect()->back()->withNotify($notify);
            }

            $route = $trip->route;
            $ticketPrice = TicketPrice::where('fleet_type_id', $trip->fleetType->id)->where('vehicle_route_id', $route->id)->first();
            $sdArray = [$request->pickup_point, $request->dropping_point];

            $getPrice = $ticketPrice->prices()
                ->where('source_destination', json_encode($sdArray))
                ->orWhere('source_destination', json_encode(array_reverse($sdArray)))
                ->first();
            if (!$getPrice) {
                $notify[] = ['error', 'Invalid selection'];
                return back()->withNotify($notify);
            }

            DB::beginTransaction();

            $lockedTrip = Trip::with('route')->whereKey($id)->lockForUpdate()->firstOrFail();
            $conflicts = $seatConflicts->conflicts(
                $lockedTrip,
                $request->date_of_journey,
                $request->pickup_point,
                $request->dropping_point,
                $seats,
                lockForUpdate: true
            );
            $unavailableSeats = $seatConflicts->conflictingSeats($conflicts, $seats);

            if ($unavailableSeats) {
                DB::rollBack();
                $notify[] = ['error', 'Seat(s) ' . formatSeatLabel($unavailableSeats) . ' were just assigned on an overlapping trip segment. Please choose another seat.'];
                return redirect()->back()->withInput()->withNotify($notify);
            }

            $unitPrice = getAmount($getPrice->price);
            $pnr_number = getTrx(10);
            $bookedTicket = new BookedTicket();
            $bookedTicket->user_id = $request->user() ? $request->user()->id : null;
            $bookedTicket->gender = 1;
            $bookedTicket->trip_id = $trip->id;
            $bookedTicket->source_destination = [$request->pickup_point, $request->dropping_point];
            $bookedTicket->pickup_point = $request->pickup_point;
            $bookedTicket->dropping_point = $request->dropping_point;
            $bookedTicket->seats = array_values($seats);
            $bookedTicket->ticket_count = sizeof($seats);
            $bookedTicket->unit_price = $unitPrice;
            $bookedTicket->sub_total = sizeof($seats) * $unitPrice;
            $bookedTicket->date_of_journey = Carbon::parse($request->date_of_journey)->format('Y-m-d');
            $bookedTicket->pnr_number = $pnr_number;
            $bookedTicket->status = Status::BOOKED_PENDING;
            $bookedTicket->kiosk_id = $request->kiosk_id;
            $bookedTicket->save();

            session()->put('pnr_number', $pnr_number);
            session()->put('booked_ticket_id', $bookedTicket->id);
            session()->put('seats', $seats);

            DB::commit();

            return redirect()->route('user.deposit.index', ['kiosk_id' => $kiosk]);
        } catch (\Exception $e) {
            DB::rollBack();
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }
    }

    public function getTripQuery()
    {
        $now = Carbon::now();
        $request = request();
        $cutoffMinutes = getBookingCutoffMinutes($request->kiosk_id);

        return Trip::with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo'])
            ->withMin('schedule as earliest_start', 'start_from')
            ->whereHas('schedule', function ($q) use ($now, $request, $cutoffMinutes) {
                $date = $request->date_of_journey ? Carbon::parse($request->date_of_journey) : Carbon::now();
                if ($date->isToday()) {
                    $q->whereRaw("
                      STR_TO_DATE(CONCAT(?, ' ', start_from), '%Y-%m-%d %H:%i:%s') > ?
                  ", [
                        Carbon::parse($date)->format('Y-m-d'),
                        $now->copy()->addMinutes($cutoffMinutes)->format('Y-m-d H:i:s')
                    ]);
                }
            })
            ->orderBy('earliest_start')
            ->orderBy('id')
            ->active();
    }

    public function filterTrip($trips)
    {
        $request = request();
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

        return $trips->with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo'])->where('status', Status::ENABLE);
    }

    private function advanceBookingDateError($journeyDate, $kioskId = null): ?string
    {
        try {
            $date = Carbon::parse($journeyDate)->startOfDay();
        } catch (\Throwable $exception) {
            return 'Enter a valid date of journey.';
        }

        $today = Carbon::today();
        if ($date->lt($today)) {
            return 'Date of journey can\'t be less than today.';
        }

        $allowedDays = getAllowedAdvanceBookingDays($kioskId);
        if ($date->gt($today->copy()->addDays($allowedDays))) {
            $channel = $kioskId ? 'Kiosk' : 'Online';
            $unit = $allowedDays === 1 ? 'day' : 'days';

            return "{$channel} bookings can only be made up to {$allowedDays} {$unit} in advance.";
        }

        return null;
    }

    private function bookingWindowError(Trip $trip, $journeyDate, $kioskId = null): ?string
    {
        if ($message = $this->advanceBookingDateError($journeyDate, $kioskId)) {
            return $message;
        }

        if (!$trip->schedule?->start_from) {
            return 'The selected trip does not have a valid departure schedule.';
        }

        $departure = Carbon::parse(
            Carbon::parse($journeyDate)->format('Y-m-d') . ' ' . $trip->schedule->start_from
        );
        $cutoffMinutes = getBookingCutoffMinutes($kioskId);

        if (now()->gte($departure->copy()->subMinutes($cutoffMinutes))) {
            $channel = $kioskId ? 'Kiosk' : 'Online';
            if ($cutoffMinutes === 0) {
                return "{$channel} booking closes at departure time.";
            }

            $unit = $cutoffMinutes === 1 ? 'minute' : 'minutes';

            return "{$channel} booking closes {$cutoffMinutes} {$unit} before departure.";
        }

        return null;
    }

    public function getDroppingPoints($counter_id)
    {
        // Fetch active trips to map exact travel sequences
        $trips = Trip::with('route')->active()->get();
        $validDroppingIds = [];

        foreach ($trips as $trip) {
            if (!$trip->route)
                continue;

            // Ensure stoppages is treated as an array
            $stoppages = $trip->route->stoppages;
            if (is_string($stoppages)) {
                $stoppages = json_decode($stoppages, true);
            }

            if (!is_array($stoppages) || empty($stoppages)) {
                // Fallback: If route has no stoppages array but matches start_from
                if ($trip->start_from == $counter_id) {
                    $validDroppingIds[] = $trip->end_to;
                }
                continue;
            }

            $stoppages = array_values($stoppages); // Re-index cleanly

            $startPoint = array_search($trip->start_from, $stoppages);
            $endPoint = array_search($trip->end_to, $stoppages);

            // Find where the user wants to board
            $pickup_point = array_search($counter_id, $stoppages);
            if ($pickup_point === false) {
                $pickup_point = array_search((string) $counter_id, $stoppages);
            }

            // If the pickup point is not on this trip's route, skip it
            if ($pickup_point === false)
                continue;

            // -----------------------------------------
            // FORWARD SEQUENCE
            // -----------------------------------------
            if ($startPoint !== false && $endPoint !== false && $startPoint < $endPoint) {
                // Verify pickup is valid (between start and end)
                if ($pickup_point >= $startPoint && $pickup_point < $endPoint) {
                    // Grab every stop AFTER the pickup point
                    for ($i = $pickup_point + 1; $i <= $endPoint; $i++) {
                        $validDroppingIds[] = $stoppages[$i];
                    }
                }
            }
            // -----------------------------------------
            // REVERSE SEQUENCE
            // -----------------------------------------
            else {
                $revStoppages = array_reverse($stoppages);
                $revStartPoint = array_search($trip->start_from, $revStoppages);
                $revEndPoint = array_search($trip->end_to, $revStoppages);

                $revPickupPoint = array_search($counter_id, $revStoppages);
                if ($revPickupPoint === false) {
                    $revPickupPoint = array_search((string) $counter_id, $revStoppages);
                }

                if ($revStartPoint !== false && $revEndPoint !== false && $revPickupPoint !== false) {
                    if ($revPickupPoint >= $revStartPoint && $revPickupPoint < $revEndPoint) {
                        for ($i = $revPickupPoint + 1; $i <= $revEndPoint; $i++) {
                            $validDroppingIds[] = $revStoppages[$i];
                        }
                    }
                }
            }
        }

        // Clean array (remove duplicates and empties)
        $validDroppingIds = array_filter(array_unique($validDroppingIds));

        // Fetch actual Counter details, sorted alphabetically
        if (!empty($validDroppingIds)) {
            $dropping_counters = Counter::active()
                ->whereIn('id', $validDroppingIds)
                ->orderBy('name', 'asc')
                ->select('id', 'name')
                ->get();
        } else {
            $dropping_counters = [];
        }

        return response()->json($dropping_counters);
    }

    public function placeholderImage($size = null)
    {
        $imgWidth = explode('x', $size)[0];
        $imgHeight = explode('x', $size)[1];
        $text = $imgWidth . '×' . $imgHeight;
        $fontFile = realpath('assets/font/solaimanLipi_bold.ttf');
        $fontSize = round(($imgWidth - 50) / 8);
        if ($fontSize <= 9) {
            $fontSize = 9;
        }
        if ($imgHeight < 100 && $fontSize > 30) {
            $fontSize = 30;
        }

        $image = imagecreatetruecolor($imgWidth, $imgHeight);
        $colorFill = imagecolorallocate($image, 100, 100, 100);
        $bgFill = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgFill);
        $textBox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        $textX = ($imgWidth - $textWidth) / 2;
        $textY = ($imgHeight + $textHeight) / 2;
        header('Content-Type: image/jpeg');
        imagettftext($image, $fontSize, 0, $textX, $textY, $colorFill, $fontFile, $text);
        imagejpeg($image);
        imagedestroy($image);
    }

    public function maintenance()
    {
        $pageTitle = 'Maintenance Mode';
        if (gs('maintenance_mode') == Status::DISABLE) {
            return to_route('home');
        }
        $maintenance = Frontend::where('data_keys', 'maintenance.data')->first();
        return view('Template::maintenance', compact('pageTitle', 'maintenance'));
    }
}
