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
use App\Services\CashierTransactionRecorder;

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
        $request->validate([
            'date' => 'nullable|date|before_or_equal:today',
        ]);

        $pageTitle = 'Shift End Report';
        $admin = auth('admin')->user();
        $date = Carbon::parse($request->date ?: now())->startOfDay();

        app(CashierTransactionRecorder::class)->backfillForDate($admin, $date);

        $transactions = CashierTransactionEvent::where('admin_id', $admin->id)
            ->whereBetween('processed_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->orderBy('processed_at')
            ->orderBy('id')
            ->get();

        $sold = $transactions->where('status', 'Sold');
        $summary = [
            'tickets' => $sold->count(),
            'gross_sales' => (float) $sold->sum('amount'),
            'discounts' => (float) $sold->sum('discount_amount'),
            'refunds' => abs((float) $transactions->where('status', 'Refunded')->sum('amount')),
            'voids' => abs((float) $transactions->where('status', 'Voided')->sum('amount')),
            'net_collection' => (float) $transactions->sum('amount'),
        ];

        return view('admin.reports.shift-end', compact(
            'pageTitle',
            'admin',
            'date',
            'transactions',
            'summary'
        ));
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

            $pdf->setPaper('A4', 'portrait');

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
