<?php

namespace App\Services;

use App\Models\CashierTransactionEvent;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DailyReportService
{
    private const ACTIVITY_STATUSES = [
        'sold' => 'Sold',
        'rebooked' => 'Rebooked',
        'cancelled' => 'Cancelled',
        'voided' => 'Voided',
        'refunded' => 'Refunded',
        'discount_override' => 'Discount Override',
        'validated' => 'Validated',
    ];

    public function __construct(
        private readonly CashierTransactionRecorder $transactionRecorder,
        private readonly CashierDashboardService $dashboardService
    ) {
    }

    public function forDate(CarbonInterface $date, array $filters = []): array
    {
        $businessDate = Carbon::parse($date->toDateString())->startOfDay();
        $this->transactionRecorder->backfillAllForDate($businessDate);

        $allTransactions = CashierTransactionEvent::query()
            ->bookingTransactions()
            ->with('admin:id,name,username')
            ->whereBetween('processed_at', [
                $businessDate->copy()->startOfDay(),
                $businessDate->copy()->endOfDay(),
            ])
            ->orderByRaw("CASE WHEN reference_no IS NULL OR reference_no = '' THEN 1 ELSE 0 END")
            ->orderBy('reference_no')
            ->orderBy('id')
            ->get();
        $filters = $this->normalizeFilters($filters);
        $filterOptions = $this->filterOptions($allTransactions);
        $transactions = $this->filterTransactions($allTransactions, $filters);

        return array_merge(
            [
                'transactions' => $transactions,
                'filters' => $filters,
                'filter_options' => $filterOptions,
                'active_filter_labels' => $this->activeFilterLabels($filters, $filterOptions),
            ],
            $this->compile($transactions)
        );
    }

    public function filterTransactions(Collection $transactions, array $filters): Collection
    {
        $filters = $this->normalizeFilters($filters);

        return $transactions
            ->filter(function ($transaction) use ($filters) {
                if ($filters['transaction_type'] !== ''
                    && strcasecmp((string) $transaction->status, $filters['transaction_type']) !== 0) {
                    return false;
                }

                if ($filters['source'] !== ''
                    && strcasecmp(trim((string) $transaction->source), $filters['source']) !== 0) {
                    return false;
                }

                if ($filters['processed_by'] !== ''
                    && $this->processedByKey($transaction) !== $filters['processed_by']) {
                    return false;
                }

                return $filters['payment_method'] === ''
                    || strcasecmp(trim((string) $transaction->payment_method), $filters['payment_method']) === 0;
            })
            ->values();
    }

    public function compile(Collection $transactions): array
    {
        $cashierCollections = $transactions
            ->filter(fn ($transaction) => $transaction->admin_id)
            ->groupBy(fn ($transaction) => (string) ($transaction->admin_id ?? 'unknown'))
            ->map(function (Collection $cashierTransactions) {
                $first = $cashierTransactions->first();

                return [
                    'cashier' => $first->admin?->name
                        ?: $first->admin?->username
                        ?: 'Unassigned',
                    'summary' => $this->dashboardService->summarize($cashierTransactions),
                ];
            })
            ->sortBy(fn (array $row) => strtolower($row['cashier']))
            ->values();

        $channelCollections = $transactions
            ->where('status', 'Sold')
            ->groupBy(fn ($transaction) => trim((string) ($transaction->source ?: 'Unspecified')))
            ->map(function (Collection $channelTransactions, string $channel) {
                return [
                    'channel' => $channel,
                    'tickets' => $channelTransactions->count(),
                    'amount' => (float) $channelTransactions->sum('amount'),
                ];
            })
            ->sortBy(fn (array $row) => strtolower($row['channel']))
            ->values();

        $activity = collect(self::ACTIVITY_STATUSES)
            ->mapWithKeys(fn (string $status, string $key) => [
                $key => $transactions->where('status', $status)->count(),
            ])
            ->all();

        return [
            'summary' => $this->dashboardService->summarize($transactions),
            'cashier_collections' => $cashierCollections,
            'channel_collections' => $channelCollections,
            'activity' => $activity,
        ];
    }

    private function normalizeFilters(array $filters): array
    {
        return collect([
            'transaction_type' => '',
            'source' => '',
            'processed_by' => '',
            'payment_method' => '',
        ])->mapWithKeys(fn ($default, $key) => [
            $key => trim((string) ($filters[$key] ?? $default)),
        ])->all();
    }

    private function filterOptions(Collection $transactions): array
    {
        $processedBy = $transactions
            ->map(fn ($transaction) => [
                'value' => $this->processedByKey($transaction),
                'label' => $this->processedByLabel($transaction),
            ])
            ->filter(fn (array $option) => $option['value'] !== '' && $option['label'] !== '')
            ->unique('value')
            ->sortBy(fn (array $option) => strtolower($option['label']))
            ->values();

        return [
            'transaction_types' => collect(self::ACTIVITY_STATUSES)->values(),
            'sources' => $this->distinctValues($transactions, 'source'),
            'processed_by' => $processedBy,
            'payment_methods' => $this->distinctValues($transactions, 'payment_method'),
        ];
    }

    private function distinctValues(Collection $transactions, string $field): Collection
    {
        return $transactions
            ->pluck($field)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn (string $value) => strtolower($value))
            ->sortBy(fn (string $value) => strtolower($value))
            ->values();
    }

    private function processedByKey($transaction): string
    {
        if ($transaction->admin_id) {
            return 'admin:' . $transaction->admin_id;
        }

        $label = $this->processedByLabel($transaction);

        return $label === '' ? '' : 'channel:' . $label;
    }

    private function processedByLabel($transaction): string
    {
        if (isset($transaction->processed_by_label)) {
            return trim((string) $transaction->processed_by_label);
        }

        return trim((string) (
            $transaction->admin?->name
            ?: $transaction->admin?->username
            ?: data_get($transaction, 'snapshot.processed_by')
            ?: $transaction->source
            ?: 'Counter'
        ));
    }

    private function activeFilterLabels(array $filters, array $options): array
    {
        $processedBy = $options['processed_by']->firstWhere('value', $filters['processed_by']);

        return array_filter([
            'Transaction Type' => $filters['transaction_type'],
            'Source' => $filters['source'],
            'Processed By' => $processedBy['label'] ?? '',
            'Payment Method' => $filters['payment_method'],
        ], fn ($value) => $value !== '');
    }

}
