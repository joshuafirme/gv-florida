<?php

namespace App\Http\Controllers\Gateway\Paynamics;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Models\Deposit;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\PaynamicsWebhookLog;
use App\Services\Paynamics;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Storage;


class ProcessController extends Controller
{
    public function __construct(private readonly PaymentGatewayService $paymentGateways)
    {
    }

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

    public function bookedQuery($bookedTicket)
    {
        return $bookedTicket->getConflicts();
    }

    public function redirect(Request $request)
    {
        $request->validate([
            'pmethod' => ['required', 'string'],
            'pchannel' => ['required', 'string'],
        ]);

        try {
            $booked_ticket_id = session()->get('booked_ticket_id');

            $ticket = BookedTicket::with('deposit')->findOrFail($booked_ticket_id);
            $isKioskBooking = $ticket->isKioskBooking();
            $this->paymentGateways->validateGatewayCurrency(
                $ticket->deposit->method_code,
                $ticket->deposit->method_currency,
                $isKioskBooking
            );
            $channel = $this->paymentGateways->validatePaynamicsChannel(
                $request->pmethod,
                $request->pchannel,
                $isKioskBooking
            );

            $booked_tickets = $this->bookedQuery($ticket);
            if ($booked_tickets->count() > 0) {
                $notify[] = ['error', "The selected seats are already booked. Please go back and select different seats."];
                return back()->withNotify($notify);
            }
            // $booked_tickets

            $paynamics = new Paynamics(request()->user());
            $paynamics->data = $ticket;
            $transaction = $paynamics->createTransaction($channel);
            $ticket->deposit->pchannel = $channel->code;
            $ticket->deposit->pmethod = $channel->paymentMethod->code;
            $ticket->deposit->save();

            if ($transaction?->response_code == "GR011") { // if req ID is already process or exist.
                $ticket->deposit->trx = generateReqID();
                session()->put('Track', $ticket->deposit->trx);
                $ticket->deposit->save();

                $paynamics = new Paynamics(request()->user());
                $paynamics->data = $ticket;

                $channel = $this->paymentGateways->validatePaynamicsChannel(
                    $request->pmethod,
                    $request->pchannel,
                    $isKioskBooking
                );
                $transaction = $paynamics->createTransaction($channel);
            }

            session()->put('paynamics_request_id', $transaction->request_id);
            session()->put('paynamics_response_id', $transaction->response_id);

            $ticket->seats = session()->has('seats') ? session('seats') : $ticket->seats;
            $ticket->save();
            session()->forget('seats');

            if ($transaction && isset($transaction->payment_action_info)) {
                return redirect()->to($transaction->payment_action_info);
            } else if ($transaction && isset($transaction->direct_otc_info)) {
                return redirect()->to('/user/paynamics/response');
            }
            return $transaction;
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            $notify[] = ['error', 'Unable to start the Paynamics transaction. Please try again.'];
            return back()->withNotify($notify);
        }
    }

    public function response(Request $request)
    {
        $paynamics = new Paynamics($request->user());

        $booked_ticket_id = session()->get('booked_ticket_id');
        $request_id = session('paynamics_request_id');

        $pageTitle = "Payment Details";

        $ticket = BookedTicket::with('deposit')->findOrFail($booked_ticket_id);

        $transaction = $this->getTransaction($request_id);

        $deposit = Deposit::where('trx', $ticket->deposit->trx)->orderBy('id', 'DESC')->firstOrFail();
        $isSuccessfulResponse = ($transaction?->response_code ?? null) === 'GR001';

        if (
            $deposit->status == Status::PAYMENT_INITIATE
            && $isSuccessfulResponse
            && !isset($transaction->direct_otc_info)
        ) {
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

        $deposit->refresh();
        session()->put('Track', $deposit->trx);

        if ((int) $deposit->status === Status::PAYMENT_SUCCESS) {
            return to_route('user.deposit.done');
        }

        if (auth()->user()) {
            $layout = 'layouts.master';
        } else {
            $layout = 'layouts.frontend';
        }
        if (session('kiosk_id')) {
            $layout = 'layouts.kiosk';
        }

        return view('templates/basic/user/payment/response/paynamics', compact('ticket', 'transaction', 'pageTitle', 'layout'));
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
        $requestId = data_get($payload, 'request_id');
        $originalTransactionId = data_get($payload, 'org_trxid')
            ?? data_get($payload, 'org_trxid2')
            ?? data_get($payload, 'original_transaction_id');
        $payReference = data_get($payload, 'pay_reference');

        $log = PaynamicsWebhookLog::create([
            'provider' => 'Paynamics',
            'event_type' => data_get($payload, 'event_type')
                ?? data_get($payload, 'payment_status')
                ?? data_get($payload, 'response_code'),
            'request_id' => $requestId,
            'original_transaction_id' => $originalTransactionId,
            'pay_reference' => $payReference,
            'status' => 'received',
            'payload' => $payload,
            'headers' => $this->safeWebhookHeaders($request),
            'ip_address' => $request->ip(),
            'received_at' => now(),
        ]);

        try {
            $deposit = null;
            if ($requestId || $originalTransactionId || $payReference) {
                $deposit = Deposit::query()
                    ->where(function ($query) use ($requestId, $originalTransactionId, $payReference) {
                        $query->when($requestId, fn ($query) => $query->where('trx', $requestId))
                            ->when($originalTransactionId, fn ($query) => $query->orWhere('trx', $originalTransactionId))
                            ->when($payReference, fn ($query) => $query->orWhere('pay_reference', $payReference));
                    })
                    ->latest('id')
                    ->first();
            }

            $log->deposit_id = $deposit?->id;

            if (data_get($payload, 'response_code') === 'GR001') {
                if (! $deposit) {
                    throw new \RuntimeException('No payment transaction matches this Paynamics webhook.');
                }

                PaymentController::userDataUpdate($deposit);
            }

            $responsePayload = [
                'status' => 'success',
                'payload' => $payload,
            ];

            $log->fill([
                'status' => 'processed',
                'http_status' => 200,
                'response' => $responsePayload,
                'processed_at' => now(),
            ])->save();

            $uid = $requestId ?: $originalTransactionId ?: $payReference ?: now()->format('Y-m-d_H-i-s');
            try {
                Storage::put(
                    'paynamics/webhooks/'.$uid.'-'.$log->id.'.json',
                    json_encode($payload, JSON_PRETTY_PRINT)
                );
            } catch (\Throwable $storageException) {
                report($storageException);
            }

            return response()->json($responsePayload);
        } catch (\Throwable $exception) {
            report($exception);

            $responsePayload = [
                'status' => 'error',
                'message' => $exception instanceof \RuntimeException
                    ? $exception->getMessage()
                    : 'Unable to process Paynamics webhook.',
            ];
            $httpStatus = $exception instanceof \RuntimeException ? 422 : 500;

            $log->fill([
                'status' => 'failed',
                'http_status' => $httpStatus,
                'response' => $responsePayload,
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ])->save();

            return response()->json($responsePayload, $httpStatus);
        }
    }

    private function safeWebhookHeaders(Request $request): array
    {
        return collect($request->headers->all())
            ->except(['authorization', 'cookie', 'x-csrf-token', 'x-xsrf-token'])
            ->map(fn ($value) => is_array($value) && count($value) === 1 ? $value[0] : $value)
            ->all();
    }
}
