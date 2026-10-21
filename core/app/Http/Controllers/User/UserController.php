<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\GoogleAuthenticator;
use App\Models\DeviceToken;
use App\Models\BookedTicket;
use App\Models\Transaction;
use App\Models\PassengerNotification;
use App\Support\UserIdentitySanitizer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class UserController extends Controller
{
    public function home()
    {
        $pageTitle = 'Dashboard';
        $emptyMessage = 'No booked ticket found';
        $user = auth()->user();
        $widget = $this->ticketWidgets($user->id);
        $bookedTickets = $this->userTicketsQuery($user->id)
            ->whereNotNull('seats')
            ->paginate(getPaginate());
        $this->prepareTicketCollection($bookedTickets);

        return view('Template::user.dashboard', compact('pageTitle', 'emptyMessage', 'bookedTickets', 'widget'));
    }

    public function depositHistory(Request $request)
    {
        $pageTitle = 'Deposit History';
        $deposits = auth()->user()->deposits()->searchable(['trx'])->with(['gateway'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view('Template::user.deposit_history', compact('pageTitle', 'deposits'));
    }

    public function show2faForm()
    {
        $ga = new GoogleAuthenticator();
        $user = auth()->user();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user->username . '@' . gs('site_name'), $secret);
        $pageTitle = '2FA Security';
        return view('Template::user.twofactor', compact('pageTitle', 'secret', 'qrCodeUrl'));
    }

    public function create2fa(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'key' => 'required',
            'code' => 'required',
        ]);
        $response = verifyG2fa($user, $request->code, $request->key);
        if ($response) {
            $user->tsc = $request->key;
            $user->ts = Status::ENABLE;
            $user->save();
            $notify[] = ['success', 'Two factor authenticator activated successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'Wrong verification code'];
            return back()->withNotify($notify);
        }
    }

    public function disable2fa(Request $request)
    {
        $request->validate([
            'code' => 'required',
        ]);

        $user = auth()->user();
        $response = verifyG2fa($user, $request->code);
        if ($response) {
            $user->tsc = null;
            $user->ts = Status::DISABLE;
            $user->save();
            $notify[] = ['success', 'Two factor authenticator deactivated successfully'];
        } else {
            $notify[] = ['error', 'Wrong verification code'];
        }
        return back()->withNotify($notify);
    }

    public function transactions()
    {
        $pageTitle = 'Transactions';
        $remarks = Transaction::distinct('remark')->orderBy('remark')->get('remark');

        $transactions = Transaction::where('user_id', auth()->id())->searchable(['trx'])->filter(['trx_type', 'remark'])->orderBy('id', 'desc')->paginate(getPaginate());

        return view('Template::user.transactions', compact('pageTitle', 'transactions', 'remarks'));
    }

    public function userData()
    {
        $user = auth()->user();

        if ($user->profile_complete == Status::YES) {
            return to_route('user.home');
        }

        $pageTitle = 'User Data';
        $info = json_decode(json_encode(getIpInfo()), true);
        $mobileCode = @implode(',', $info['code']);
        $countries = json_decode(file_get_contents(resource_path('views/partials/country.json')));

        return view('Template::user.user_data', compact('pageTitle', 'user', 'countries', 'mobileCode'));
    }

    public function userDataSubmit(Request $request)
    {

        $user = auth()->user();

        if ($user->profile_complete == Status::YES) {
            return to_route('user.home');
        }

        $countryData = (array) json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryCodes = implode(',', array_keys($countryData));
        $mobileCodes = implode(',', array_column($countryData, 'dial_code'));
        $countries = implode(',', array_column($countryData, 'country'));

        $request->merge(UserIdentitySanitizer::sanitize(
            $request->only(['username'])
        ));

        $request->validate([
            'country_code' => 'required|in:' . $countryCodes,
            'country' => 'required|in:' . $countries,
            'mobile_code' => 'required|in:' . $mobileCodes,
            'username' => [
                ...UserIdentitySanitizer::usernameRules(),
                Rule::unique('users', 'username'),
            ],
            'mobile' => ['required', 'regex:/^([0-9]*)$/', Rule::unique('users')->where('dial_code', $request->mobile_code)],
        ], [
            'username.regex' => 'Username may contain lowercase letters, numbers, and underscores only.',
        ]);

        $user->country_code = $request->country_code;
        $user->mobile = $request->mobile;
        $user->username = $request->username;


        $user->address = $request->address;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;
        $user->country_name = @$request->country;
        $user->dial_code = $request->mobile_code;

        $user->profile_complete = Status::YES;
        $user->save();

        return to_route('user.home');
    }


    public function addDeviceToken(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'errors' => $validator->errors()->all()];
        }

        $deviceToken = DeviceToken::where('token', $request->token)->first();

        if ($deviceToken) {
            return ['success' => true, 'message' => 'Already exists'];
        }

        $deviceToken = new DeviceToken();
        $deviceToken->user_id = auth()->user()->id;
        $deviceToken->token = $request->token;
        $deviceToken->is_app = Status::NO;
        $deviceToken->save();

        return ['success' => true, 'message' => 'Token saved successfully'];
    }

    public function notifications()
    {
        $pageTitle = 'Notification History';
        $notifications = auth()->user()
            ->passengerNotifications()
            ->latest()
            ->paginate(getPaginate());

        return view('Template::user.notifications', compact('pageTitle', 'notifications'));
    }

    public function notificationDetails(PassengerNotification $notification)
    {
        abort_unless((int) $notification->user_id === (int) auth()->id(), 404);

        return response()->json(
            app(\App\Services\PassengerNotificationPresenter::class)->present($notification)
        );
    }

    public function readNotification(PassengerNotification $notification)
    {
        abort_unless((int) $notification->user_id === (int) auth()->id(), 404);

        if (!$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return to_route('user.notifications.index');
    }

    public function readAllNotifications()
    {
        auth()->user()->passengerNotifications()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    public function downloadAttachment($fileHash)
    {
        $filePath = decrypt($fileHash);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $title = slug(gs('site_name')) . '- attachments.' . $extension;
        try {
            $mimetype = mime_content_type($filePath);
        } catch (\Exception $e) {
            $notify[] = ['error', 'File does not exists'];
            return back()->withNotify($notify);
        }
        header('Content-Disposition: attachment; filename="' . $title);
        header("Content-Type: " . $mimetype);
        return readfile($filePath);
    }

    public function ticketHistory(Request $request)
    {
        $pageTitle = 'My Tickets';
        $emptyMessage = 'No booked ticket found';
        $userId = auth()->id();
        $widget = $this->ticketWidgets($userId);

        $query = $this->userTicketsQuery($userId);

        if ($request->search) {
            $query->where('pnr_number', $request->search);
        }
        $bookedTickets = $query->paginate(getPaginate());
        $this->prepareTicketCollection($bookedTickets);

        return view('Template::user.booking_history', compact('pageTitle', 'emptyMessage', 'bookedTickets', 'widget'));
    }

    private function userTicketsQuery(int $userId)
    {
        return BookedTicket::with([
            'trip.fleetType',
            'trip.startFrom',
            'trip.endTo',
            'trip.schedule',
            'pickup',
            'drop',
            'deposit.gateway',
            'paymentSourceDeposit.gateway',
        ])
            ->where('user_id', $userId)
            ->orderByDesc('id');
    }

    private function ticketWidgets(int $userId): array
    {
        return [
            'booked' => BookedTicket::booked()->where('user_id', $userId)->count(),
            'pending' => BookedTicket::pending()
                ->whereDoesntHave('trip.schedule', function ($query) {
                    $query->whereRaw(
                        "CONCAT(booked_tickets.date_of_journey, ' ', schedules.start_from) < ?",
                        [now()->format('Y-m-d H:i:s')]
                    );
                })
                ->whereNotNull('seats')
                ->where('user_id', $userId)
                ->count(),
            'rejected' => BookedTicket::rejected()->where('user_id', $userId)->count(),
        ];
    }

    private function prepareTicketCollection($bookedTickets): void
    {
        $bookedTickets->getCollection()->transform(function (BookedTicket $ticket): array {
            $ticket->useCanonicalPaymentRecord();

            return $this->ticketDisplayData($ticket);
        });
    }

    private function ticketDisplayData(BookedTicket $ticket): array
    {
        $payment = $ticket->deposit;
        $journeyPassed = false;

        try {
            $journeyPassed = Carbon::parse(
                $ticket->date_of_journey . ' ' . ($ticket->trip?->schedule?->start_from ?: '00:00:00')
            )->isPast();
        } catch (\Throwable $exception) {
            $journeyPassed = false;
        }

        [$ticketStatus, $ticketTone] = match ((int) $ticket->status) {
            Status::BOOKED_APPROVED => ['Booked', 'success'],
            Status::BOOKED_PENDING => $journeyPassed && (int) $payment?->status !== Status::PAYMENT_SUCCESS
                ? ['Expired', 'warning']
                : ['Pending', 'warning'],
            Status::BOOKED_EXPIRED => ['Expired', 'warning'],
            Status::BOOKED_REFUNDED => ['Refunded', 'info'],
            Status::BOOKED_CANCELLED => ['Cancelled', 'danger'],
            Status::BOOKED_VOIDED => ['Voided', 'danger'],
            default => ['Rejected', 'danger'],
        };

        $paymentExpired = $payment
            && (int) $payment->status !== Status::PAYMENT_SUCCESS
            && ((int) $payment->status === Status::PAYMENT_EXPIRED
                || (filled($payment->expiry_limit) && isExpired($payment->expiry_limit)));
        [$paymentStatus, $paymentTone] = $paymentExpired
            ? ['Expired', 'warning']
            : match ((int) $payment?->status) {
                Status::PAYMENT_SUCCESS => ['Paid', 'success'],
                Status::PAYMENT_REJECT => ['Rejected', 'danger'],
                Status::PAYMENT_EXPIRED => ['Expired', 'warning'],
                default => ['Pending', 'warning'],
            };

        $paymentMethod = $payment?->gateway?->name ?: '-';
        if ($payment?->gateway?->name === 'Paynamics' && filled($payment->pchannel)) {
            $paymentMethod = getPaynamicsPChannel($payment->pchannel, true);
        }

        $fareAmount = (float) $ticket->sub_total;
        $paymentFee = (float) ($payment?->charge ?? 0) * (float) ($payment?->rate ?: 1);
        $totalAmount = (float) ($payment?->final_amount ?? $fareAmount);
        $canAccessVoucher = ((int) $ticket->status === Status::BOOKED_APPROVED
                && (!$payment?->expiry_limit || !isExpired($payment->expiry_limit)))
            || (int) $payment?->status === Status::PAYMENT_SUCCESS;
        $canPay = (int) $payment?->status === Status::PAYMENT_PENDING
            && filled($payment?->expiry_limit)
            && !isExpired($payment->expiry_limit);

        return [
            'id' => $ticket->id,
            'pnr' => $ticket->pnr_number ?: '-',
            'origin' => $ticket->pickup?->name ?: $ticket->trip?->startFrom?->name ?: '-',
            'destination' => $ticket->drop?->name ?: $ticket->trip?->endTo?->name ?: '-',
            'travel_date' => $ticket->date_of_journey
                ? Carbon::parse($ticket->date_of_journey)->format('d M, Y')
                : '-',
            'travel_date_long' => $ticket->date_of_journey
                ? Carbon::parse($ticket->date_of_journey)->format('D, M j, Y')
                : '-',
            'departure_time' => $ticket->trip?->schedule?->start_from
                ? Carbon::parse($ticket->trip->schedule->start_from)->format('g:i A')
                : '-',
            'bus_class' => $ticket->trip?->fleetType?->name ?: '-',
            'seats' => $ticket->seats ? formatSeatLabel($ticket->seats) : '-',
            'ticket_status' => $ticketStatus,
            'ticket_tone' => $ticketTone,
            'fare' => showAmount($fareAmount),
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'payment_tone' => $paymentTone,
            'transaction_id' => $payment?->trx ?: '-',
            'fare_amount' => showAmount($fareAmount),
            'payment_fee' => showAmount($paymentFee),
            'total_amount' => showAmount($totalAmount),
            'can_access_voucher' => $canAccessVoucher,
            'can_pay' => $canPay,
            'print_url' => $canAccessVoucher ? route('user.ticket.print', $ticket->id) : null,
            'download_url' => $canAccessVoucher ? route('user.ticket.download', $ticket->id) : null,
            'voucher_url' => $canAccessVoucher ? route('user.ticket.view', $ticket->id) : null,
            'pay_url' => $canPay ? url("/user/payment/deposit?booked_ticket_id={$ticket->id}") : null,
        ];
    }

    public function getTicketDetails($id)
    {
        $ticket = BookedTicket::with([
            'trip.fleetType',
            'trip.startFrom',
            'trip.endTo',
            'trip.schedule',
            'trip.assignedVehicle.vehicle',
            'pickup',
            'drop',
            'user',
            'deposit.gateway',
            'paymentSourceDeposit.gateway',
        ]);

        $ticket = $ticket->findOrFail($id);
        $ticket->useCanonicalPaymentRecord();

        return $ticket;
    }

    public function printTicket($id)
    {
        $pageTitle = "Ticket Print";

        $ticket = $this->getTicketDetails($id);
        //  return view('Template::user.print_ticket', ['ticket' => $ticket, 'pageTitle' => $pageTitle]);
        $pdf = Pdf::setOptions([
            'isRemoteEnabled' => true
        ])->loadView('Template::user.print_ticket', ['ticket' => $ticket, 'pageTitle' => $pageTitle]);



        if ($ticket->kiosk_id) {
            // $pdf->setPaper([0, 0, 226.77, 1200], 'portrait');
            $pdf->setPaper([0, 0, 144, 600], 'portrait');
        } else {
            $pdf->setPaper('A4', 'portrait');
        }

        return $pdf->stream("$ticket->pnr_number.pdf");
    }

    public function printTicketView($id)
    {
        $pageTitle = "Ticket Print";

        $ticket = $this->getTicketDetails($id);

        return view('Template::user.print_ticket', ['ticket' => $ticket, 'pageTitle' => $pageTitle]);
    }

    public function downloadTicket($id)
    {
        $pageTitle = "Ticket Print";

        $ticket = $this->getTicketDetails($id);

        $pdf = Pdf::setOptions([
            'isRemoteEnabled' => true
        ])->loadView('Template::user.print_ticket', ['ticket' => $ticket, 'pageTitle' => $pageTitle]);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("$ticket->pnr_number.pdf");
    }

}
