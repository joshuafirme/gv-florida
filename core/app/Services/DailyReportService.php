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
    ];

    public function __construct(
        private readonly CashierTransactionRecorder $transactionRecorder,
        private readonly CashierDashboardService $dashboardService
    ) {
    }

    public function forDate(CarbonInterface $date): array
    {
        $businessDate = Carbon::parse($date->toDateString())->startOfDay();
        $this->transactionRecorder->backfillAllForDate($businessDate);

        $transactions = CashierTransactionEvent::query()
            ->bookingTransactions()
            ->with('admin:id,name,username')
            ->whereBetween('processed_at', [
                $businessDate->copy()->startOfDay(),
                $businessDate->copy()->endOfDay(),
            ])
            ->orderBy('processed_at')
            ->orderBy('id')
            ->get();

        return array_merge(
            ['transactions' => $transactions],
            $this->compile($transactions)
        );
    }

    public function compile(Collection $transactions): array
    {
        $cashierCollections = $transactions
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
}
