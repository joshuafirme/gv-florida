<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\PaynamicsPaymentChannel;
use App\Models\PaynamicsWebhookLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeveloperController extends Controller
{
    private const PAYMENT_STATUSES = [
        'initiated' => Status::PAYMENT_INITIATE,
        'success' => Status::PAYMENT_SUCCESS,
        'pending' => Status::PAYMENT_PENDING,
        'rejected' => Status::PAYMENT_REJECT,
        'expired' => Status::PAYMENT_EXPIRED,
    ];

    public function paymentTransactions(Request $request)
    {
        $this->validatePaymentFilters($request);

        $pageTitle = 'Payment Transactions';
        $gatewayCode = Gateway::query()->where('alias', 'paynamics')->value('code');
        $query = Deposit::query()
            ->with(['gateway', 'user', 'bookedTicket'])
            ->when($gatewayCode, fn (Builder $query) => $query->where('method_code', $gatewayCode))
            ->when(! $gatewayCode, fn (Builder $query) => $query->whereRaw('1 = 0'));

        $totalTransactions = (clone $query)->count();
        $this->applyPaymentFilters($query, $request);

        $transactions = $query->latest('id')->paginate(getPaginate())->withQueryString();
        $transactions->getCollection()->each(function (Deposit $transaction) {
            $transaction->setAttribute(
                'request_payload',
                $this->readPaynamicsJson("paynamics/payloads/{$transaction->trx}.json")
            );
            $transaction->setAttribute(
                'response_payload',
                $this->readPaynamicsJson("paynamics/responses/{$transaction->trx}.json")
                    ?? $this->readPaynamicsJson("paynamics/{$transaction->trx}.json")
            );
        });
        $paymentStatuses = self::PAYMENT_STATUSES;
        $channelNames = PaynamicsPaymentChannel::query()->pluck('name', 'code');

        return view('admin.developer.payment-transactions', compact(
            'pageTitle',
            'transactions',
            'totalTransactions',
            'paymentStatuses',
            'channelNames'
        ));
    }

    public function exportPaymentTransactions(Request $request): StreamedResponse
    {
        $this->validatePaymentFilters($request);

        $gatewayCode = Gateway::query()->where('alias', 'paynamics')->value('code');
        $query = Deposit::query()
            ->with(['bookedTicket'])
            ->when($gatewayCode, fn (Builder $query) => $query->where('method_code', $gatewayCode))
            ->when(! $gatewayCode, fn (Builder $query) => $query->whereRaw('1 = 0'));

        $this->applyPaymentFilters($query, $request);

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Transaction ID',
                'Pay Reference',
                'PNR',
                'Payment Method',
                'Payment Channel',
                'Status',
                'Amount',
                'Currency',
                'Created At',
                'Last Updated',
                'Expires At',
            ]);

            $statusLabels = array_flip(self::PAYMENT_STATUSES);
            $query->chunkById(500, function ($transactions) use ($output, $statusLabels) {
                foreach ($transactions as $transaction) {
                    fputcsv($output, [
                        $transaction->trx,
                        $transaction->pay_reference,
                        $transaction->bookedTicket?->pnr_number,
                        $transaction->pmethod,
                        $transaction->pchannel,
                        ucfirst($statusLabels[(int) $transaction->status] ?? 'initiated'),
                        number_format((float) $transaction->final_amount, 2, '.', ''),
                        $transaction->method_currency,
                        $transaction->created_at?->toDateTimeString(),
                        $transaction->updated_at?->toDateTimeString(),
                        $transaction->expiry_limit,
                    ]);
                }
            }, 'id', 'id');

            fclose($output);
        }, 'paynamics-transactions-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function webhookLogs(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:20'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $pageTitle = 'Webhook Logs';
        $query = PaynamicsWebhookLog::query()->with('deposit.bookedTicket');
        $totalWebhooks = (clone $query)->count();

        if ($request->filled('search')) {
            $search = '%'.addcslashes(trim((string) $request->search), '\\%_').'%';
            $query->where(function (Builder $query) use ($search) {
                $query->where('request_id', 'like', $search)
                    ->orWhere('original_transaction_id', 'like', $search)
                    ->orWhere('pay_reference', 'like', $search)
                    ->orWhere('event_type', 'like', $search)
                    ->orWhere('error_message', 'like', $search)
                    ->orWhere('payload', 'like', $search);
            });
        }

        $query->when($request->filled('provider'), fn (Builder $query) => $query->where('provider', $request->provider));
        $query->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->status));
        $this->applyDateFilters($query, $request, 'received_at');

        $webhooks = $query->latest('received_at')->latest('id')->paginate(getPaginate())->withQueryString();
        $providers = PaynamicsWebhookLog::query()->distinct()->orderBy('provider')->pluck('provider');
        $webhookStatuses = PaynamicsWebhookLog::query()->distinct()->orderBy('status')->pluck('status');

        return view('admin.developer.webhook-logs', compact(
            'pageTitle',
            'webhooks',
            'totalWebhooks',
            'providers',
            'webhookStatuses'
        ));
    }

    private function applyPaymentFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = '%'.addcslashes(trim((string) $request->search), '\\%_').'%';
            $query->where(function (Builder $query) use ($search) {
                $query->where('trx', 'like', $search)
                    ->orWhere('pay_reference', 'like', $search)
                    ->orWhere('pmethod', 'like', $search)
                    ->orWhere('pchannel', 'like', $search)
                    ->orWhereHas('bookedTicket', fn (Builder $ticketQuery) => $ticketQuery->where('pnr_number', 'like', $search))
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('firstname', 'like', $search)
                            ->orWhere('lastname', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    });
            });
        }

        $status = strtolower((string) $request->status);
        if (array_key_exists($status, self::PAYMENT_STATUSES)) {
            $query->where('status', self::PAYMENT_STATUSES[$status]);
        }

        $this->applyDateFilters($query, $request, 'created_at');
    }

    private function validatePaymentFilters(Request $request): void
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_keys(self::PAYMENT_STATUSES))],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);
    }

    private function applyDateFilters(Builder $query, Request $request, string $column): void
    {
        if ($request->filled('date_from')) {
            $query->where($column, '>=', Carbon::parse($request->date_from)->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where($column, '<=', Carbon::parse($request->date_to)->endOfDay());
        }
    }

    private function readPaynamicsJson(string $path): array|string|null
    {
        if (! Storage::exists($path)) {
            return null;
        }

        $contents = Storage::get($path);

        try {
            return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $contents;
        }
    }
}
