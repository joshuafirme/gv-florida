<?php

namespace App\Http\Controllers\Gateway\Paynamics;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Models\Deposit;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\PaynamicsWebhookLog;
use App\Services\Paynamics;
use App\Services\PaynamicsPaymentBroadcaster;
use App\Services\PendingPaymentExpirationService;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Storage;


class ProcessController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $paymentGateways,
        private readonly PendingPaymentExpirationService $pendingPaymentExpiration
    ) {
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
                $ticket->deposit->expiry_limit = $isKioskBooking
                    ? $this->pendingPaymentExpiration->expiresAt($ticket->deposit->created_at)
                    : ($transaction->expiry_limit ?? $ticket->deposit->expiry_limit);
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

    public function status(Request $request)
    {
        $transactionId = trim((string) session('Track'));
        $bookedTicketId = (int) session('booked_ticket_id');

        abort_unless($transactionId !== '' && $bookedTicketId > 0, 404);

        $deposit = Deposit::query()
            ->with(['gateway', 'bookedTicket'])
            ->where('trx', $transactionId)
            ->where('booked_ticket_id', $bookedTicketId)
            ->latest('id')
            ->firstOrFail();

        abort_unless($this->isPaynamicsDeposit($deposit), 404);

        $transaction = null;

        if (in_array((int) $deposit->status, [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING], true)) {
            try {
                $transaction = $this->getTransaction($deposit->trx);
                $this->syncProviderDetails($deposit, $transaction);

                if (data_get($transaction, 'response_code') === 'GR001') {
                    PaymentController::userDataUpdate($deposit);
                }
            } catch (\Throwable $exception) {
                report($exception);

                return response()->json([
                    'message' => 'Payment status could not be refreshed yet. Live updates will remain active.',
                ], 503);
            }
        }

        $deposit->refresh()->loadMissing(['gateway', 'bookedTicket']);
        $payload = $this->paymentStatusPayload($deposit, $transaction);

        session()->put('paynamics_callback_details', $payload['details']);
        app(PaynamicsPaymentBroadcaster::class)->paymentUpdated($deposit->trx, $payload);

        return response()->json(['data' => $payload]);
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

    private function isPaynamicsDeposit(Deposit $deposit): bool
    {
        return filled($deposit->pchannel)
            || strtolower((string) $deposit->gateway?->alias) === PaymentGatewayService::PAYNAMICS;
    }

    private function syncProviderDetails(Deposit $deposit, mixed $transaction): void
    {
        if (!$transaction) {
            return;
        }

        $payReference = trim((string) data_get($transaction, 'pay_reference'));
        $channel = trim((string) (
            data_get($transaction, 'pchannel')
            ?: data_get($transaction, 'direct_otc_info.0.pay_channel')
        ));
        $expiryLimit = data_get($transaction, 'expiry_limit');

        if ($payReference !== '') {
            $deposit->pay_reference = $payReference;
        }

        if ($channel !== '') {
            $deposit->pchannel = $channel;
        }

        $isKioskBooking = $deposit->getAttribute('booked_ticket_id')
            && $deposit->bookedTicket?->isKioskBooking();

        if ($isKioskBooking) {
            $deposit->expiry_limit = $this->pendingPaymentExpiration->expiresAt($deposit->created_at);
        } elseif ($expiryLimit) {
            $deposit->expiry_limit = $expiryLimit;
        }

        if ($deposit->isDirty()) {
            $deposit->save();
        }
    }

    private function paymentStatusPayload(Deposit $deposit, mixed $transaction): array
    {
        $state = match ((int) $deposit->status) {
            Status::PAYMENT_SUCCESS => 'success',
            Status::PAYMENT_REJECT => 'failed',
            Status::PAYMENT_EXPIRED => 'expired',
            default => 'pending',
        };
        $details = $this->callbackDetails($deposit, $transaction, $state);

        return [
            'state' => $state,
            'is_paid' => $state === 'success',
            'transaction_id' => $deposit->trx,
            'payment_method' => $details['payment_channel'] ?: 'Paynamics',
            'amount' => (float) $deposit->final_amount,
            'amount_display' => showAmount($deposit->final_amount),
            'updated_at' => $deposit->updated_at?->toIso8601String(),
            'details' => $details,
        ];
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

            if ($deposit) {
                $this->syncProviderDetails($deposit, (object) $payload);
                $deposit->refresh();
                app(PaynamicsPaymentBroadcaster::class)->paymentUpdated(
                    $deposit->trx,
                    $this->paymentStatusPayload($deposit, (object) $payload)
                );
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
