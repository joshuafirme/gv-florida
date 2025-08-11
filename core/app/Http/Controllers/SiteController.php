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
use Carbon\Carbon;
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
        return view('Template::home', compact('pageTitle', 'sections', 'seoContents', 'seoImage'));
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
        $pageTitle = 'Book Ticket';
        $emptyMessage = 'There is no trip available';
        $fleetType = FleetType::active()->get();

        $tripIds = [];

        if ($request->kiosk_id) {
            $ksk_trips = Kiosk::where('id', $request->kiosk_id)->with([
                'counter' => function ($q) {
                    $q->with([
                        'trips' => function ($q) {
                            $q->select('id', 'start_from');
                        },
                    ]);
                },
            ])->get();

            foreach ($ksk_trips as $kiosk) {
                if (isset($kiosk['counter']['trips'])) {
                    foreach ($kiosk['counter']['trips'] as $trip) {
                        $tripIds[] = $trip['id'];
                    }
                }
            }
        }

        $trips_query = Trip::with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo'])->where('status', Status::ENABLE);
 
        if ($request->kiosk_id) {
            $trips_query->whereIn('id', $tripIds);
        }

        $trips = $trips_query->paginate(getPaginate(10));

        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }

        $schedules = Schedule::all();
        $routes = VehicleRoute::active()->get();

        $view = $request->kiosk_id ? 'kiosk_booking' : 'ticket';

        return view("Template::$view", compact('pageTitle', 'fleetType', 'trips', 'routes', 'schedules', 'emptyMessage', 'layout'));
    }

    public function showSeat($id)
    {
        $trip = Trip::with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo', 'assignedVehicle.vehicle', 'bookedTickets'])->where('status', Status::ENABLE)->where('id', $id)->firstOrFail();
        $pageTitle = $trip->title;
        $route = $trip->route;
        $stoppageArr = $trip->route->stoppages;
        $stoppages = Counter::routeStoppages($stoppageArr);
        $busLayout = new BusLayout($trip);
        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }
        return view('Template::book_ticket', compact('pageTitle', 'trip', 'stoppages', 'busLayout', 'layout'));
    }

    public function getTicketPrice(Request $request)
    {
        $ticketPrice = TicketPrice::where('vehicle_route_id', $request->vehicle_route_id)->where('fleet_type_id', $request->fleet_type_id)->with('route')->first();
        $route = $ticketPrice->route;
        $stoppages = $ticketPrice->route->stoppages;
        $trip = Trip::find($request->trip_id);
        $sourcePos = array_search($request->source_id, $stoppages);
        $destinationPos = array_search($request->destination_id, $stoppages);

        $bookedTicket = BookedTicket::where('trip_id', $request->trip_id)->where('date_of_journey', Carbon::parse($request->date)->format('Y-m-d'))->whereIn('status', [Status::BOOKED_APPROVED, Status::BOOKED_PENDING])->get()->toArray();

        $startPoint = array_search($trip->start_from, array_values($trip->route->stoppages));
        $endPoint = array_search($trip->end_to, array_values($trip->route->stoppages));
        if ($startPoint < $endPoint) {
            $reverse = false;
        } else {
            $reverse = true;
        }

        if (!$reverse) {
            $can_go = ($sourcePos < $destinationPos) ? true : false;
        } else {
            $can_go = ($sourcePos > $destinationPos) ? true : false;
        }

        if (!$can_go) {
            $data = [
                'error' => 'Select Pickup Point & Dropping Point Properly'
            ];
            return response()->json($data);
        }
        $sdArray = [$request->source_id, $request->destination_id];
        $getPrice = $ticketPrice->prices()->where('source_destination', json_encode($sdArray))->orWhere('source_destination', json_encode(array_reverse($sdArray)))->first();

        if ($getPrice) {
            $price = $getPrice->price;
        } else {
            $price = [
                'error' => 'Admin may not set prices for this route. So, you can\'t buy ticket for this trip.'
            ];
        }
        $data['bookedSeats'] = $bookedTicket;
        $data['reqSource'] = $request->source_id;
        $data['reqDestination'] = $request->destination_id;
        $data['reverse'] = $reverse;
        $data['stoppages'] = $stoppages;
        $data['price'] = $price;
        return response()->json($data);
    }

    public function bookTicket(Request $request, $id)
    {
        $request->validate([
            "pickup_point" => "required|integer|gt:0",
            "dropping_point" => "required|integer|gt:0",
            "date_of_journey" => "required|date",
            "seats" => "required|string",
            "gender" => "required|integer"
        ], [
            "seats.required" => "Please Select at Least One Seat"
        ]);

        if (!auth()->user()) {
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
        $stoppages = $trip->route->stoppages;
        $source_pos = array_search($request->pickup_point, $stoppages);
        $destination_pos = array_search($request->dropping_point, $stoppages);

        if (!empty($trip->day_off)) {
            if (in_array($dayOff, $trip->day_off)) {
                $notify[] = ['error', 'The trip is not available for ' . $date_of_journey->format('l')];
                return redirect()->back()->withNotify($notify);
            }
        }

        $booked_ticket = BookedTicket::where('trip_id', $id)
            ->where('date_of_journey', Carbon::parse($request->date)->format('Y-m-d'))
            ->whereIn('status', [Status::BOOKED_APPROVED, Status::BOOKED_PENDING])
            ->where('pickup_point', $request->pickup_point)
            ->where('dropping_point', $request->dropping_point)
            ->whereJsonContains('seats', rtrim($request->seats, ","))
            ->get();

        if ($booked_ticket->count() > 0) {
            $notify[] = ['error', 'Those seats are already booked'];
            return redirect()->back()->withNotify($notify);
        }

        $startPoint = array_search($trip->start_from, array_values($trip->route->stoppages));
        $endPoint = array_search($trip->end_to, array_values($trip->route->stoppages));
        if ($startPoint < $endPoint) {
            $reverse = false;
        } else {
            $reverse = true;
        }

        if (!$reverse) {
            $can_go = ($source_pos < $destination_pos) ? true : false;
        } else {
            $can_go = ($source_pos > $destination_pos) ? true : false;
        }

        if (!$can_go) {
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
        $seats = array_filter((explode(',', $request->seats)));
        $unitPrice = getAmount($getPrice->price);
        $pnr_number = getTrx(10);
        $bookedTicket = new BookedTicket();
        $bookedTicket->user_id = auth()->user()->id;
        $bookedTicket->gender = $request->gender;
        $bookedTicket->trip_id = $trip->id;
        $bookedTicket->source_destination = [$request->pickup_point, $request->dropping_point];
        $bookedTicket->pickup_point = $request->pickup_point;
        $bookedTicket->dropping_point = $request->dropping_point;
        $bookedTicket->seats = $seats;
        $bookedTicket->ticket_count = sizeof($seats);
        $bookedTicket->unit_price = $unitPrice;
        $bookedTicket->sub_total = sizeof($seats) * $unitPrice;
        $bookedTicket->date_of_journey = Carbon::parse($request->date_of_journey)->format('Y-m-d');
        $bookedTicket->pnr_number = $pnr_number;
        $bookedTicket->status = Status::BOOKED_REJECTED;
        $bookedTicket->save();
        session()->put('pnr_number', $pnr_number);
        return redirect()->route('user.deposit.index');
    }

    public function ticketSearch(Request $request)
    {
        if ($request->pickup && $request->destination && $request->pickup == $request->destination) {
            $notify[] = ['error', 'Please select pickup point and destination point properly'];
            return redirect()->back()->withNotify($notify);
        }
        if ($request->date_of_journey && Carbon::parse($request->date_of_journey)->format('Y-m-d') < Carbon::now()->format('Y-m-d')) {
            $notify[] = ['error', 'Date of journey can\'t be less than today.'];
            return redirect()->back()->withNotify($notify);
        }

           if ($request->kiosk_id) {
            $ksk_trips = Kiosk::where('id', $request->kiosk_id)->with([
                'counter' => function ($q) {
                    $q->with([
                        'trips' => function ($q) {
                            $q->select('id', 'start_from');
                        },
                    ]);
                },
            ])->get();

            foreach ($ksk_trips as $kiosk) {
                if (isset($kiosk['counter']['trips'])) {
                    foreach ($kiosk['counter']['trips'] as $trip) {
                        $tripIds[] = $trip['id'];
                    }
                }
            }
        }

        $trips_query = Trip::with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo'])->active();
 
        if ($request->kiosk_id) {
            $trips_query->whereIn('id', $tripIds);
        }

        $trips = $trips_query;

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
        $view = $request->kiosk_id ? 'kiosk_booking' : 'ticket';
        return view("Template::$view", compact('pageTitle', 'fleetType', 'trips', 'routes', 'schedules', 'emptyMessage', 'layout'));
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
