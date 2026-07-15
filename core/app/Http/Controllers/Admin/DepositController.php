<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use App\Exports\PaymentExport;
use Maatwebsite\Excel\Facades\Excel;

class DepositController extends Controller
{
    public function pending($userId = null)
    {
        $pageTitle = 'Pending Deposits';
        $status = 'pending';
        $deposits = $this->depositData($status, userId: $userId);
        return view('admin.deposit.log', compact('pageTitle', 'deposits', 'status'));
    }

    public function scanPending(Request $request)
    {
        $request->validate([
            'scan' => 'required|string|max:2048',
        ]);

        $scannedValue = trim($request->scan);
        $lookup = $scannedValue;

        // Ticket QR codes contain a search URL. Hardware scanners may also be
        // configured to send the PNR, reference number, or ticket number only.
        if (filter_var($scannedValue, FILTER_VALIDATE_URL)) {
            parse_str((string) parse_url($scannedValue, PHP_URL_QUERY), $query);
            $lookup = trim((string) ($query['search'] ?? $query['pnr'] ?? $scannedValue));
        }

        $deposit = Deposit::pending()
            ->with([
                'user',
                'userDiscount',
                'bookedTicket.pickup',
                'bookedTicket.drop',
                'bookedTicket.trip.schedule',
                'bookedTicket.trip.fleetType',
                'bookedTicket.slipSeriesNumbers',
                'bookedTicket.activeSlipSeriesNumbers',
            ])
            ->where(function ($query) use ($lookup) {
                $query->where('trx', $lookup)
                    ->orWhereHas('bookedTicket', function ($ticketQuery) use ($lookup) {
                        $ticketQuery->where('pnr_number', $lookup)
                            ->orWhere('series_number', $lookup)
                            ->orWhereHas('slipSeriesNumbers', fn ($slipQuery) => $slipQuery->where('id', $lookup));
                    });
            })
            ->latest('id')
            ->first();

        if (!$deposit) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No pending payment was found for the scanned QR code.',
                ], 404);
            }

            $notify[] = ['error', 'No pending payment was found for the scanned QR code.'];
            return to_route('admin.deposit.pending')->withInput()->withNotify($notify);
        }

        if ($request->expectsJson()) {
            $ticket = $deposit->bookedTicket;
            $manifest = collect($ticket->passenger_manifest ?: ($deposit->userDiscount?->passenger_manifest ?: []))
                ->keyBy(fn ($passenger) => (string) ($passenger['seat'] ?? ''));
            $seats = collect($ticket->seats ?: $manifest->keys())
                ->map(fn ($seat) => trim((string) $seat))
                ->filter()
                ->unique()
                ->values();
            $fallbackTicketFare = (float) $deposit->final_amount / max($seats->count(), 1);
            $fallbackPassengerName = $deposit->userDiscount?->passenger_name
                ?: $deposit->user?->fullname
                ?: 'Guest';
            $fallbackPassengerType = getPassengerType($deposit);

            return response()->json([
                'deposit_id' => $deposit->id,
                'ticket_id' => $ticket->id,
                'pnr' => $ticket->pnr_number,
                'trip' => $ticket->pickup->name . ' via ' . $ticket->drop->name,
                'route' => $ticket->pickup->name . ' via ' . $ticket->drop->name,
                'bus_type' => $ticket->trip->fleetType->name,
                'departure_time' => Carbon::parse($ticket->trip->schedule->start_from)->format('g:i A'),
                'travel_date' => Carbon::parse($ticket->date_of_journey)->format('D, M d, Y'),
                'amount' => (float) $deposit->final_amount,
                'passenger_name' => $fallbackPassengerName,
                'passenger_type' => $fallbackPassengerType,
                'processed_by' => auth('admin')->user()->name,
                'tickets' => $seats->map(function ($seat) use ($manifest, $fallbackPassengerName, $fallbackPassengerType, $fallbackTicketFare) {
                    $passenger = $manifest->get((string) $seat, []);
                    $isDiscounted = ($passenger['passenger_type'] ?? 'regular') === 'discounted';

                    return [
                        'number' => null,
                        'seat' => $seat,
                        'fare' => (float) ($passenger['fare'] ?? $fallbackTicketFare),
                        'base_fare' => (float) ($passenger['base_fare'] ?? $fallbackTicketFare),
                        'discount_amount' => (float) ($passenger['discount_amount'] ?? 0),
                        'passenger_name' => ($passenger['name'] ?? null) ?: $fallbackPassengerName,
                        'passenger_type' => $passenger
                            ? ($isDiscounted ? ($passenger['discount_name'] ?? 'Discounted') : 'Regular')
                            : $fallbackPassengerType,
                    ];
                })->values(),
                'reject_url' => route('admin.deposit.reject'),
                'validate_url' => url("api/ticket/validate-deposit/{$deposit->id}"),
                'print_url' => url("api/ticket/download/reservation-slip/{$ticket->id}"),
                'reservation_slip_url' => route('admin.trip.reservationSlip', $ticket->id),
            ]);
        }

        $notify[] = ['info', 'Pending payments must be processed through the new POS modal on the Pending Payments page.'];
        return to_route('admin.deposit.pending')->withNotify($notify);
    }


    public function approved($userId = null)
    {
        $pageTitle = 'Approved Deposits';
        $status = 'approved';
        $deposits = $this->depositData($status, userId: $userId);
        return view('admin.deposit.log', compact('pageTitle', 'deposits', 'status'));
    }

    public function successful($userId = null)
    {
        $pageTitle = 'Successful Deposits';
        $status = 'successful';
        $deposits = $this->depositData($status, userId: $userId);
        return view('admin.deposit.log', compact('pageTitle', 'deposits', 'status'));
    }

    public function rejected($userId = null)
    {
        $pageTitle = 'Rejected Deposits';
        $status = 'approved';
        $deposits = $this->depositData($status, userId: $userId);
        return view('admin.deposit.log', compact('pageTitle', 'deposits', 'status'));
    }

    public function initiated($userId = null)
    {
        $pageTitle = 'Initiated Deposits';
        $status = 'initiated';
        $deposits = $this->depositData($status, userId: $userId);
        return view('admin.deposit.log', compact('pageTitle', 'deposits', 'status'));
    }

    public function expired($userId = null)
    {
        $pageTitle = 'Expired Deposits';
        $status = 'expired';
        $deposits = $this->depositData($status, userId: $userId);
        return view('admin.deposit.log', compact('pageTitle', 'deposits', 'status'));
    }

    public function deposit($userId = null)
    {
        $pageTitle = 'Deposit History';
        $depositData = $this->depositData($scope = null, $summary = true, userId: $userId);
        $deposits = $depositData['data'];
        $summary = $depositData['summary'];
        $successful = $summary['successful'];
        $pending = $summary['pending'];
        $rejected = $summary['rejected'];
        $initiated = $summary['initiated'];
        return view('admin.deposit.log', compact('pageTitle', 'deposits', 'successful', 'pending', 'rejected', 'initiated'));
    }

    protected function depositData($scope = null, $summary = false, $userId = null)
    {
        $request = request();
        if ($scope) {
            $deposits = Deposit::$scope()->with([
                'user',
                'gateway',
                'userDiscount',
                'processedBy',
                'bookedTicket.kiosk',
                'bookedTicket.pickup',
                'bookedTicket.drop',
                'bookedTicket.trip.schedule',
                'bookedTicket.trip.fleetType',
            ]);
            if ($scope == 'approved' || $scope == 'rejected') {
                $deposits = $deposits->where('processed_by_admin_id', auth('admin')->id());
            }
        } else {
            $deposits = Deposit::with(['user', 'gateway', 'bookedTicket']);
        }

        if ($userId) {
            $deposits->where('user_id', $userId);
        }

        if ($request->method_code && $request->method_code != 'all') {
            $deposits->where('method_code', request('method_code'));
        }

        $deposits = $deposits->searchable([
            'trx',
            'user:username',
            'bookedTicket:pnr_number'
        ]);

        if (request()->filled('date')) {
            [$from, $to] = explode(' - ', $request->date);

            $deposits->whereBetween(
                DB::raw('DATE(created_at)'),
                [
                    Carbon::parse(trim($from))->toDateString(),
                    Carbon::parse(trim($to))->toDateString(),
                ]
            );
        }


        if ($request->method_code && $request->method_code != 'all') {
            // if ($request->method_code != Status::GOOGLE_PAY) {
            //     $method = Gateway::where('alias', $request->method_code)->firstOrFail();
            //     $deposits = $deposits->where('method_code', $method->code);
            // } else {
            //     $deposits = $deposits->where('method_code', Status::GOOGLE_PAY);
            // }
        }

        if (!$summary) {
            return $deposits->orderBy('id', 'desc')->paginate(getPaginate());
        } else {
            $successful = clone $deposits;
            $pending = clone $deposits;
            $rejected = clone $deposits;
            $initiated = clone $deposits;

            $successfulSummary = $successful->where('status', Status::PAYMENT_SUCCESS)->sum('amount');
            $pendingSummary = $pending->where('status', Status::PAYMENT_PENDING)->sum('amount');
            $rejectedSummary = $rejected->where('status', Status::PAYMENT_REJECT)->sum('amount');
            $initiatedSummary = $initiated->where('status', Status::PAYMENT_INITIATE)->sum('amount');

            return [
                'data' => $deposits->orderBy('id', 'desc')->paginate(getPaginate()),
                'summary' => [
                    'successful' => $successfulSummary,
                    'pending' => $pendingSummary,
                    'rejected' => $rejectedSummary,
                    'initiated' => $initiatedSummary,
                ]
            ];
        }
    }

    public function details($id)
    {
        $deposit = Deposit::where('id', $id)->with([
            'user',
            'gateway',
            'userDiscount',
            'bookedTicket.pickup',
            'bookedTicket.drop',
            'bookedTicket.trip.schedule',
            'bookedTicket.trip.fleetType',
            'bookedTicket.slipSeriesNumbers',
        ])->firstOrFail();

        if ($deposit->status == Status::PAYMENT_PENDING) {
            $notify[] = ['info', 'Pending payments must be processed through the new POS modal on the Pending Payments page.'];
            return to_route('admin.deposit.pending')->withNotify($notify);
        }

        if (!$deposit->user) {
            $pageTitle = "Requested payment from {$deposit->bookedTicket->kiosk->name}";
        } else {
            $pageTitle = $deposit->user->username . ' requested ' . showAmount($deposit->amount);
        }
        $details = ($deposit->detail != null) ? json_encode($deposit->detail) : null;
        return view('admin.deposit.detail', compact('pageTitle', 'deposit', 'details'));
    }


    public function approve($id)
    {
        $notify[] = ['error', 'Legacy POS approval is disabled. Please process pending payments through the new POS modal.'];
        return to_route('admin.deposit.pending')->withNotify($notify);
    }

    public function reject(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'message' => 'required|string|max:255'
        ]);
        $deposit = Deposit::where('id', $request->id)->where('status', Status::PAYMENT_PENDING)->firstOrFail();
        $deposit->processed_by_name = auth('admin')->user()->name;
        $deposit->processed_by_admin_id = auth('admin')->user()->id;
        $deposit->admin_feedback = $request->message;
        $deposit->status = Status::PAYMENT_REJECT;
        $deposit->save();

        $bookedTicket = BookedTicket::find($deposit->booked_ticket_id);
        $bookedTicket->status = 0;
        $bookedTicket->save();


        notify($deposit->user, 'PAYMENT_REJECT', [
            'method_name' => $deposit->gatewayCurrency()->name,
            'method_currency' => $deposit->method_currency,
            'method_amount' => showAmount($deposit->final_amount, currencyFormat: false),
            'amount' => showAmount($deposit->amount, currencyFormat: false),
            'charge' => showAmount($deposit->charge, currencyFormat: false),
            'rate' => showAmount($deposit->rate, currencyFormat: false),
            'trx' => $deposit->trx,
            'rejection_message' => $request->message,
            'journey_date' => showDateTime($bookedTicket->date_of_journey, 'd m, Y'),
            'seats' => implode(',', $bookedTicket->seats),
            'total_seats' => sizeof($bookedTicket->seats),
            'source' => $bookedTicket->pickup->name,
            'destination' => $bookedTicket->drop->name
        ]);

        $notify[] = ['success', 'Deposit request rejected successfully'];
        return to_route('admin.deposit.pending')->withNotify($notify);
    }

    public function export()
    {
        $file_name = "Deposits";

        return Excel::download(
            new PaymentExport,
            $file_name . ' - ' . date('Ymdhi') . '.xlsx'
        );
    }
}
