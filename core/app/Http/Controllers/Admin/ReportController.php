<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
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
