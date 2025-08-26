<?php

namespace App\Http\Controllers\Gateway\Paynamics;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Models\Deposit;
use App\Http\Controllers\Gateway\PaymentController;
use App\Services\Paynamics;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Storage;


class ProcessController extends Controller
{
    /*
     * Stripe Gateway
     */
    public static function process($deposit)
    {

        $alias = ucfirst($deposit->gateway->alias);

        $send['track'] = $deposit->trx;
        $send['view'] = 'user.payment.' . $alias;
        $send['method'] = 'post';
        $send['url'] = route('ipn.' . $alias);

        return json_encode($send);
    }

    public function redirect(Request $request)
    {
        try {
            $booked_ticket_id = session()->get('booked_ticket_id');

            $ticket = BookedTicket::find($booked_ticket_id);

            $paynamics = new Paynamics(request()->user());
            $paynamics->pchannel = request()->pchannel;
            $paynamics->data = $ticket;
            $transaction = $paynamics->createTransaction();
            $ticket->deposit->pchannel = $paynamics->pchannel;
            $ticket->deposit->pmethod = getPaynamicsPMethod($paynamics->pchannel);
            $ticket->deposit->save();

            if ($transaction->response_code == "GR011") { // if req ID is already process or exist.
                $ticket->deposit->trx = generateReqID();
                session()->put('Track', $ticket->deposit->trx);
                $ticket->deposit->save();

                $paynamics = new Paynamics(request()->user());
                $paynamics->pchannel = request()->pchannel;
                $paynamics->data = $ticket;

                $transaction = $paynamics->createTransaction();
            }

            session()->put('paynamics_request_id', $transaction->request_id);
            session()->put('paynamics_response_id', $transaction->response_id);

            if ($transaction && isset($transaction->payment_action_info)) {
                return redirect()->to($transaction->payment_action_info);
            } else if ($transaction && isset($transaction->direct_otc_info)) {
                return redirect()->to('/user/paynamics/response');
            }
            return $transaction;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function response(Request $request)
    {
        $paynamics = new Paynamics($request->user());

        $booked_ticket_id = session()->get('booked_ticket_id');
        $request_id = session('paynamics_request_id');

        $pageTitle = "Payment Details";

        $ticket = BookedTicket::find($booked_ticket_id);

        $transaction = $this->getTransaction($request_id);

        $deposit = Deposit::where('trx', $ticket->deposit->trx)->orderBy('id', 'DESC')->first();

        if ($deposit->status == Status::PAYMENT_INITIATE && !isset($transaction->direct_otc_info)) {
            PaymentController::userDataUpdate($deposit);
        } else if (isset($transaction->direct_otc_info) && $transaction->pay_reference != $deposit->pay_reference) {
            $deposit->status = Status::PAYMENT_PENDING;
            $deposit->expiry_limit = $transaction->expiry_limit;
            $deposit->pay_reference = $transaction->pay_reference;
            $deposit->save();

            $bookedTicket = BookedTicket::find($deposit->booked_ticket_id);
            $bookedTicket->status = Status::BOOKED_PENDING;
            $bookedTicket->save();
        }

        return view('templates/basic/user/payment/response/paynamics', compact('transaction', 'pageTitle'));
    }

    public function getTransaction($request_id)
    {
        $path = "paynamics/$request_id.json";
        if (Storage::exists($path)) {
            $transaction = json_decode(Storage::get($path));
        } else {
            $paynamics = new Paynamics(request()->user());
            $transaction = $paynamics->queryTransaction();
            Storage::put($path, json_encode($transaction));
        }
        return $transaction;
    }

    public function getPaymentDetails($request_id)
    {
        $path = "paynamics/{$request_id}.json";
        if (Storage::exists($path)) {
            $data = json_decode(Storage::get($path));
            if (isset($data->pchannel)) {
                $data->pchannel_name = getPaynamicsPChannel($data->pchannel, true);
            } else if (isset($data->direct_otc_info)) {
                $data->pchannel_name = getPaynamicsPChannel($data->direct_otc_info[0]->pay_channel, true);
            }
            return $data;
        }
        return abort(404, "File not found");
    }

    public function notification(Request $request)
    {
        $payload = $request->all();
        $uid = now()->format('Y-m-d_H-i-s');

        $deposit = Deposit::orderBy('id', 'DESC');
        if (isset($payload['request_id'])) {
            $uid = $payload['request_id'];
            $deposit->where('trx', $uid);
        } else if (isset($payload['pay_reference'])) {
            $uid = $payload['pay_reference'];
            $deposit->where('pay_reference', $uid);
        }

        $deposit = $deposit->first();

        if ($payload['response_code'] == 'GR001') {
             PaymentController::userDataUpdate($deposit);
        }

        // if ($deposit->status == Status::PAYMENT_INITIATE && !isset($transaction->direct_otc_info)) {
        //     PaymentController::userDataUpdate($deposit);
        // } else if (isset($transaction->direct_otc_info) && $transaction->pay_reference != $deposit->pay_reference) {
        //     $deposit->status = Status::PAYMENT_PENDING;
        //     $deposit->expiry_limit = $transaction->expiry_limit;
        //     $deposit->pay_reference = $transaction->pay_reference;
        //     $deposit->save();

        //     $bookedTicket = BookedTicket::find($deposit->booked_ticket_id);
        //     $bookedTicket->status = Status::BOOKED_PENDING;
        //     $bookedTicket->save();
        // }

        $fileName = 'paynamics/webhooks/' . $uid . '.json';

        Storage::put($fileName, json_encode($payload));

        return response()->json([
            'status' => 'success',
            'payload' => $payload
        ]);
    }
}
