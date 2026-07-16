<?php

namespace App\Http\Controllers\Gateway;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\AdminNotification;
use App\Models\BookedTicket;
use App\Models\Deposit;
use App\Models\Discount;
use App\Models\GatewayCurrency;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Models\UserDiscount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function deposit(Request $request)
    {
        $pnr = session()->get('pnr_number');
        $booked_ticket_id = session()->get('booked_ticket_id');

        if ($request->booked_ticket_id) {
            $booked_ticket_id = $request->booked_ticket_id;
            session()->put('booked_ticket_id', $booked_ticket_id);
        }

        $bookedTicket = BookedTicket::find($booked_ticket_id);

        if (!$bookedTicket) {
            $notify[] = 'Please Try again.';
            return redirect()->route('ticket')->withNotify($notify);
        }

        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method');

        if (request('kiosk_id')) {
            //    $gatewayCurrency->where('method_code', '>=', 1000);
        }

        $gatewayCurrency = $gatewayCurrency->orderby('name')->get();

        // $bookedTicket = $bookedTicket->orderBy('id', 'desc');
        // if (!$booked_ticket_id) {
        //     $bookedTicket->where('user_id', auth()->user()->id);
        // }
        // $bookedTicket = $bookedTicket->first();
        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }
        $discounts = Discount::where('status', Status::ENABLE)->get();
        $pageTitle = 'Payment Methods';
        return view('Template::user.payment.deposit', compact('gatewayCurrency', 'pageTitle', 'bookedTicket', 'layout', 'discounts'));
    }

    public function releaseSeats(Request $request)
    {
        $request->validate([
            'booked_ticket_id' => 'required|integer',
        ]);

        $sessionTicketId = (int) session('booked_ticket_id');

        abort_unless($sessionTicketId && $sessionTicketId === (int) $request->booked_ticket_id, 403);

        $redirectUrl = route('ticket', ['kiosk_id' => session('kiosk_id')]);
        $released = DB::transaction(function () use ($sessionTicketId, &$redirectUrl) {
            $ticket = BookedTicket::with(['deposit', 'trip'])
                ->whereKey($sessionTicketId)
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                return true;
            }

            if ($ticket->user_id && (int) $ticket->user_id !== (int) auth()->id()) {
                abort(403);
            }

            if ($ticket->kiosk_id && (int) $ticket->kiosk_id !== (int) session('kiosk_id')) {
                abort(403);
            }

            if ((int) $ticket->status !== Status::BOOKED_PENDING || $ticket->deposit) {
                return false;
            }

            $redirectUrl = route('ticket.seats', [
                $ticket->trip_id,
                slug($ticket->trip?->title ?: 'trip'),
                'start_from' => $ticket->trip?->start_from,
                'end_to' => $ticket->trip?->end_to,
                'dropping_point' => $ticket->dropping_point,
                'kiosk_id' => $ticket->kiosk_id,
                'date_of_journey' => Carbon::parse($ticket->date_of_journey)->format('m/d/Y'),
            ]);

            $ticket->slipSeriesNumbers()->delete();
            $ticket->delete();

            return true;
        });

        if (!$released) {
            $notify[] = ['error', 'Seats with an existing payment transaction can no longer be released from this page.'];
            return back()->withNotify($notify);
        }

        session()->forget(['pnr_number', 'booked_ticket_id', 'seats', 'Track']);

        $notify[] = ['success', 'Your previous seat selection has been released.'];
        return redirect($redirectUrl)->withNotify($notify);
    }

    public function depositInsert(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'gateway' => 'required',
            'currency' => 'required',
            'passengers' => 'required|string',
        ]);
        $booked_ticket_id = session()->get('booked_ticket_id');
        $bookedTicket = BookedTicket::find($booked_ticket_id);
        if (!$bookedTicket) {
            $notify[] = ['error', 'Invalid booking session. Please select your seats again.'];
            return to_route('ticket')->withNotify($notify);
        }
        $bookedTicket->seats = session()->has('seats') ? session('seats') : $bookedTicket->seats;
        $seats = is_array($bookedTicket->seats) ? $bookedTicket->seats : json_decode($bookedTicket->seats, true);
        $seats = array_values(array_filter($seats ?? []));

        $user = auth()->user();
        $gate = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->where('method_code', $request->gateway)->where('currency', $request->currency)->first();
        if (!$gate) {
            $notify[] = ['error', 'Invalid gateway'];
            return back()->withNotify($notify);
        }

        $passengers = json_decode($request->passengers, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($passengers)) {
            $notify[] = ['error', 'Passenger details are invalid. Please review each selected seat.'];
            return back()->withNotify($notify);
        }

        $discounts = Discount::where('status', Status::ENABLE)->get()->keyBy('id');
        $manifest = [];
        $discountedPassengers = [];
        $discountAmount = 0;
        $unitPrice = getAmount($bookedTicket->unit_price);

        foreach ($seats as $seat) {
            $passenger = collect($passengers)->firstWhere('seat', $seat);

            if (!$passenger) {
                $notify[] = ['error', "Please assign passenger details for seat {$seat}."];
                return back()->withNotify($notify);
            }

            $passengerType = $passenger['passenger_type'] ?? 'regular';
            $name = trim($passenger['name'] ?? '');
            $idNumber = trim($passenger['id_number'] ?? '');
            $discountId = isset($passenger['discount_id']) ? (int) $passenger['discount_id'] : null;
            $discount = null;
            $seatDiscount = 0;

            if ($passengerType === 'discounted') {
                $discount = $discounts->get($discountId);

                if (!$discount) {
                    $notify[] = ['error', "Please select a valid discount type for seat {$seat}."];
                    return back()->withNotify($notify);
                }

                if ($name === '' || $idNumber === '') {
                    $notify[] = ['error', "Discounted passengers must provide a name and ID number for seat {$seat}."];
                    return back()->withNotify($notify);
                }

                $seatDiscount = $unitPrice * ($discount->percentage / 100);
                $discountAmount += $seatDiscount;
            }

            $entry = [
                'seat' => $seat,
                'name' => $name,
                'passenger_type' => $passengerType,
                'discount_id' => $discount?->id,
                'discount_name' => $discount?->name,
                'discount_percentage' => $discount ? getAmount($discount->percentage) : 0,
                'id_number' => $passengerType === 'discounted' ? $idNumber : null,
                'base_fare' => getAmount($unitPrice),
                'discount_amount' => getAmount($seatDiscount),
                'fare' => getAmount($unitPrice - $seatDiscount),
            ];

            $manifest[] = $entry;

            if ($passengerType === 'discounted') {
                $discountedPassengers[] = $entry;
            }
        }

        if (count($manifest) !== count($seats)) {
            $notify[] = ['error', 'Each selected seat must have one passenger assignment.'];
            return back()->withNotify($notify);
        }

        if (count($discountedPassengers) > 0 && !$request->boolean('discount_authorized')) {
            $notify[] = ['error', 'Discount authorization is required before proceeding to payment.'];
            return back()->withNotify($notify);
        }

        if (count($discountedPassengers) > 0 && !$request->authorization_reference) {
            $notify[] = ['error', 'Discount authorization details are incomplete.'];
            return back()->withNotify($notify);
        }

        $bookedTicket->passenger_manifest = $manifest;
        $bookedTicket->save();

        $booked_tickets = $bookedTicket->getConflicts();

        // return $booked_tickets;
        if ($booked_tickets->count() > 0) {
            $notify[] = ['error', "The selected seats are already booked. Please go back and select different seats."];
            return back()->withNotify($notify);
        }

        // if ($gate->min_amount > $bookedTicket->sub_total || $gate->max_amount < $bookedTicket->sub_total) {
        //     $notify[] = ['error', 'Please follow payment limit'];
        //     return back()->withNotify($notify);
        // }

        $discountedSubtotal = max($bookedTicket->sub_total - $discountAmount, 0);
        $charge = $gate->fixed_charge + ($discountedSubtotal * $gate->percent_charge / 100);
        $payable = $discountedSubtotal + $charge;
        $finalAmount = $payable * $gate->rate;


        $deposit = Deposit::where('booked_ticket_id', $bookedTicket->id)->first();
        if (!$deposit) {
            $deposit = new Deposit();

            $deposit->trx = generateReqID();
        }
        $deposit->user_id = $user ? $user->id : null;
        $deposit->booked_ticket_id = $bookedTicket->id;
        $deposit->method_code = $gate->method_code;
        $deposit->method_currency = strtoupper($gate->currency);
        $deposit->amount = $bookedTicket->sub_total;
        $deposit->charge = $charge;
        $deposit->rate = $gate->rate;
        $deposit->btc_amount = 0;
        $deposit->btc_wallet = "";
        $deposit->status = Status::PAYMENT_INITIATE;
        $deposit->expiry_limit = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' + 1 hour'));
        $deposit->success_url = route('user.deposit.done');
        $deposit->failed_url = urlPath('ticket');
        $deposit->final_amount = $finalAmount;
        $deposit->save();

        if (count($discountedPassengers) > 0) {
            $user_discount = UserDiscount::where('deposit_id', $deposit->id)->firstOrNew();
            $user_discount->deposit_id = $deposit->id;
            $user_discount->percentage = collect($discountedPassengers)->avg('discount_percentage') ?: 0;
            $user_discount->amount = getAmount($discountAmount);
            $user_discount->description = collect($discountedPassengers)->pluck('discount_name')->filter()->unique()->implode(', ');
            $user_discount->id_number = collect($discountedPassengers)->pluck('id_number')->filter()->implode(', ');
            $user_discount->passenger_name = collect($discountedPassengers)->pluck('name')->filter()->implode(', ');
            $user_discount->passenger_manifest = $discountedPassengers;
            $user_discount->authorization_method = $request->authorization_method;
            $user_discount->authorized_by_admin_id = $request->authorized_by_admin_id;
            $user_discount->authorized_by_name = $request->authorized_by_name;
            $user_discount->authorization_reference = $deposit->trx . ' | ' . $request->authorization_reference;
            $user_discount->authorized_at = now();
            $user_discount->save();
        } else {
            UserDiscount::where('deposit_id', $deposit->id)->delete();
        }

        session()->put('Track', $deposit->trx);

        if (strtolower($gate->name) === 'cash') {
            $deposit->status = Status::PAYMENT_PENDING;
            $deposit->save();

            return to_route('user.deposit.done');
        }

        return to_route('user.deposit.confirm');
    }

    public function done()
    {
        $track = session()->get('Track');
        $deposit = Deposit::where('trx', $track)->with(['bookedTicket.trip.fleetType', 'bookedTicket.pickup', 'bookedTicket.drop', 'userDiscount'])->firstOrFail();

        if (!in_array($deposit->status, [Status::PAYMENT_PENDING, Status::PAYMENT_SUCCESS])) {
            return to_route('user.deposit.confirm');
        }

        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }

        if (session('kiosk_id')) {
            $layout = 'layouts.kiosk';
        }

        $ticket = $deposit->bookedTicket;
        $pageTitle = 'Booking Voucher';

        return view('Template::user.payment.done', compact('deposit', 'ticket', 'pageTitle', 'layout'));
    }


    public function appDepositConfirm($hash)
    {
        try {
            $id = decrypt($hash);
        } catch (\Exception $ex) {
            abort(404);
        }
        $data = Deposit::where('id', $id)->where('status', Status::PAYMENT_INITIATE)->orderBy('id', 'DESC')->firstOrFail();
        $user = User::findOrFail($data->user_id);
        auth()->login($user);
        return to_route('user.deposit.confirm');
    }


    public function depositConfirm()
    {
        $track = session()->get('Track');

        $deposit = Deposit::where('trx', $track)->where('status', Status::PAYMENT_INITIATE)->orderBy('id', 'DESC')->with('gateway')->firstOrFail();

        if ($deposit->method_code >= 1000) {
            return to_route('user.deposit.manual.confirm');
        }


        $dirName = $deposit->gateway->alias;
        $new = __NAMESPACE__ . '\\' . ucfirst($dirName) . '\\ProcessController';

        $data = $new::process($deposit);
        $data = json_decode($data);


        if (isset($data->error)) {
            $notify[] = ['error', $data->message];
            return back()->withNotify($notify);
        }
        if (isset($data->redirect)) {
            return redirect($data->redirect_url);
        }

        // for Stripe V3
        if (@$data->session) {
            $deposit->btc_wallet = $data->session->id;
            $deposit->save();
        }


        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }
        if (session('kiosk_id')) {
            $layout = 'layouts.kiosk';
        }

        $ticket = BookedTicket::where('id', $deposit->booked_ticket_id)->with(['trip', 'pickup', 'drop'])->first();

        $pageTitle = 'Confirm Payment';
        return view("Template::$data->view", compact('data', 'pageTitle', 'deposit', 'ticket', 'layout'));
    }


    public static function userDataUpdate($deposit, $isManual = null)
    {
        if (!in_array($deposit->status, [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING])) {
            return;
        }

        $payment = DB::transaction(function () use ($deposit) {
            $deposit = Deposit::whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if (!in_array($deposit->status, [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING])) {
                return null;
            }

            $bookedTicket = BookedTicket::whereKey($deposit->booked_ticket_id)->lockForUpdate()->firstOrFail();

            $deposit->status = Status::PAYMENT_SUCCESS;
            $deposit->save();
            $bookedTicket->status = 1;
            $bookedTicket->save();
            $bookedTicket->ensureSlipSeriesNumbers();

            return [$deposit, $bookedTicket];
        });

        if (!$payment) {
            return;
        }

        [$deposit, $bookedTicket] = $payment;
        $user = User::find($deposit->user_id);

        if (!$isManual && !$bookedTicket->kiosk_id) {
            $adminNotification = new AdminNotification();
            $adminNotification->user_id = isset($user->id) ? $user->id : null;
            $adminNotification->title = 'Payment successful via ' . $deposit->gatewayCurrency()->name;
            $adminNotification->click_url = urlPath('admin.vehicle.ticket.booked');
            $adminNotification->save();
        }


        $general = GeneralSetting::first();

        if ($user) {
            notify($user, $isManual ? 'PAYMENT_APPROVE' : 'PAYMENT_COMPLETE', [
                'method_name' => $deposit->gatewayCurrency()->name,
                'method_currency' => $deposit->method_currency,
                'method_amount' => showAmount($deposit->final_amount, currencyFormat: false),
                'amount' => showAmount($deposit->amount, currencyFormat: false),
                'charge' => showAmount($deposit->charge, currencyFormat: false),
                'currency' => $general->cur_text,
                'rate' => showAmount($deposit->rate, currencyFormat: false),
                'trx' => $deposit->trx,
                'journey_date' => showDateTime($bookedTicket->date_of_journey, 'd m, Y'),
                'seats' => implode(',', $bookedTicket->seats),
                'total_seats' => sizeof($bookedTicket->seats),
                'source' => $bookedTicket->pickup->name,
                'destination' => $bookedTicket->drop->name,
                'ticket' => $bookedTicket,
                'has_file' => true
            ]);
        }
    }

    public function manualDepositConfirm()
    {
        $track = session()->get('Track');
        $data = Deposit::with('gateway')->where('status', Status::PAYMENT_INITIATE)->where('trx', $track)->first();
        abort_if(!$data, 404);
        if ($data->method_code > 999) {
            $pageTitle = 'Confirm Payment';
            $method = $data->gatewayCurrency();
            $gateway = $method->method;
            if (auth()->user()) {
                $layout = 'layouts.master';
            } else {
                $layout = 'layouts.frontend';
            }
            if (session('kiosk_id')) {
                $layout = 'layouts.kiosk';
            }
            return view('Template::user.payment.manual', compact('data', 'pageTitle', 'method', 'gateway', 'layout'));
        }
        abort(404);
    }

    public function manualDepositUpdate(Request $request)
    {
        $track = session()->get('Track');

        $data = Deposit::with('gateway')->whereIn('status', [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING])->where('trx', $track)->first();
        abort_if(!$data, 404);
        $gatewayCurrency = $data->gatewayCurrency();
        $gateway = $gatewayCurrency->method;
        $formData = $gateway->form->form_data;

        $formProcessor = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $userData = $formProcessor->processFormData($request, $formData);


        $data->detail = $userData;
        $data->status = Status::PAYMENT_PENDING;
        $data->save();

        $bookedTicket = BookedTicket::find($data->booked_ticket_id);
        $bookedTicket->status = Status::BOOKED_PENDING;
        $bookedTicket->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $data->user ? $data->user->id : 0;
        $adminNotification->title = 'Payment request from ';
        $adminNotification->click_url = urlPath('admin.deposit.details', $data->id);
        $adminNotification->save();

        notify($data->user, 'PAYMENT_REQUEST', [
            'method_name' => $data->gatewayCurrency()->name,
            'method_currency' => $data->method_currency,
            'method_amount' => showAmount($data->final_amount, currencyFormat: false),
            'amount' => showAmount($data->amount, currencyFormat: false),
            'charge' => showAmount($data->charge, currencyFormat: false),
            'rate' => showAmount($data->rate, currencyFormat: false),
            'trx' => $data->trx,
            'journey_date' => showDateTime($bookedTicket->date_of_journey, 'd m, Y'),
            'seats' => implode(',', $bookedTicket->seats),
            'total_seats' => sizeof($bookedTicket->seats),
            'source' => $bookedTicket->pickup->name,
            'destination' => $bookedTicket->drop->name
        ]);


        $notify[] = ['success', 'You have deposit request has been taken'];

        if ($bookedTicket->kiosk_id) {
            return view('Template::user.print_ticket_kiosk', ['ticket' => $bookedTicket, 'pageTitle' => 'print']);
            //     return to_route('user.ticket.print', $bookedTicket->id)->withNotify($notify);

        }

        return to_route('user.ticket.history')->withNotify($notify);
    }
}
