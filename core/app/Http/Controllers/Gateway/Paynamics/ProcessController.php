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

        $alias = $deposit->gateway->alias;

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

            $paynamics = new Paynamics($request->user());
            $paynamics->pchannel = $request->pchannel;
            $paynamics->data = $ticket;
            $paynamics_res = $paynamics->createTransaction();

            if (isset($paynamics_res->payment_action_info)) {
                session()->put('paynamics_request_id', $paynamics_res->request_id);
                session()->put('paynamics_response_id', $paynamics_res->response_id);
                return redirect()->to($paynamics_res->payment_action_info);
            }
            abort(500);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function response(Request $request)
    {
        $paynamics = new Paynamics($request->user());
        return $paynamics->queryTransaction();
    }

    public function notification(Request $request)
    {
        $jsonData = json_encode($request, JSON_PRETTY_PRINT);

        // Save to storage/app/data.json
        Storage::disk('public')->put('paynamics.json', $jsonData);
    }
}
