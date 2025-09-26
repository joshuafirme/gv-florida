<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use App\Models\Counter;
use App\Models\NotificationLog;
use App\Models\Transaction;
use App\Models\UserLogin;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
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
        $data = BookedTicket::orderBy('created_at', 'desc')
            ->where('status', Status::BOOKED_APPROVED);

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
