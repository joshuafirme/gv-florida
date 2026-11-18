<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\BookedTicket;
use App\Models\CashierTransactionEvent;
use App\Models\Counter;
use App\Models\NotificationLog;
use App\Models\Transaction;
use App\Models\UserLogin;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\CashierDashboardService;
use App\Services\DailyReportService;
use App\Support\BusinessTime;

class ReportController extends Controller
{
    public function auditTrail(Request $request)
    {
        $dateToRules = ['nullable', 'date', 'before_or_equal:today'];
        if ($request->filled('date_from')) {
            $dateToRules[] = 'after_or_equal:date_from';
        }

        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'event' => 'nullable|string|max:30',
            'admin_id' => 'nullable|integer|exists:admins,id',
            'source' => 'nullable|string|max:30',
            'date_from' => 'nullable|date|before_or_equal:today',
            'date_to' => $dateToRules,
        ]);

        $pageTitle = 'Audit Trail';
        $search = trim((string) ($validated['search'] ?? ''));

        $query = CashierTransactionEvent::query()
            ->with('admin:id,name,username')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('pnr', 'like', "%{$search}%")
                        ->orWhere('reference_no', 'like', "%{$search}%")
                        ->orWhere('passenger_name', 'like', "%{$search}%")
                        ->orWhere('passenger_type', 'like', "%{$search}%")
                        ->orWhere('passenger_id', 'like', "%{$search}%")
                        ->orWhere('seat_no', 'like', "%{$search}%")
                        ->orWhere('trip_class', 'like', "%{$search}%")
                        ->orWhere('trip_route', 'like', "%{$search}%")
                        ->orWhere('payment_method', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('admin', function ($adminQuery) use ($search) {
                            $adminQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        });
                });
            })
            ->when($validated['event'] ?? null, fn ($query, $event) => $query->where('status', $event))
            ->when($validated['admin_id'] ?? null, fn ($query, $adminId) => $query->where('admin_id', $adminId))
            ->when($validated['source'] ?? null, fn ($query, $source) => $query->where('source', $source))
            ->when($validated['date_from'] ?? null, function ($query, $date) {
                $query->where('processed_at', '>=', Carbon::parse($date)->startOfDay());
            })
            ->when($validated['date_to'] ?? null, function ($query, $date) {
                $query->where('processed_at', '<=', Carbon::parse($date)->endOfDay());
            });

        $transactions = $query
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->paginate(getPaginate())
            ->withQueryString();

        $admins = Admin::query()
            ->whereIn('id', CashierTransactionEvent::query()->select('admin_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name', 'username']);
        $events = CashierTransactionEvent::query()->distinct()->orderBy('status')->pluck('status');
        $sources = CashierTransactionEvent::query()->whereNotNull('source')->distinct()->orderBy('source')->pluck('source');

        return view('admin.reports.audit-trail', compact(
            'pageTitle',
            'transactions',
            'admins',
            'events',
            'sources',
            'search'
        ));
    }

    public function shiftEnd(Request $request)
    {
        $pageTitle = 'Shift End Report';
        $admin = auth('admin')->user();
        [$dateFrom, $dateTo, $period] = $this->reportPeriodParameters($request);
        $date = $dateFrom;
        $report = $this->shiftEndData($admin, $dateFrom, $dateTo);

        return view('admin.reports.shift-end', compact(
            'pageTitle',
            'admin',
            'date',
            'dateFrom',
            'dateTo',
            'period'
        ) + $report);
    }

    public function shiftEndPdf(Request $request)
    {
        $admin = auth('admin')->user();
        [$dateFrom, $dateTo, $period] = $this->reportPeriodParameters($request);
        $date = $dateFrom;
        $report = $this->shiftEndData($admin, $dateFrom, $dateTo);
        $filename = $this->reportFilename('shift-end-report', $dateFrom, $dateTo);

        $pdf = Pdf::setOptions($this->reportPdfOptions())
            ->loadView(
                'admin.pdf.shift-end-report',
                compact('admin', 'date', 'dateFrom', 'dateTo', 'period') + $report
            )
            ->setPaper('letter', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function daily(Request $request, DailyReportService $dailyReportService)
    {
        $pageTitle = 'Daily Report';
        [$dateFrom, $dateTo, $period, $filters] = $this->dailyReportParameters($request);
        $date = $dateFrom;
        $report = $dailyReportService->forPeriod($dateFrom, $dateTo, $filters);

        return view('admin.reports.daily', array_merge(
            compact('pageTitle', 'date', 'dateFrom', 'dateTo', 'period', 'filters'),
            $report
        ));
    }

    public function dailyPdf(Request $request, DailyReportService $dailyReportService)
    {
        [$dateFrom, $dateTo, $period, $filters] = $this->dailyReportParameters($request);
        $date = $dateFrom;
        $report = $dailyReportService->forPeriod($dateFrom, $dateTo, $filters);
        $filename = $this->reportFilename('daily-report', $dateFrom, $dateTo);

        return Pdf::setOptions($this->reportPdfOptions())
            ->loadView(
                'admin.pdf.daily-report',
                compact('date', 'dateFrom', 'dateTo', 'period', 'filters') + $report
            )
            ->setPaper('legal', 'landscape')
            ->stream($filename);
    }

    private function dailyReportParameters(Request $request): array
    {
        $validated = $request->validate(array_merge($this->reportPeriodRules($request), [
            'transaction_type' => [
                'nullable',
                'string',
                Rule::in(CashierTransactionEvent::BOOKING_TRANSACTION_STATUSES),
            ],
            'source' => 'nullable|string|max:30',
            'processed_by' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
        ]));

        [$dateFrom, $dateTo, $period] = $this->resolveReportPeriod($validated);
        $filters = collect($validated)->only([
            'transaction_type',
            'source',
            'processed_by',
            'payment_method',
        ])->all();

        return [$dateFrom, $dateTo, $period, $filters];
    }

    private function reportPeriodParameters(Request $request): array
    {
        $validated = $request->validate($this->reportPeriodRules($request));

        return $this->resolveReportPeriod($validated);
    }

    private function reportPeriodRules(Request $request): array
    {
        $dateToRules = ['nullable', 'date', 'before_or_equal:today'];
        if ($request->filled('date_from')) {
            $dateToRules[] = 'after_or_equal:date_from';
        }

        return [
            'period' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'custom'])],
            'date' => 'nullable|date|before_or_equal:today',
            'date_from' => 'nullable|date|before_or_equal:today',
            'date_to' => $dateToRules,
        ];
    }

    private function resolveReportPeriod(array $validated): array
    {
        $hasExplicitRange = !empty($validated['date_from']) || !empty($validated['date_to']);
        $period = $validated['period'] ?? ($hasExplicitRange ? 'custom' : 'daily');
        $anchor = BusinessTime::parse(
            $validated['date_to']
                ?? $validated['date_from']
                ?? $validated['date']
                ?? BusinessTime::now()
        )->startOfDay();

        if ($hasExplicitRange) {
            $dateFrom = BusinessTime::parse($validated['date_from'] ?? $validated['date_to'])->startOfDay();
            $dateTo = BusinessTime::parse($validated['date_to'] ?? $validated['date_from'])->startOfDay();

            return [$dateFrom, $dateTo, $period];
        }

        $today = BusinessTime::now()->startOfDay();
        [$dateFrom, $dateTo] = match ($period) {
            'weekly' => [
                $anchor->copy()->startOfWeek(Carbon::MONDAY),
                $anchor->copy()->endOfWeek(Carbon::SUNDAY)->min($today)->startOfDay(),
            ],
            'monthly' => [
                $anchor->copy()->startOfMonth(),
                $anchor->copy()->endOfMonth()->min($today)->startOfDay(),
            ],
            default => [$anchor->copy(), $anchor->copy()],
        };

        return [$dateFrom, $dateTo, $period];
    }

    private function shiftEndData(Admin $admin, Carbon $dateFrom, Carbon $dateTo): array
    {
        $report = app(CashierDashboardService::class)->forPeriod($admin, $dateFrom, $dateTo);

        return [
            'transactions' => $report['transactions'],
            'summary' => $report['summary'],
        ];
    }

    private function reportFilename(string $prefix, Carbon $dateFrom, Carbon $dateTo): string
    {
        $period = $dateFrom->isSameDay($dateTo)
            ? $dateFrom->format('Y-m-d')
            : $dateFrom->format('Y-m-d') . '-to-' . $dateTo->format('Y-m-d');

        return $prefix . '-' . $period . '.pdf';
    }

    private function reportPdfOptions(): array
    {
        return [
            'defaultFont' => 'DejaVu Sans',
            'dpi' => 96,
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ];
    }

    public function transaction(Request $request, $userId = null)
    {
        $pageTitle = 'Transaction Logs';

        $remarks = Transaction::distinct('remark')->orderBy('remark')->get('remark');

        $transactions = Transaction::searchable(['trx', 'user:username'])->filter(['trx_type', 'remark'])->dateFilter()->orderBy('id', 'desc')->with('user');
        if ($userId) {
            $transactions = $transactions->where('user_id', $userId);
        }
        $transactions = $transactions->paginate(getPaginate());

        return view('admin.reports.transactions', compact('pageTitle', 'transactions', 'remarks'));
    }

    public function travelManifest(Request $request)
    {
        $pageTitle = 'Travel Manifest';
        $data = BookedTicket::orderBy('created_at', 'desc');

        if (request('status') != 'all') {
            $data->where('status', request('status'));
        }

        if ($request->pickup) {
            $data->where('pickup_point', $request->pickup);
        }
        if ($request->destination) {
            $data->where('dropping_point', $request->destination);
        }
        if ($request->date) {
            $dates = explode(' - ', $request->date);
            $date_from = date('Y-m-d', strtotime($dates[0]));
            $date_to = date('Y-m-d', strtotime($dates[1]));
            $data->whereBetween('date_of_journey', [$date_from, $date_to]);
        }

        if ($request->print || $request->download) {
            $pdf = Pdf::setOptions([
                'isRemoteEnabled' => true
            ])->loadView('admin.pdf.travel-manifest', ['data' => $data->get()]);

            $pdf->setPaper('legal', 'portrait');

            if ($request->print) {
                return $pdf->stream("$pageTitle.pdf");
            }
            return $pdf->download("$pageTitle.pdf");
        }

        $data = $data->paginate(getPaginate());
        $counters = Counter::where('status', 1)->get();
        return view('admin.reports.travel-manifest', compact('pageTitle', 'data', 'counters'));
    }

    public function loginHistory(Request $request)
    {
        $pageTitle = 'User Login History';
        $loginLogs = UserLogin::orderBy('id', 'desc')->searchable(['user:username'])->dateFilter()->with('user')->paginate(getPaginate());
        return view('admin.reports.logins', compact('pageTitle', 'loginLogs'));
    }

    public function loginIpHistory($ip)
    {
        $pageTitle = 'Login by - ' . $ip;
        $loginLogs = UserLogin::where('user_ip', $ip)->orderBy('id', 'desc')->with('user')->paginate(getPaginate());
        return view('admin.reports.logins', compact('pageTitle', 'loginLogs', 'ip'));
    }

    public function notificationHistory(Request $request)
    {
        $pageTitle = 'Notification History';
        $logs = NotificationLog::orderBy('id', 'desc')->searchable(['user:username'])->dateFilter()->with('user')->paginate(getPaginate());
        return view('admin.reports.notification_history', compact('pageTitle', 'logs'));
    }

    public function emailDetails($id)
    {
        $pageTitle = 'Email Details';
        $email = NotificationLog::findOrFail($id);
        return view('admin.reports.email_details', compact('pageTitle', 'email'));
    }
}
