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
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

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

        $bookedTicket = $bookedTicket->orderBy('id', 'desc');
        if (!$booked_ticket_id) {
            $bookedTicket->where('user_id', auth()->user()->id);
        }
        $bookedTicket = $bookedTicket->first();
        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }
        $pageTitle = 'Payment Methods';
        return view('Template::user.payment.deposit', compact('gatewayCurrency', 'pageTitle', 'bookedTicket', 'layout'));
    }

    public function depositInsert(Request $request)
    {
        $discount_id = $request->discount_id;
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'gateway' => 'required',
            'currency' => 'required',
        ]);
        $booked_ticket_id = session()->get('booked_ticket_id');
        $bookedTicket = BookedTicket::find($booked_ticket_id);

        $user = auth()->user();
        $gate = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->where('method_code', $request->gateway)->where('currency', $request->currency)->first();
        if (!$gate) {
            $notify[] = ['error', 'Invalid gateway'];
            return back()->withNotify($notify);
        }

        // if ($gate->min_amount > $bookedTicket->sub_total || $gate->max_amount < $bookedTicket->sub_total) {
        //     $notify[] = ['error', 'Please follow payment limit'];
        //     return back()->withNotify($notify);
        // }

        $charge = $gate->fixed_charge + ($bookedTicket->sub_total * $gate->percent_charge / 100);
        $payable = $bookedTicket->sub_total + $charge;
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
        $deposit->success_url = urlPath('user.ticket.history');
        $deposit->failed_url = urlPath('ticket');
        $deposit->save();

        if ($discount_id) {
            $discount = Discount::find($discount_id);
            $user_discount = UserDiscount::where('deposit_id', $deposit->id)->firstOrNew();
            $user_discount->deposit_id = $deposit->id;
            $user_discount->percentage = $discount->percentage;
            $user_discount->amount = $finalAmount * ($discount->percentage / 100);
            $user_discount->save();
            $deposit->final_amount = $finalAmount - $user_discount->amount;
            $deposit->save();
        }

        session()->put('Track', $deposit->trx);

        return to_route('user.deposit.confirm');
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


        $ticket = BookedTicket::where('id', $deposit->booked_ticket_id)->with(['trip', 'pickup', 'drop'])->first();

        $pageTitle = 'Confirm Payment';
        return view("Template::$data->view", compact('data', 'pageTitle', 'deposit', 'ticket'));
    }


    public static function userDataUpdate($deposit, $isManual = null)
    {
        if ($deposit->status == Status::PAYMENT_INITIATE || $deposit->status == Status::PAYMENT_PENDING) {

            $deposit->status = Status::PAYMENT_SUCCESS;
            $deposit->save();
            $user = User::find($deposit->user_id);

            $bookedTicket = BookedTicket::where('id', $deposit->booked_ticket_id)->first();
            $bookedTicket->status = 1;
            $bookedTicket->save();

            if (!$isManual) {
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
        $data = Deposit::with('gateway')->where('status', Status::PAYMENT_INITIATE)->where('trx', $track)->first();
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

        $bookedTicket = BookedTicket::where('id', $data->booked_ticket_id)->first();
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
            return to_route('user.ticket.print', $bookedTicket->id)->withNotify($notify);
        }

        return to_route('user.ticket.history')->withNotify($notify);
    }
}
