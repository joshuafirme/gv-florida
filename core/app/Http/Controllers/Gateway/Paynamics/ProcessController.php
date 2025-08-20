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

            if ($transaction->response_code == "GR011") { // if req ID is already process or exist.
                $ticket->deposit->trx = generateReqID();
                session()->put('Track', $ticket->deposit->trx);
                $ticket->deposit->save();

                $paynamics = new Paynamics(request()->user());
                $paynamics->pchannel = request()->pchannel;
                $paynamics->data = $ticket;

                $transaction = $paynamics->createTransaction();
            }

            if ($transaction && isset($transaction->payment_action_info)) {
                session()->put('paynamics_request_id', $transaction->request_id);
                session()->put('paynamics_response_id', $transaction->response_id);

                return redirect()->to($transaction->payment_action_info);
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

        $pageTitle = "Transaction";

        $ticket = BookedTicket::find($booked_ticket_id);

        $transaction = $this->getTransaction($request_id);

        $deposit = Deposit::where('trx', $ticket->deposit->trx)->orderBy('id', 'DESC')->first();

        if ($deposit->status == Status::PAYMENT_INITIATE) {
            PaymentController::userDataUpdate($deposit);
        }

        $pageTitle = $transaction->response_message;

        return view('templates/basic/user/payment/response/paynamics', compact('transaction', 'pageTitle'));
    }

    public function getTransaction($request_id)
    {
        // $path = "paynamics/$request_id.json";
        // if (Storage::exists($path)) {
        //     $transaction = json_decode(Storage::get($path));
        // } else {

        // }
        $paynamics = new Paynamics(request()->user());
        $transaction = $paynamics->queryTransaction();
        //Storage::put($path, json_encode($transaction));
        return $transaction;
    }

    public function notification(Request $request)
    {
        $jsonData = json_encode($request, JSON_PRETTY_PRINT);

        // Save to storage/app/data.json
        Storage::disk('public')->put('paynamics.json', $jsonData);
    }
}
