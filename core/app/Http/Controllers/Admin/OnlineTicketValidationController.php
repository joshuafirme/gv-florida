<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\BookedTicket;
use App\Models\Discount;
use App\Models\OnlineTicketValidation;
use App\Models\SlipSeriesNumber;
use App\Services\CashierTransactionRecorder;
use App\Services\TransactionAuthorizationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OnlineTicketValidationController extends Controller
{
    public function __construct(
        private readonly CashierTransactionRecorder $transactionRecorder
    ) {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(['all', 'to_validate', 'validated'])],
        ]);
        $pageTitle = 'Online Ticket Validation';
        $search = trim((string) ($validated['search'] ?? ''));
        $status = $validated['status'] ?? 'all';

        $countRow = $this->eligibleTicketsQuery()
            ->reorder()
            ->select([
                DB::raw('COUNT(*) AS total_count'),
                DB::raw('SUM(CASE WHEN validation_record.validated_at IS NULL THEN 1 ELSE 0 END) AS to_validate_count'),
                DB::raw('SUM(CASE WHEN validation_record.validated_at IS NOT NULL THEN 1 ELSE 0 END) AS validated_count'),
            ])
            ->first();
        $counts = [
            'all' => (int) ($countRow?->total_count ?? 0),
            'to_validate' => (int) ($countRow?->to_validate_count ?? 0),
            'validated' => (int) ($countRow?->validated_count ?? 0),
        ];
        $searchLike = '%' . addcslashes($search, '\\%_') . '%';

        $query = $this->eligibleTicketsQuery()
            ->when($search !== '', function ($query) use ($searchLike) {
                $query->leftJoin('users as validation_user', 'validation_user.id', '=', 'validation_ticket.user_id')
                    ->where(function ($searchQuery) use ($searchLike) {
                        $searchQuery->where('slip_series_numbers.id', 'like', $searchLike)
                            ->orWhere('slip_series_numbers.seat', 'like', $searchLike)
                            ->orWhere('validation_ticket.pnr_number', 'like', $searchLike)
                            ->orWhere('validation_ticket.passenger_manifest', 'like', $searchLike)
                            ->orWhere('validation_user.firstname', 'like', $searchLike)
                            ->orWhere('validation_user.lastname', 'like', $searchLike)
                            ->orWhereRaw("CONCAT_WS(' ', validation_user.firstname, validation_user.lastname) LIKE ?", [$searchLike])
                            ->orWhere('direct_payment.trx', 'like', $searchLike)
                            ->orWhere('source_payment.trx', 'like', $searchLike);
                    });
            })
            ->when($status === 'to_validate', fn ($query) => $query->whereNull('validation_record.validated_at'))
            ->when($status === 'validated', fn ($query) => $query->whereNotNull('validation_record.validated_at'))
            ->with($this->indexRelations())
            ->latest('slip_series_numbers.id');

        $paginationTotal = $search === '' ? $counts[$status] : null;
        $tickets = $query
            ->paginate(getPaginate(), ['*'], 'page', null, $paginationTotal)
            ->withQueryString();
        $tickets->getCollection()->transform(fn (SlipSeriesNumber $slip) => $this->ticketData($slip));

        return view('admin.ticket.online-validation', compact(
            'pageTitle',
            'tickets',
            'search',
            'status',
            'counts'
        ));
    }

    public function details(SlipSeriesNumber $slip)
    {
        $slip->loadMissing($this->relations());
        $this->ensureOnlinePaidTicket($slip);

        return response()->json($this->ticketData($slip, true));
    }

    public function applyDiscount(Request $request, SlipSeriesNumber $slip)
    {
        $validated = $request->validate([
            'discount_id' => ['required', 'integer', Rule::exists('discounts', 'id')->where('status', Status::ENABLE)],
            'passenger_name' => 'required|string|max:255',
            'passenger_id' => 'required|string|max:100',
            'authorization_code' => 'required|string|max:100',
            'reason' => 'nullable|string|max:500',
            'approval_remarks' => 'nullable|string|max:500',
        ]);

        $authorizedBy = app(TransactionAuthorizationService::class)->authorize(
            $validated['authorization_code'],
            TransactionAuthorizationService::DISCOUNT_OVERRIDE,
            [
                'booked_ticket_id' => $slip->booked_ticket_id,
                'slip_series_number_id' => $slip->id,
                'reference_no' => (string) $slip->id,
                'seat_no' => $slip->seat,
                'reason' => $validated['reason'] ?? 'Online ticket discount override',
            ]
        );

        DB::transaction(function () use ($slip, $validated, $authorizedBy) {
            $lockedSlip = SlipSeriesNumber::query()->lockForUpdate()->findOrFail($slip->id);
            $lockedSlip->loadMissing($this->relations());
            $this->ensureOnlinePaidTicket($lockedSlip);

            $record = OnlineTicketValidation::query()->firstOrNew([
                'slip_series_number_id' => $lockedSlip->id,
            ]);
            if ($record->validated_at) {
                throw ValidationException::withMessages([
                    'discount_id' => 'A discount cannot be applied after the ticket has been validated.',
                ]);
            }

            $discount = Discount::where('status', Status::ENABLE)->findOrFail($validated['discount_id']);
            $snapshot = $this->transactionRecorder->ticketSnapshotFor($lockedSlip->bookedTicket, $lockedSlip);
            $originalFare = (float) ($snapshot['fare'] ?? $snapshot['base_fare'] ?? 0);
            $discountAmount = round($originalFare * ((float) $discount->percentage / 100), 2);

            $record->fill([
                'booked_ticket_id' => $lockedSlip->booked_ticket_id,
                'deposit_id' => $lockedSlip->bookedTicket->payment_record?->id,
                'discount_id' => $discount->id,
                'discount_applied_by_admin_id' => auth('admin')->id(),
                'discount_authorized_by_admin_id' => $authorizedBy->id,
                'discount_authorized_at' => now(),
                'original_fare' => $originalFare,
                'discount_percentage' => (float) $discount->percentage,
                'discount_amount' => $discountAmount,
                'net_fare' => max($originalFare - $discountAmount, 0),
                'passenger_name' => trim($validated['passenger_name']),
                'passenger_id' => trim($validated['passenger_id']),
                'reason' => trim((string) ($validated['reason'] ?? ''))
                    ?: "{$discount->name} discount applied during online ticket validation.",
                'approval_remarks' => trim((string) ($validated['approval_remarks'] ?? '')) ?: null,
            ]);
            $record->save();

            $this->transactionRecorder->recordOnlineDiscountOverride($record->fresh());
        });

        $slip->refresh()->loadMissing($this->relations());

        return response()->json([
            'message' => 'Discount override applied. Validate the ticket to complete verification.',
            'ticket' => $this->ticketData($slip, true),
        ]);
    }

    public function validateTicket(SlipSeriesNumber $slip)
    {
        $validation = DB::transaction(function () use ($slip) {
            $lockedSlip = SlipSeriesNumber::query()->lockForUpdate()->findOrFail($slip->id);
            $lockedSlip->loadMissing($this->relations());
            $this->ensureOnlinePaidTicket($lockedSlip);

            $record = OnlineTicketValidation::query()->firstOrNew([
                'slip_series_number_id' => $lockedSlip->id,
            ]);
            if (!$record->exists) {
                $snapshot = $this->transactionRecorder->ticketSnapshotFor($lockedSlip->bookedTicket, $lockedSlip);
                $fare = (float) ($snapshot['fare'] ?? 0);
                $record->fill([
                    'booked_ticket_id' => $lockedSlip->booked_ticket_id,
                    'deposit_id' => $lockedSlip->bookedTicket->payment_record?->id,
                    'original_fare' => $fare,
                    'net_fare' => $fare,
                ]);
            }

            if (!$record->validated_at) {
                $record->validated_by_admin_id = auth('admin')->id();
                $record->validated_at = now();
                $record->save();
                $this->transactionRecorder->recordOnlineValidation($record->fresh());
            }

            return $record;
        });

        return response()->json([
            'message' => 'Online ticket validated successfully.',
            'validated_at' => $validation->validated_at?->format('M j, Y h:i A'),
            'print_url' => route('admin.trip.reservationSlip', [
                'id' => $slip->booked_ticket_id,
                'slip_id' => $slip->id,
            ]),
        ]);
    }

    private function eligibleTicketsQuery()
    {
        return SlipSeriesNumber::query()
            ->join('booked_tickets as validation_ticket', 'validation_ticket.id', '=', 'slip_series_numbers.booked_ticket_id')
            ->leftJoin('deposits as direct_payment', 'direct_payment.booked_ticket_id', '=', 'validation_ticket.id')
            ->leftJoin('deposits as source_payment', 'source_payment.id', '=', 'validation_ticket.payment_source_deposit_id')
            ->leftJoin('ticket_refunds as validation_refund', 'validation_refund.slip_series_number_id', '=', 'slip_series_numbers.id')
            ->leftJoin('ticket_cancellations as validation_cancellation', 'validation_cancellation.slip_series_number_id', '=', 'slip_series_numbers.id')
            ->leftJoin('ticket_voids as validation_void', 'validation_void.slip_series_number_id', '=', 'slip_series_numbers.id')
            ->leftJoin('online_ticket_validations as validation_record', 'validation_record.slip_series_number_id', '=', 'slip_series_numbers.id')
            ->whereNotNull('validation_ticket.user_id')
            ->where(function ($kiosk) {
                $kiosk->whereNull('validation_ticket.kiosk_id')
                    ->orWhere('validation_ticket.kiosk_id', 0);
            })
            ->where('validation_ticket.status', Status::BOOKED_APPROVED)
            ->where(function ($payment) {
                $payment->where('direct_payment.status', Status::PAYMENT_SUCCESS)
                    ->orWhere('source_payment.status', Status::PAYMENT_SUCCESS);
            })
            ->whereNull('validation_refund.id')
            ->whereNull('validation_cancellation.id')
            ->whereNull('validation_void.id')
            ->select('slip_series_numbers.*');
    }

    private function ensureOnlinePaidTicket(SlipSeriesNumber $slip): void
    {
        $ticket = $slip->bookedTicket;
        $payment = $ticket?->payment_record;
        $active = $ticket
            && $ticket->user_id
            && !$ticket->isKioskBooking()
            && (int) $ticket->status === Status::BOOKED_APPROVED
            && $payment
            && (int) $payment->status === Status::PAYMENT_SUCCESS
            && !$slip->refund
            && !$slip->cancellation
            && !$slip->voidRecord;

        if (!$active) {
            throw ValidationException::withMessages([
                'ticket' => 'This ticket is not an active, successfully paid online ticket.',
            ]);
        }
    }

    private function ticketData(SlipSeriesNumber $slip, bool $includeDiscounts = false): array
    {
        $ticket = $slip->bookedTicket;
        $payment = $ticket->payment_record;
        $validation = $slip->onlineValidation;
        $contact = $this->passengerContact($ticket);
        $snapshot = $this->transactionRecorder->ticketSnapshotFor($ticket, $slip);
        $originalFare = (float) ($validation?->original_fare ?: ($snapshot['fare'] ?? 0));
        $discountAmount = (float) ($validation?->discount_amount ?? 0);
        $netFare = (float) ($validation?->net_fare ?: max($originalFare - $discountAmount, 0));
        $journeyDate = $ticket->date_of_journey
            ? Carbon::parse($ticket->date_of_journey)->format('M j, Y')
            : '-';
        $departureTime = $ticket->trip?->schedule?->start_from
            ? Carbon::parse($ticket->trip->schedule->start_from)->format('h:i A')
            : '-';

        $data = [
            'slip_id' => $slip->id,
            'reference_no' => (string) $slip->id,
            'request_no' => $payment?->trx ?: '-',
            'pnr' => $ticket->pnr_number ?: '-',
            'passenger_name' => $validation?->passenger_name ?: ($snapshot['passenger_name'] ?? 'Guest'),
            'booking_passenger_name' => $this->providedPassengerName($snapshot['passenger_name'] ?? null),
            'passenger_type' => $validation?->discount?->name ?: ($snapshot['passenger_type'] ?? 'Regular'),
            'passenger_id' => $validation?->passenger_id ?: ($snapshot['passenger_id'] ?? null),
            'passenger_email' => $contact['email'],
            'passenger_phone' => $contact['phone'],
            'journey_date' => $journeyDate,
            'departure_time' => $departureTime,
            'trip_class' => $snapshot['trip_class'] ?? '-',
            'trip_route' => $snapshot['trip_route'] ?? '-',
            'seat' => formatSeatLabel($slip->seat),
            'drop_off' => $snapshot['drop_off'] ?? '-',
            'km_post' => $snapshot['km_post'] ?? null,
            'payment_method' => $snapshot['payment_method'] ?? '-',
            'payment_status' => 'Successful',
            'original_fare' => $originalFare,
            'discount_name' => $validation?->discount?->name,
            'discount_percentage' => (float) ($validation?->discount_percentage ?? 0),
            'discount_amount' => $discountAmount,
            'net_fare' => $netFare,
            'discount_authorized_by' => $validation?->discountAuthorizedBy?->name,
            'discount_authorized_at' => $validation?->discount_authorized_at?->format('M j, Y h:i A'),
            'approval_remarks' => $validation?->approval_remarks,
            'validated' => (bool) $validation?->validated_at,
            'validated_by' => $validation?->validator?->name ?: $validation?->validator?->username,
            'validated_at' => $validation?->validated_at?->format('M j, Y h:i A'),
            'details_url' => route('admin.online.ticket.validation.details', $slip),
            'discount_url' => route('admin.online.ticket.validation.discount', $slip),
            'validate_url' => route('admin.online.ticket.validation.validate', $slip),
            'print_url' => route('admin.trip.reservationSlip', ['id' => $ticket->id, 'slip_id' => $slip->id]),
            'rebook_url' => route('admin.vehicle.ticket.booked', ['rebook_ticket' => $ticket->id, 'slip_id' => $slip->id]),
            'cancel_url' => route('admin.vehicle.ticket.booked', ['ticket_action' => 'cancel', 'slip_id' => $slip->id]),
            'refund_url' => route('admin.vehicle.ticket.booked', ['ticket_action' => 'refund', 'slip_id' => $slip->id]),
        ];

        if ($includeDiscounts) {
            $data['discounts'] = Discount::query()
                ->where('status', Status::ENABLE)
                ->orderBy('name')
                ->get(['id', 'name', 'percentage']);
        }

        return $data;
    }

    private function passengerContact(BookedTicket $ticket): array
    {
        $email = trim((string) $ticket->user?->email);
        $phone = trim((string) $ticket->user?->mobileNumber);

        if ($phone !== '' && !str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return [
            'email' => $email ?: null,
            'phone' => $phone ?: null,
        ];
    }

    private function providedPassengerName(?string $name): string
    {
        $name = trim((string) $name);

        return in_array(strtolower($name), ['', 'guest', 'passenger'], true) ? '' : $name;
    }

    private function relations(): array
    {
        return [
            'refund',
            'cancellation',
            'voidRecord',
            'onlineValidation.discount',
            'onlineValidation.validator:id,name,username',
            'onlineValidation.discountAuthorizedBy:id,name,username',
            'bookedTicket.trip.schedule',
            'bookedTicket.trip.route',
            'bookedTicket.trip.fleetType',
            'bookedTicket.pickup',
            'bookedTicket.drop',
            'bookedTicket.user',
            'bookedTicket.deposit.userDiscount',
            'bookedTicket.deposit.processedBy:id,name,username',
            'bookedTicket.paymentSourceDeposit.userDiscount',
            'bookedTicket.paymentSourceDeposit.processedBy:id,name,username',
            'bookedTicket.slipSeriesNumbers',
        ];
    }

    private function indexRelations(): array
    {
        return array_values(array_diff($this->relations(), [
            'refund',
            'cancellation',
            'voidRecord',
        ]));
    }
}
