<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\CashierTransactionEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class CashierDashboardService
{
    private const STATUS_LABELS = [
        'sold' => 'Sold',
        'rebooked' => 'Rebooked',
        'refunded' => 'Refunded',
        'voided' => 'Voided',
        'cancelled' => 'Cancelled',
    ];

    public function __construct(
        private readonly CashierTransactionRecorder $transactionRecorder
    ) {
    }

    public function forDate(Admin $admin, CarbonInterface $date): array
    {
        $this->transactionRecorder->backfillForDate($admin, $date->copy());

        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $transactions = CashierTransactionEvent::query()
            ->where('admin_id', $admin->id)
            ->whereBetween('processed_at', [$start, $end])
            ->orderBy('processed_at')
            ->orderBy('id')
            ->get();

        $newerStatusPriority = $this->statusPrioritySql('newer_events');
        $currentStatusPriority = $this->statusPrioritySql('cashier_transaction_events');

        $latestTransactions = CashierTransactionEvent::query()
            ->where('cashier_transaction_events.admin_id', $admin->id)
            ->whereBetween('cashier_transaction_events.processed_at', [$start, $end])
            ->whereNotExists(function ($newerEvent) use ($newerStatusPriority, $currentStatusPriority) {
                $newerEvent
                    ->selectRaw('1')
                    ->from('cashier_transaction_events as newer_events')
                    ->whereColumn(
                        'newer_events.slip_series_number_id',
                        'cashier_transaction_events.slip_series_number_id'
                    )
                    ->where(function ($newer) use ($newerStatusPriority, $currentStatusPriority) {
                        $newer
                            ->whereColumn(
                                'newer_events.processed_at',
                                '>',
                                'cashier_transaction_events.processed_at'
                            )
                            ->orWhere(function ($sameTime) use ($newerStatusPriority, $currentStatusPriority) {
                                $sameTime
                                    ->whereColumn(
                                        'newer_events.processed_at',
                                        'cashier_transaction_events.processed_at'
                                    )
                                    ->where(function ($sameTimeOrder) use (
                                        $newerStatusPriority,
                                        $currentStatusPriority
                                    ) {
                                        $sameTimeOrder
                                            ->whereRaw("{$newerStatusPriority} > {$currentStatusPriority}")
                                            ->orWhere(function ($samePriority) use (
                                                $newerStatusPriority,
                                                $currentStatusPriority
                                            ) {
                                                $samePriority
                                                    ->whereRaw("{$newerStatusPriority} = {$currentStatusPriority}")
                                                    ->whereColumn(
                                                        'newer_events.id',
                                                        '>',
                                                        'cashier_transaction_events.id'
                                                    );
                                            });
                                    });
                            });
                    });
            })
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->get();

        return [
            'summary' => $this->summarize($transactions),
            'status_metrics' => $this->statusMetrics($latestTransactions),
            'latest_transactions' => $latestTransactions->take(5),
        ];
    }

    public function summarize(Collection $transactions): array
    {
        $sold = $transactions->where('status', 'Sold');
        $surcharges = (float) $sold->sum('surcharge_amount');

        return [
            'tickets' => $sold->count(),
            'gross_sales' => (float) $sold->sum('amount') - $surcharges,
            'discounts' => (float) $sold->sum('discount_amount'),
            'surcharges' => $surcharges,
            'refunds' => abs((float) $transactions->where('status', 'Refunded')->sum('amount')),
            'voids' => abs((float) $transactions->where('status', 'Voided')->sum('amount')),
            'rebooked' => $transactions->where('status', 'Rebooked')->count(),
            'cancelled' => $transactions->where('status', 'Cancelled')->count(),
            'net_collection' => (float) $transactions->sum('amount'),
            'transaction_count' => $transactions->count(),
        ];
    }

    private function statusMetrics(Collection $latestTransactions): array
    {
        return collect(self::STATUS_LABELS)
            ->mapWithKeys(function (string $status, string $key) use ($latestTransactions) {
                $transactions = $latestTransactions->where('status', $status);

                return [
                    $key => [
                        'status' => $status,
                        'count' => $transactions->count(),
                        'amount' => abs((float) $transactions->sum('amount')),
                    ],
                ];
            })
            ->all();
    }

    private function statusPrioritySql(string $table): string
    {
        return "CASE {$table}.status
            WHEN 'Refunded' THEN 5
            WHEN 'Voided' THEN 4
            WHEN 'Cancelled' THEN 3
            WHEN 'Rebooked' THEN 2
            WHEN 'Sold' THEN 1
            ELSE 0
        END";
    }
}
