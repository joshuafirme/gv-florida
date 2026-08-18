<?php

namespace App\Http\Controllers\Gateway\Paynamics;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Models\Deposit;
use App\Http\Controllers\Gateway\PaymentController;
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
                $ticket->deposit->status = Status::PAYMENT_PENDING;
                $ticket->deposit->expiry_limit = $transaction->expiry_limit ?? $ticket->deposit->expiry_limit;
                $ticket->deposit->pay_reference = $transaction->pay_reference ?? $ticket->deposit->pay_reference;
                $ticket->deposit->save();

                $ticket->status = Status::BOOKED_PENDING;
                $ticket->save();

                Storage::put(
                    "paynamics/direct-otc/{$ticket->deposit->trx}.json",
                    json_encode($transaction)
                );

                return to_route('user.paynamics.response', [
                    'request_id' => $ticket->deposit->trx,
                ]);
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
        $deposit = $this->callbackDeposit($request);
        if (!$deposit) {
            return $this->callbackUnavailable('response');
        }

        $ticket = $deposit->bookedTicket;
        $this->restoreBookingSession($ticket, $deposit);

        $verifiedTransaction = null;

        try {
            $verifiedTransaction = $this->getTransaction($deposit->trx);
        } catch (\Throwable $exception) {
            report($exception);
        }

        $transaction = $verifiedTransaction;

        if (!$transaction && $request->filled('response_code')) {
            $transaction = (object) $request->only([
                'request_id',
                'response_code',
                'response_message',
                'response_advise',
                'pay_reference',
                'pchannel',
                'timestamp',
            ]);
        }

        $isSuccessfulResponse = ($verifiedTransaction?->response_code ?? null) === 'GR001';

        if (
            in_array((int) $deposit->status, [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING], true)
            && $isSuccessfulResponse
            && !isset($verifiedTransaction->direct_otc_info)
        ) {
            PaymentController::userDataUpdate($deposit);
        }

        $deposit->refresh();
        $ticket->refresh();
        $callbackState = (int) $deposit->status === Status::PAYMENT_SUCCESS
            ? 'success'
            : ((int) $deposit->status === Status::PAYMENT_PENDING ? 'pending' : 'failed');
        $callbackDetails = $this->callbackDetails($deposit, $transaction, $callbackState);
        session()->put('paynamics_callback_details', $callbackDetails);

        if (
            (int) $deposit->status === Status::PAYMENT_SUCCESS
            || ($ticket->isKioskBooking() && (int) $deposit->status === Status::PAYMENT_PENDING)
        ) {
            return to_route('user.deposit.done');
        }

        $layout = $this->bookingLayout($ticket);
        $pageTitle = $callbackState === 'pending' ? 'Payment Pending' : 'Payment Response';

        return view('templates/basic/user/payment/response/paynamics', compact(
            'ticket',
            'deposit',
            'transaction',
            'callbackDetails',
            'callbackState',
            'pageTitle',
            'layout'
        ));
    }

    public function cancel(Request $request)
    {
        $deposit = $this->callbackDeposit($request);
        if (!$deposit) {
            return $this->callbackUnavailable('cancelled');
        }

        $ticket = $deposit->bookedTicket;
        $this->restoreBookingSession($ticket, $deposit);

        if ((int) $deposit->status === Status::PAYMENT_SUCCESS) {
            session()->put('paynamics_callback_details', $this->callbackDetails($deposit, null, 'success'));
            return to_route('user.deposit.done');
        }

        $transaction = (object) $request->only([
            'request_id',
            'response_code',
            'response_message',
            'response_advise',
            'pay_reference',
            'pchannel',
            'timestamp',
        ]);
        $callbackState = 'cancelled';
        $callbackDetails = $this->callbackDetails($deposit, $transaction, $callbackState);
        session()->put('paynamics_callback_details', $callbackDetails);
        $layout = $this->bookingLayout($ticket);
        $pageTitle = 'Payment Cancelled';

        return view('templates/basic/user/payment/response/paynamics', compact(
            'ticket',
            'deposit',
            'transaction',
            'callbackDetails',
            'callbackState',
            'pageTitle',
            'layout'
        ));
    }

    public function getTransaction(?string $requestId)
    {
        if (!$requestId) {
            return null;
        }

        $paynamics = new Paynamics(request()->user());
        $transaction = $paynamics->queryTransaction($requestId);

        if ($transaction) {
            Storage::put("paynamics/{$requestId}.json", json_encode($transaction));
            return $transaction;
        }

        $directOtcPath = "paynamics/direct-otc/{$requestId}.json";

        return Storage::exists($directOtcPath)
            ? json_decode(Storage::get($directOtcPath))
            : null;
    }

    private function callbackDeposit(Request $request): ?Deposit
    {
        $transactionIds = collect([
            $request->query('request_id'),
            $request->input('request_id'),
            $request->input('org_trxid'),
            $request->input('org_trxid2'),
            $request->input('original_transaction_id'),
            session('paynamics_request_id'),
            session('Track'),
        ])->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $payReferences = collect([
            $request->input('pay_reference'),
        ])->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $query = Deposit::query()->with([
            'gateway',
            'userDiscount',
            'bookedTicket.user',
            'bookedTicket.trip.schedule',
            'bookedTicket.trip.fleetType',
            'bookedTicket.pickup',
            'bookedTicket.drop',
        ]);

        if ($transactionIds->isNotEmpty() || $payReferences->isNotEmpty()) {
            $deposit = (clone $query)
                ->where(function ($lookup) use ($transactionIds, $payReferences) {
                    if ($transactionIds->isNotEmpty()) {
                        $lookup->whereIn('trx', $transactionIds->all());
                    }

                    if ($payReferences->isNotEmpty()) {
                        $method = $transactionIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                        $lookup->{$method}('pay_reference', $payReferences->all());
                    }
                })
                ->latest('id')
                ->first();

            if ($deposit?->bookedTicket) {
                return $deposit;
            }
        }

        $bookedTicketId = (int) session('booked_ticket_id');
        if (!$bookedTicketId) {
            return null;
        }

        return $query
            ->where('booked_ticket_id', $bookedTicketId)
            ->latest('id')
            ->first();
    }

    private function restoreBookingSession(BookedTicket $ticket, Deposit $deposit): void
    {
        session()->put([
            'Track' => $deposit->trx,
            'booked_ticket_id' => $ticket->id,
            'paynamics_request_id' => $deposit->trx,
        ]);

        if ($ticket->isKioskBooking()) {
            session()->put('kiosk_id', $ticket->kiosk_id);
        } else {
            session()->forget('kiosk_id');
        }
    }

    private function callbackDetails(Deposit $deposit, mixed $transaction, string $state): array
    {
        $channelCode = data_get($transaction, 'pchannel')
            ?: data_get($transaction, 'direct_otc_info.0.pay_channel')
            ?: $deposit->pchannel;
        $channelName = $channelCode ?: 'Paynamics';

        if ($channelCode) {
            try {
                $channelName = getPaynamicsPChannel($channelCode, true);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $defaultMessage = match ($state) {
            'success' => 'Payment successfully confirmed.',
            'pending' => 'Your payment is pending confirmation.',
            'cancelled' => 'The Paynamics payment was cancelled. No payment was confirmed.',
            default => 'Paynamics could not confirm this payment.',
        };
        $providerMessage = trim((string) data_get($transaction, 'response_message'));

        return [
            'state' => $state,
            'request_id' => $deposit->trx,
            'response_code' => data_get($transaction, 'response_code'),
            'message' => $state === 'cancelled' ? $defaultMessage : ($providerMessage ?: $defaultMessage),
            'advice' => data_get($transaction, 'response_advise'),
            'pay_reference' => data_get($transaction, 'pay_reference') ?: $deposit->pay_reference,
            'payment_channel' => $channelName,
            'payment_channel_code' => $channelCode,
            'timestamp' => data_get($transaction, 'timestamp') ?: now()->format('M d, Y h:i A'),
            'instructions' => data_get($transaction, 'direct_otc_info.0.pay_instructions'),
            'amount' => (float) $deposit->final_amount,
        ];
    }

    private function bookingLayout(BookedTicket $ticket): string
    {
        if ($ticket->isKioskBooking()) {
            return 'layouts.kiosk';
        }

        return auth()->check() ? 'layouts.master' : 'layouts.frontend';
    }

    private function callbackUnavailable(string $state)
    {
        $callbackState = $state === 'cancelled' ? 'cancelled' : 'failed';
        $callbackDetails = [
            'state' => $callbackState,
            'request_id' => request('request_id'),
            'response_code' => request('response_code'),
            'message' => 'We could not locate this payment transaction. Please return to the booking page or ask the cashier for assistance.',
            'advice' => null,
            'pay_reference' => request('pay_reference'),
            'payment_channel' => 'Paynamics',
            'payment_channel_code' => null,
            'timestamp' => now()->format('M d, Y h:i A'),
            'instructions' => null,
            'amount' => null,
        ];
        $ticket = null;
        $deposit = null;
        $transaction = null;
        $layout = 'layouts.frontend';
        $pageTitle = $callbackState === 'cancelled' ? 'Payment Cancelled' : 'Payment Response';

        return view('templates/basic/user/payment/response/paynamics', compact(
            'ticket',
            'deposit',
            'transaction',
            'callbackDetails',
            'callbackState',
            'pageTitle',
            'layout'
        ));
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
        $uid = null;
        $deposit = Deposit::orderBy('id', 'DESC');
        if (isset($payload['request_id'])) {
            $uid = $payload['request_id'];
            $deposit->where('trx', $uid);
        } else if (isset($payload['pay_reference'])) {
            $uid = $payload['pay_reference'];
            $deposit->where('pay_reference', $uid);
        }

        $deposit = $deposit->first();

        if ($payload['response_code'] == 'GR001' && $uid) {
            PaymentController::userDataUpdate($deposit);
        } else {
            $uid = now()->format('Y-m-d_H-i-s');
        }

        $fileName = 'paynamics/webhooks/' . $uid . '.json';

        Storage::put($fileName, json_encode($payload, JSON_PRETTY_PRINT));

        return response()->json([
            'status' => 'success',
            'payload' => $payload
        ]);
    }
}
