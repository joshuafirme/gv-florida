<?php

namespace App\Http\Controllers\Gateway;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\AdminNotification;
use App\Models\BookedTicket;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function deposit()
    {
        $pnr = session()->get('pnr_number');
        $bookedTicket = BookedTicket::where('pnr_number', $pnr)->first();

        if (!$bookedTicket) {
            $notify[] = 'Please Try again.';
            return redirect()->route('ticket')->withNotify($notify);
        }

        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method');

        if (request('kiosk_id')) {
            $gatewayCurrency->where('method_code', '>=', 1000);
        }

        $gatewayCurrency = $gatewayCurrency->orderby('name')->get();

        $booked_ticket_id = session()->get('booked_ticket_id');
        $bookedTicket = BookedTicket::orderBy('id', 'desc');
        if ($booked_ticket_id) {
            $bookedTicket->find($booked_ticket_id);
        } else {
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
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'gateway' => 'required',
            'currency' => 'required',
        ]);

        $pnr = session()->get('pnr_number');
        $bookedTicket = BookedTicket::where('pnr_number', $pnr)->first();

        $user = auth()->user();
        $gate = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->where('method_code', $request->gateway)->where('currency', $request->currency)->first();
        if (!$gate) {
            $notify[] = ['error', 'Invalid gateway'];
            return back()->withNotify($notify);
        }

        if ($gate->min_amount > $bookedTicket->sub_total || $gate->max_amount < $bookedTicket->sub_total) {
            $notify[] = ['error', 'Please follow payment limit'];
            return back()->withNotify($notify);
        }

        $charge = $gate->fixed_charge + ($bookedTicket->sub_total * $gate->percent_charge / 100);
        $payable = $bookedTicket->sub_total + $charge;
        $finalAmount = $payable * $gate->rate;

        $deposit = Deposit::where('booked_ticket_id', $bookedTicket->id)->first();
        if (!$deposit) {
            $deposit = new Deposit();
        }
        $date = date('Ymd');
        $req_id = "GVF-$date-" . substr(uniqid(), 0, 7);
        $deposit->user_id = $user ? $user->id : null;
        $deposit->booked_ticket_id = $bookedTicket->id;
        $deposit->method_code = $gate->method_code;
        $deposit->method_currency = strtoupper($gate->currency);
        $deposit->amount = $bookedTicket->sub_total;
        $deposit->charge = $charge;
        $deposit->rate = $gate->rate;
        $deposit->final_amount = $finalAmount;
        $deposit->btc_amount = 0;
        $deposit->btc_wallet = "";
        // $deposit->trx = getTrx();
        $deposit->trx = $req_id;
        $deposit->status = Status::PAYMENT_INITIATE;
        $deposit->success_url = urlPath('user.ticket.history');
        $deposit->failed_url = urlPath('ticket');
        $deposit->save();

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
        session()->put('Track', $data->trx);
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
        $new = __NAMESPACE__ . '\\' . $dirName . '\\ProcessController';

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


        $pnr = session()->get('pnr_number');
        $ticket = BookedTicket::where('pnr_number', $pnr)->first();

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
                $adminNotification->user_id = $user->id;
                $adminNotification->title = 'Payment successful via ' . $deposit->gatewayCurrency()->name;
                $adminNotification->click_url = urlPath('admin.vehicle.ticket.booked');
                $adminNotification->save();
            }


            $general = GeneralSetting::first();

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
                'destination' => $bookedTicket->drop->name
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


    public function paynamicsRedirect(Request $request)
    {
        $pmethods = json_decode(file_get_contents('assets/admin/paynamics_pmethod.json'))->pmethod;
        $pmethod = '';
        foreach ($pmethods as $item) {
            foreach ($item->types as $type) {
                if ($request->pchannel == $type->value) {
                    $pmethod = $item->value;
                    break;
                }
            }
        }

        $user = $request->user();
        $date = date('Ymd');
        $booked_ticket_id = session()->get('booked_ticket_id');

        $ticket = BookedTicket::find($booked_ticket_id);

        $orders = [];
        $orders[] = [
            "itemname" => "PNR: $ticket->pnr_number Seats: " . implode(', ', $ticket->seats),
            "quantity" => 1,
            "unitprice" => $ticket->deposit->final_amount,
            "totalprice" => $ticket->deposit->final_amount
        ];

        $base_url = config('app.url');
        $merchantid = config('paynamics.merchant_id');
        $mkey = config('paynamics.merchant_key');
        $basicUser = config('paynamics.basic_auth_user');
        $basicPass = config('paynamics.basic_auth_pw');

        $req_id = "GVF-$date-" . substr(uniqid(), 0, 7);

        $data = [
            "transaction" => [
                "request_id" => $req_id,
                "notification_url" => "$base_url/ipn/paynamics",
                "response_url" => "$base_url/paynamics/response",
                "cancel_url" => "$base_url/paynamics/cancel",
                "pmethod" => $pmethod,
                "pchannel" => $request->pchannel,
                "payment_action" => "url_link",
                "collection_method" => "single_pay",
                "payment_notification_status" => "1",
                "payment_notification_channel" => "1",
                "amount" => $ticket->deposit->final_amount,
                "currency" => "PHP",
                "trx_type" => "sale",
                // "mtac_url" => ""
            ],
            "customer_info" => [
                "fname" => $user->firstname,
                "lname" => $user->lastname,
                "mname" => "",
                "email" => $user->email,
                "phone" => $user->mobile,
                "mobile" => $user->mobile,
                "dob" => ""
            ],
            "order_details" => [
                "orders" => $orders,
                "subtotalprice" => $ticket->deposit->final_amount,
                "shippingprice" => "0.00",
                "discountamount" => "0.00",
                "totalorderamount" => $ticket->deposit->final_amount
            ]
        ];

        // Generate Transaction Signature
        $rawTrx = $merchantid .
            ($data["transaction"]["request_id"] ?? '') .
            ($data["transaction"]["notification_url"] ?? '') .
            ($data["transaction"]["response_url"] ?? '') .
            ($data["transaction"]["cancel_url"] ?? '') .
            ($data["transaction"]["pmethod"] ?? '') .
            ($data["transaction"]["payment_action"] ?? '') .
            ($data["transaction"]["schedule"] ?? '') .
            ($data["transaction"]["collection_method"] ?? '') .
            ($data["transaction"]["deferred_period"] ?? '') .
            ($data["transaction"]["deferred_time"] ?? '') .
            ($data["transaction"]["dp_balance_info"] ?? '') .
            ($data["transaction"]["amount"] ?? '') .
            ($data["transaction"]["currency"] ?? '') .
            ($data["transaction"]["descriptor_note"] ?? '') .
            ($data["transaction"]["payment_notification_status"] ?? '') .
            ($data["transaction"]["payment_notification_channel"] ?? '') .
            $mkey;

        $signatureTrx = hash('sha512', $rawTrx);
        $data["transaction"]["signature"] = $signatureTrx;

        // Generate Customer Signature
        $rawCustomer = ($data["customer_info"]["fname"] ?? '') .
            ($data["customer_info"]["lname"] ?? '') .
            ($data["customer_info"]["mname"] ?? '') .
            ($data["customer_info"]["email"] ?? '') .
            ($data["customer_info"]["phone"] ?? '') .
            ($data["customer_info"]["mobile"] ?? '') .
            ($data["customer_info"]["dob"] ?? '') .
            $mkey;

        $signatureCustomer = hash('sha512', $rawCustomer);
        $data["customer_info"]["signature"] = $signatureCustomer;

        // Convert to JSON
        $jsonPayload = json_encode($data);

        // cURL Request to Paynamics
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://payin.payserv.net/paygate/transactions/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Basic " . base64_encode("$basicUser:$basicPass")
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            curl_close($ch);
            $json_res = json_decode($response);
            if (isset($json_res->payment_action_info)) {
                return redirect()->to($json_res->payment_action_info);
            }
            return $json_res;
        }
    }
}
