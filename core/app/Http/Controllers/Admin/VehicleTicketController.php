<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\BusLayout;
use App\Models\Admin;
use App\Models\BookedTicket;
use App\Models\Counter;
use App\Models\FleetType;
use App\Models\SlipSeriesNumber;
use App\Models\Trip;
use App\Models\VehicleRoute;
use App\Models\TicketPrice;
use App\Models\TicketPriceByStoppage;
use App\Models\TicketCancellation;
use App\Models\TicketRefund;
use App\Models\TicketVoid;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VehicleTicketController extends Controller
{
    public function booked()
    {
        $pageTitle = 'Booked Ticket';
        $tickets = $this->bookedTicketRows()->paginate(getPaginate());
        $ticketRows = true;
        return view('admin.ticket.log', compact('pageTitle', 'tickets', 'ticketRows'));
    }

    public function refunded()
    {
        $pageTitle = 'Refunded Tickets';
        $search = trim((string) request('search'));
        $refunds = TicketRefund::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('reason', 'like', "%{$search}%")
                        ->orWhereHas('slipSeriesNumber', fn ($slip) => $slip->where('id', 'like', "%{$search}%"))
                        ->orWhereHas('bookedTicket', function ($ticket) use ($search) {
                            $ticket->where('pnr_number', 'like', "%{$search}%")
                                ->orWhere('series_number', 'like', "%{$search}%")
                                ->orWhereHas('deposit.userDiscount', fn ($discount) => $discount->where('passenger_name', 'like', "%{$search}%"));
                        });
                });
            })
            ->with([
                'slipSeriesNumber',
                'bookedTicket.trip.schedule',
                'bookedTicket.trip.fleetType',
                'bookedTicket.pickup',
                'bookedTicket.drop',
                'bookedTicket.user',
                'bookedTicket.kiosk',
                'bookedTicket.deposit.userDiscount',
                'processedBy',
                'authorizedBy',
            ])
            ->latest()
            ->paginate(getPaginate())
            ->withQueryString();

        return view('admin.ticket.refunded', compact('pageTitle', 'refunds', 'search'));
    }

    public function cancelled()
    {
        $pageTitle = 'Cancelled Ticket';
        $search = trim((string) request('search'));
        $cancellations = TicketCancellation::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('reason', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhere('transaction_snapshot', 'like', "%{$search}%")
                        ->orWhereHas('slipSeriesNumber', fn ($slip) => $slip->where('id', 'like', "%{$search}%"))
                        ->orWhereHas('bookedTicket', function ($ticket) use ($search) {
                            $ticket->where('pnr_number', 'like', "%{$search}%")
                                ->orWhere('series_number', 'like', "%{$search}%")
                                ->orWhereHas('deposit.userDiscount', fn ($discount) => $discount->where('passenger_name', 'like', "%{$search}%"));
                        });
                });
            })
            ->with([
                'slipSeriesNumber',
                'bookedTicket.trip.schedule',
                'bookedTicket.trip.fleetType',
                'bookedTicket.pickup',
                'bookedTicket.drop',
                'bookedTicket.user',
                'bookedTicket.kiosk',
                'bookedTicket.deposit.userDiscount',
                'processedBy',
                'authorizedBy',
            ])
            ->latest()
            ->paginate(getPaginate())
            ->withQueryString();

        return view('admin.ticket.cancelled', compact('pageTitle', 'cancellations', 'search'));
    }

    public function voided()
    {
        $pageTitle = 'Voided Tickets';
        $search = trim((string) request('search'));
        $voids = TicketVoid::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('reason', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhere('transaction_snapshot', 'like', "%{$search}%")
                        ->orWhereHas('slipSeriesNumber', fn ($slip) => $slip->where('id', 'like', "%{$search}%"))
                        ->orWhereHas('bookedTicket', function ($ticket) use ($search) {
                            $ticket->where('pnr_number', 'like', "%{$search}%")
                                ->orWhere('passenger_manifest', 'like', "%{$search}%")
                                ->orWhereHas('deposit.userDiscount', fn ($discount) => $discount->where('passenger_name', 'like', "%{$search}%"));
                        });
                });
            })
            ->with([
                'slipSeriesNumber',
                'bookedTicket.trip.route',
                'bookedTicket.trip.schedule',
                'bookedTicket.trip.fleetType',
                'bookedTicket.pickup',
                'bookedTicket.drop',
                'bookedTicket.user',
                'bookedTicket.kiosk',
                'bookedTicket.deposit.userDiscount',
                'processedBy',
                'authorizedBy',
            ])
            ->latest()
            ->paginate(getPaginate())
            ->withQueryString();

        return view('admin.ticket.voided', compact('pageTitle', 'voids', 'search'));
    }

    public function refundOptions($slip)
    {
        $slip = $this->refundableSlip($slip);
        $ticket = $slip->bookedTicket;
        $fare = $this->ticketFare($ticket);

        return response()->json([
            'slip_id' => $slip->id,
            'booking_id' => $ticket->id,
            'pnr' => $ticket->pnr_number,
            'reference' => (string) $slip->id,
            'passenger_name' => $ticket->deposit?->userDiscount?->passenger_name
                ?: $ticket->user?->fullname
                ?: 'Guest',
            'passenger_type' => getPassengerType($ticket->deposit),
            'seat' => $slip->seat,
            'fare' => $fare,
            'default_refund' => round($fare * 0.5, 2),
            'processed_by' => auth('admin')->user()->name,
            'reasons' => [
                'Passenger no-show',
                'Change of plans',
                'Duplicate booking',
                'Wrong trip / seat',
                'Trip cancelled',
                'Medical / emergency',
            ],
            'confirm_url' => route('admin.vehicle.ticket.refund.confirm', $slip->id),
        ]);
    }

    public function confirmRefund(Request $request, $slip)
    {
        $validated = $request->validate([
            'reason' => 'required|in:Passenger no-show,Change of plans,Duplicate booking,Wrong trip / seat,Trip cancelled,Medical / emergency',
            'refund_amount' => 'required|numeric|min:0.01',
            'remarks' => 'required|string|max:1000',
            'authorization_code' => 'required|string|max:100',
        ]);

        $authorizedBy = Admin::where('status', Status::ENABLE)
            ->where('passcode', $validated['authorization_code'])
            ->first();

        if (!$authorizedBy) {
            throw ValidationException::withMessages([
                'authorization_code' => 'The authorization code is invalid or belongs to an inactive administrator.',
            ]);
        }

        $refund = DB::transaction(function () use ($slip, $validated, $authorizedBy) {
            $slip = SlipSeriesNumber::whereDoesntHave('refund')
                ->whereDoesntHave('cancellation')
                ->whereDoesntHave('voidRecord')
                ->whereHas('bookedTicket', fn ($ticket) => $ticket->booked())
                ->with(['bookedTicket.deposit', 'bookedTicket.slipSeriesNumbers.refund'])
                ->lockForUpdate()
                ->findOrFail($slip);
            $ticket = $slip->bookedTicket;
            $fare = $this->ticketFare($ticket);

            if ((float) $validated['refund_amount'] > $fare) {
                throw ValidationException::withMessages([
                    'refund_amount' => 'The refund amount cannot exceed the ticket fare of ' . showAmount($fare) . '.',
                ]);
            }

            $refund = TicketRefund::create([
                'booked_ticket_id' => $ticket->id,
                'slip_series_number_id' => $slip->id,
                'processed_by_admin_id' => auth('admin')->id(),
                'authorized_by_admin_id' => $authorizedBy->id,
                'original_fare' => $fare,
                'refund_amount' => $validated['refund_amount'],
                'reason' => $validated['reason'],
                'remarks' => $validated['remarks'],
            ]);

            $remainingSeats = $ticket->slipSeriesNumbers()
                ->whereDoesntHave('refund')
                ->whereDoesntHave('cancellation')
                ->whereDoesntHave('voidRecord')
                ->pluck('seat')
                ->values()
                ->all();
            $ticket->seats = $remainingSeats;
            $ticket->ticket_count = count($remainingSeats);
            if (!$remainingSeats) {
                $ticket->status = Status::BOOKED_REFUNDED;
            }
            $ticket->save();

            return $refund;
        });

        return response()->json([
            'success' => true,
            'message' => 'Refund confirmed successfully. The seat has been released and the ticket was moved to Refunded Tickets.',
            'redirect_url' => route('admin.vehicle.ticket.refunded'),
            'refund_id' => $refund->id,
        ]);
    }

    public function cancelOptions($slip)
    {
        $slip = $this->cancellableSlip($slip);
        $ticket = $slip->bookedTicket;
        $fare = $this->ticketFare($ticket);

        return response()->json([
            'slip_id' => $slip->id,
            'booking_id' => $ticket->id,
            'pnr' => $ticket->pnr_number,
            'reference' => (string) $slip->id,
            'passenger_name' => $ticket->deposit?->userDiscount?->passenger_name
                ?: $ticket->user?->fullname
                ?: 'Guest',
            'passenger_type' => getPassengerType($ticket->deposit),
            'seat' => $slip->seat,
            'fare' => $fare,
            'processed_by' => auth('admin')->user()->name,
            'reasons' => [
                'Passenger no-show',
                'Change of plans',
                'Duplicate booking',
                'Wrong trip / seat',
                'Trip cancelled',
                'Medical / emergency',
            ],
            'confirm_url' => route('admin.vehicle.ticket.cancel.confirm', $slip->id),
        ]);
    }

    public function confirmCancellation(Request $request, $slip)
    {
        $validated = $request->validate([
            'reason' => 'required|in:Passenger no-show,Change of plans,Duplicate booking,Wrong trip / seat,Trip cancelled,Medical / emergency',
            'remarks' => 'required|string|max:1000',
            'authorization_code' => 'required|string|max:100',
        ]);

        $authorizedBy = Admin::where('status', Status::ENABLE)
            ->where('passcode', $validated['authorization_code'])
            ->first();

        if (!$authorizedBy) {
            throw ValidationException::withMessages([
                'authorization_code' => 'The authorization code is invalid or belongs to an inactive administrator.',
            ]);
        }

        $cancellation = DB::transaction(function () use ($slip, $validated, $authorizedBy) {
            $slip = SlipSeriesNumber::whereDoesntHave('refund')
                ->whereDoesntHave('cancellation')
                ->whereDoesntHave('voidRecord')
                ->whereHas('bookedTicket', fn ($ticket) => $ticket->booked())
                ->with(['bookedTicket.deposit', 'bookedTicket.slipSeriesNumbers.refund', 'bookedTicket.slipSeriesNumbers.cancellation'])
                ->lockForUpdate()
                ->findOrFail($slip);
            $ticket = $slip->bookedTicket;
            $fare = $this->ticketFare($ticket);

            $cancellation = TicketCancellation::create([
                'booked_ticket_id' => $ticket->id,
                'slip_series_number_id' => $slip->id,
                'processed_by_admin_id' => auth('admin')->id(),
                'authorized_by_admin_id' => $authorizedBy->id,
                'original_fare' => $fare,
                'reason' => $validated['reason'],
                'remarks' => $validated['remarks'],
            ]);

            $remainingSeats = $ticket->slipSeriesNumbers()
                ->whereDoesntHave('refund')
                ->whereDoesntHave('cancellation')
                ->whereDoesntHave('voidRecord')
                ->pluck('seat')
                ->values()
                ->all();
            $ticket->seats = $remainingSeats;
            $ticket->ticket_count = count($remainingSeats);
            if (!$remainingSeats) {
                $ticket->status = Status::BOOKED_CANCELLED;
            }
            $ticket->save();

            return $cancellation;
        });

        return response()->json([
            'success' => true,
            'message' => 'Cancellation confirmed successfully. The seat has been released and the ticket was moved to Cancelled Tickets.',
            'redirect_url' => route('admin.vehicle.ticket.cancelled'),
            'acknowledgment_url' => route('admin.vehicle.ticket.cancel.acknowledgment', $cancellation->id),
            'cancellation_id' => $cancellation->id,
        ]);
    }

    public function voidOptions($slip)
    {
        $slip = $this->voidableSlip($slip);
        $ticket = $slip->bookedTicket;
        $passenger = $this->ticketPassenger($ticket, $slip);

        return response()->json([
            'slip_id' => $slip->id,
            'booking_id' => $ticket->id,
            'pnr' => $ticket->pnr_number,
            'reference' => (string) $slip->id,
            'passenger_name' => $passenger['name'],
            'passenger_type' => $passenger['type'],
            'passenger_id' => $passenger['id_number'],
            'seat' => $slip->seat,
            'fare' => $passenger['fare'],
            'processed_by' => auth('admin')->user()->name,
            'reasons' => $this->voidReasons(),
            'confirm_url' => route('admin.vehicle.ticket.void.confirm', $slip->id),
        ]);
    }

    public function confirmVoid(Request $request, $slip)
    {
        $validated = $request->validate([
            'reason' => 'required|in:' . implode(',', $this->voidReasons()),
            'remarks' => 'required|string|max:1000',
            'authorization_code' => 'required|string|max:100',
        ]);

        $authorizedBy = Admin::where('status', Status::ENABLE)
            ->where('passcode', $validated['authorization_code'])
            ->first();

        if (!$authorizedBy) {
            throw ValidationException::withMessages([
                'authorization_code' => 'The authorization code is invalid or belongs to an inactive administrator.',
            ]);
        }

        $ticketVoid = DB::transaction(function () use ($slip, $validated, $authorizedBy) {
            $slip = SlipSeriesNumber::whereDoesntHave('refund')
                ->whereDoesntHave('cancellation')
                ->whereDoesntHave('voidRecord')
                ->whereHas('bookedTicket', fn ($ticket) => $ticket->booked())
                ->with([
                    'bookedTicket.deposit.userDiscount',
                    'bookedTicket.user',
                    'bookedTicket.slipSeriesNumbers.refund',
                    'bookedTicket.slipSeriesNumbers.cancellation',
                    'bookedTicket.slipSeriesNumbers.voidRecord',
                ])
                ->lockForUpdate()
                ->findOrFail($slip);
            $ticket = $slip->bookedTicket;
            $passenger = $this->ticketPassenger($ticket, $slip);

            $ticketVoid = TicketVoid::create([
                'booked_ticket_id' => $ticket->id,
                'slip_series_number_id' => $slip->id,
                'processed_by_admin_id' => auth('admin')->id(),
                'authorized_by_admin_id' => $authorizedBy->id,
                'original_fare' => $passenger['fare'],
                'returned_amount' => $passenger['fare'],
                'reason' => $validated['reason'],
                'remarks' => $validated['remarks'],
                'transaction_snapshot' => $this->voidSnapshot($ticket, $slip, $passenger, $authorizedBy),
            ]);

            $remainingSeats = $ticket->slipSeriesNumbers()
                ->whereDoesntHave('refund')
                ->whereDoesntHave('cancellation')
                ->whereDoesntHave('voidRecord')
                ->pluck('seat')
                ->values()
                ->all();
            $ticket->seats = $remainingSeats;
            $ticket->ticket_count = count($remainingSeats);
            if (!$remainingSeats) {
                $ticket->status = Status::BOOKED_VOIDED;
            }
            $ticket->save();

            return $ticketVoid;
        });

        return response()->json([
            'success' => true,
            'message' => 'Ticket voided successfully. The full fare was recorded as returned and the seat was released.',
            'redirect_url' => route('admin.vehicle.ticket.voided'),
            'void_id' => $ticketVoid->id,
        ]);
    }

    public function voidDetails($id)
    {
        $ticketVoid = TicketVoid::with([
            'slipSeriesNumber',
            'bookedTicket.trip.route',
            'bookedTicket.trip.schedule',
            'bookedTicket.trip.fleetType',
            'bookedTicket.pickup',
            'bookedTicket.drop',
            'bookedTicket.user',
            'bookedTicket.kiosk',
            'bookedTicket.deposit.userDiscount',
            'processedBy',
            'authorizedBy',
        ])->findOrFail($id);
        $ticket = $ticketVoid->bookedTicket;
        $passenger = $this->ticketPassenger($ticket, $ticketVoid->slipSeriesNumber);
        $snapshot = $ticketVoid->transaction_snapshot ?: [];

        return response()->json([
            'pnr' => $snapshot['pnr'] ?? $ticket->pnr_number,
            'reference' => (string) ($snapshot['reference'] ?? $ticketVoid->slip_series_number_id),
            'transaction' => $snapshot['transaction'] ?? ($ticket->deposit?->trx ?: '-'),
            'trip_route' => $snapshot['trip_route'] ?? ($ticket->pickup?->name . ' - ' . $ticket->drop?->name),
            'route_name' => $snapshot['route_name'] ?? ($ticket->trip?->route?->name ?: '-'),
            'bus_type' => $snapshot['bus_type'] ?? ($ticket->trip?->fleetType?->name ?: '-'),
            'date_of_journey' => $snapshot['date_of_journey'] ?? showDateTime($ticket->date_of_journey, 'M d, Y'),
            'departure_time' => $snapshot['departure_time'] ?? ($ticket->trip?->schedule?->start_from
                ? date('g:i A', strtotime($ticket->trip->schedule->start_from))
                : '-'),
            'seat' => $snapshot['seat'] ?? ($ticketVoid->slipSeriesNumber?->seat ?: '-'),
            'passenger_name' => $snapshot['passenger_name'] ?? $passenger['name'],
            'passenger_type' => $snapshot['passenger_type'] ?? $passenger['type'],
            'passenger_id' => $snapshot['passenger_id'] ?? ($passenger['id_number'] ?: '-'),
            'booking_source' => $snapshot['booking_source'] ?? ($ticket->kiosk_id ? ($ticket->kiosk?->name ?: 'Kiosk') : 'Online'),
            'payment_method' => $snapshot['payment_method'] ?? $this->paymentMethod($ticket),
            'fare' => (float) $ticketVoid->original_fare,
            'returned_amount' => (float) $ticketVoid->returned_amount,
            'ticket_count' => 1,
            'processed_by' => $snapshot['processed_by'] ?? ($ticketVoid->processedBy?->name ?: '-'),
            'authorized_by' => $snapshot['authorized_by'] ?? ($ticketVoid->authorizedBy?->name ?: '-'),
            'reason' => $ticketVoid->reason,
            'remarks' => $ticketVoid->remarks,
            'voided_at' => showDateTime($ticketVoid->created_at),
            'voided_ago' => diffForHumans($ticketVoid->created_at),
            'status' => 'Voided',
        ]);
    }

    private function refundableSlip($id)
    {
        return SlipSeriesNumber::whereDoesntHave('refund')
            ->whereDoesntHave('cancellation')
            ->whereDoesntHave('voidRecord')
            ->whereHas('bookedTicket', fn ($ticket) => $ticket->booked())
            ->with([
                'bookedTicket.user',
                'bookedTicket.deposit.userDiscount',
                'bookedTicket.slipSeriesNumbers',
            ])
            ->findOrFail($id);
    }

    private function cancellableSlip($id)
    {
        return SlipSeriesNumber::whereDoesntHave('refund')
            ->whereDoesntHave('cancellation')
            ->whereDoesntHave('voidRecord')
            ->whereHas('bookedTicket', fn ($ticket) => $ticket->booked())
            ->with([
                'bookedTicket.user',
                'bookedTicket.deposit.userDiscount',
                'bookedTicket.slipSeriesNumbers',
            ])
            ->findOrFail($id);
    }

    private function voidableSlip($id)
    {
        return SlipSeriesNumber::whereDoesntHave('refund')
            ->whereDoesntHave('cancellation')
            ->whereDoesntHave('voidRecord')
            ->whereHas('bookedTicket', fn ($ticket) => $ticket->booked())
            ->with([
                'bookedTicket.user',
                'bookedTicket.deposit.userDiscount',
                'bookedTicket.slipSeriesNumbers',
            ])
            ->findOrFail($id);
    }

    private function voidReasons(): array
    {
        return [
            'Passenger no-show',
            'Change of plans',
            'Duplicate booking',
            'Wrong trip / seat',
            'Trip cancelled',
            'Medical / emergency',
        ];
    }

    private function ticketPassenger(BookedTicket $ticket, SlipSeriesNumber $slip): array
    {
        $manifest = collect($ticket->passenger_manifest ?: ($ticket->deposit?->userDiscount?->passenger_manifest ?: []));
        $passenger = $manifest->first(fn ($item) => (string) ($item['seat'] ?? '') === (string) $slip->seat) ?: [];
        $discounted = ($passenger['passenger_type'] ?? 'regular') === 'discounted';

        return [
            'name' => ($passenger['name'] ?? null) ?: ($ticket->user?->fullname ?: 'Guest'),
            'type' => $passenger
                ? ($discounted ? ($passenger['discount_name'] ?? 'Discounted') : 'Regular')
                : getPassengerType($ticket->deposit),
            'id_number' => $passenger['id_number'] ?? null,
            'fare' => round((float) ($passenger['fare'] ?? $this->ticketFare($ticket)), 2),
        ];
    }

    private function paymentMethod(BookedTicket $ticket): string
    {
        if (!$ticket->deposit) {
            return '-';
        }

        if ($ticket->deposit->pchannel) {
            return readPaymentChannel($ticket->deposit->pchannel);
        }

        return $ticket->deposit->gatewayCurrency()?->name ?: '-';
    }

    private function voidSnapshot(BookedTicket $ticket, SlipSeriesNumber $slip, array $passenger, Admin $authorizedBy): array
    {
        $ticket->loadMissing(['trip.route', 'trip.schedule', 'trip.fleetType', 'pickup', 'drop', 'kiosk', 'deposit']);

        return [
            'booked_ticket_id' => $ticket->id,
            'slip_series_number_id' => $slip->id,
            'trip_id' => $ticket->trip_id,
            'pickup_point_id' => $ticket->pickup_point,
            'dropping_point_id' => $ticket->dropping_point,
            'user_id' => $ticket->user_id,
            'kiosk_id' => $ticket->kiosk_id,
            'deposit_id' => $ticket->deposit?->id,
            'pnr' => $ticket->pnr_number,
            'reference' => (string) $slip->id,
            'transaction' => $ticket->deposit?->trx ?: '-',
            'route_name' => $ticket->trip?->route?->name ?: '-',
            'trip_route' => $ticket->pickup?->name . ' - ' . $ticket->drop?->name,
            'bus_type' => $ticket->trip?->fleetType?->name ?: '-',
            'date_of_journey' => showDateTime($ticket->date_of_journey, 'M d, Y'),
            'date_of_journey_raw' => Carbon::parse($ticket->date_of_journey)->format('Y-m-d'),
            'departure_time' => $ticket->trip?->schedule?->start_from
                ? date('g:i A', strtotime($ticket->trip->schedule->start_from))
                : '-',
            'seat' => $slip->seat,
            'passenger_name' => $passenger['name'],
            'passenger_type' => $passenger['type'],
            'passenger_id' => $passenger['id_number'] ?: '-',
            'booking_source' => $ticket->kiosk_id ? ($ticket->kiosk?->name ?: 'Kiosk') : 'Online',
            'booking_source_reference' => $ticket->kiosk_id ? $ticket->kiosk?->uid : $ticket->user?->username,
            'payment_method' => $this->paymentMethod($ticket),
            'fare' => $passenger['fare'],
            'processed_by' => auth('admin')->user()->name,
            'authorized_by' => $authorizedBy->name,
        ];
    }

    private function ticketFare(BookedTicket $ticket): float
    {
        $ticketCount = max($ticket->slipSeriesNumbers->count(), 1);
        $total = (float) ($ticket->deposit?->final_amount ?? 0);
        if ($total <= 0) {
            $total = (float) $ticket->sub_total;
        }
        if ($total <= 0) {
            $total = (float) $ticket->unit_price * $ticketCount;
        }

        return round($total / $ticketCount, 2);
    }

    public function pending()
    {
        $pageTitle = 'Pending Ticket';
        $tickets = BookedTicket::pending()->with(['trip', 'pickup', 'drop', 'user'])->paginate(getPaginate());
        return view('admin.ticket.log', compact('pageTitle', 'tickets'));
    }

    public function rejected()
    {
        $pageTitle = 'Rejected Ticket';
        $tickets = BookedTicket::rejected()->with(['trip', 'pickup', 'drop', 'user'])->paginate(getPaginate());
        return view('admin.ticket.log', compact('pageTitle', 'tickets'));
    }

    public function list()
    {
        $pageTitle = 'All Ticket';
        $tickets = BookedTicket::with(['trip', 'pickup', 'drop', 'user'])->paginate(getPaginate());
        return view('admin.ticket.log', compact('pageTitle', 'tickets'));
    }

    public function search(Request $request, $scope)
    {
        $search = $request->search;
        $pageTitle = '';

        $ticket = BookedTicket::where('pnr_number', $search);
        switch ($scope) {
            case 'pending':
                $pageTitle .= 'Pending Ticket Search';
                break;
            case 'booked':
                $pageTitle .= 'Booked Ticket Search';
                break;
            case 'rejected':
                $pageTitle .= 'Rejected Ticket Search';
                break;
            case 'list':
                $pageTitle .= 'Ticket Booking History Search';
                break;
        }
        if ($scope === 'booked') {
            $tickets = $this->bookedTicketRows($search)->paginate(getPaginate())->withQueryString();
            $ticketRows = true;
        } else {
            $tickets = $ticket->with(['trip', 'pickup', 'drop', 'user'])->paginate(getPaginate())->withQueryString();
            $ticketRows = false;
        }
        $pageTitle .= ' - ' . $search;

        return view('admin.ticket.log', compact('pageTitle', 'search', 'scope', 'tickets', 'ticketRows'));
    }

    private function bookedTicketRows(?string $search = null)
    {
        return SlipSeriesNumber::query()
            ->whereDoesntHave('refund')
            ->whereDoesntHave('cancellation')
            ->whereDoesntHave('voidRecord')
            ->whereHas('bookedTicket', function ($query) {
                $query->booked();
            })
            ->when($search !== null && trim($search) !== '', function ($query) use ($search) {
                $term = trim($search);
                $query->where(function ($rowQuery) use ($term) {
                    $rowQuery->where('id', 'like', "%{$term}%")
                        ->orWhereHas('bookedTicket', function ($ticketQuery) use ($term) {
                            $ticketQuery->where('pnr_number', 'like', "%{$term}%")
                                ->orWhere('series_number', 'like', "%{$term}%")
                                ->orWhereHas('deposit.userDiscount', function ($discountQuery) use ($term) {
                                    $discountQuery->where('passenger_name', 'like', "%{$term}%");
                                });
                        });
                });
            })
            ->with([
                'bookedTicket.trip.schedule',
                'bookedTicket.trip.fleetType',
                'bookedTicket.pickup',
                'bookedTicket.drop',
                'bookedTicket.user',
                'bookedTicket.kiosk',
                'bookedTicket.deposit.userDiscount',
                'bookedTicket.slipSeriesNumbers',
                'bookedTicket.approvedBy',
            ])
            ->orderByDesc('booked_ticket_id')
            ->orderBy('id');
    }

    public function ticketPriceList()
    {
        $pageTitle = "All Ticket Price";
        $fleetTypes = FleetType::active()->get();
        $routes = VehicleRoute::active()->get();
        $query = TicketPrice::with(['fleetType', 'route.startFrom']);

        $searchTerm = request('search');

        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {

                // Search route start location
                $q->whereHas('route.startFrom', function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%");
                });

                // OPTIONAL: search route end location
                $q->orWhereHas('route.endTo', function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%");
                });

                // OPTIONAL: search fleet type
                $q->orWhereHas('fleetType', function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%");
                });

            });
        }

        $prices = $query
            ->orderByDesc('id')
            ->paginate(getPaginate());

        return view('admin.trip.ticket.price_list', compact('pageTitle', 'prices', 'fleetTypes', 'routes'));
    }

   public function ticketPriceForm($id = null)
{
    $pageTitle = $id ? "Update Ticket Price Configuration" : "Ticket Price Configuration";
    
    $fleetTypes = FleetType::active()->get();
    
    // Fetch routes and ensure stoppages are available for JavaScript
    $routes = VehicleRoute::active()->get();
    
    // Fetch all counters to map stoppage IDs to Names and KM Posts in the frontend
    $counters = Counter::active()->get(); 
    
    // If an ID is passed, fetch the existing ticket price and its relationships
    $ticketPrice = null;
    $existingPrices = [];
    if ($id) {
        $ticketPrice = TicketPrice::findOrFail($id);
        
        // Map existing prices into a key-value pair: ['start-end' => price] for easy JS lookup
        $prices = TicketPriceByStoppage::where('ticket_price_id', $id)->get();
        foreach ($prices as $p) {
            $key = $p->source_destination[0] . '-' . $p->source_destination[1];
            $existingPrices[$key] = $p->price;
        }
    }

    return view('admin.trip.ticket.price_form', compact(
        'pageTitle', 
        'fleetTypes', 
        'routes', 
        'counters', 
        'ticketPrice',
        'existingPrices'
    ));
}

    public function ticketPriceEdit($id)
    {
        $pageTitle = "Update Ticket Price";
        $ticketPrice = TicketPrice::with(['prices', 'route.startFrom', 'route.endTo'])->findOrFail($id);
        $stoppageArr = $ticketPrice->route->stoppages;
        $stoppages = stoppageCombination($stoppageArr, 2);
        return view('admin.trip.ticket.edit_price', compact('pageTitle', 'ticketPrice', 'stoppages'));
    }

    public function getRouteData(Request $request)
    {
        $route = VehicleRoute::active()->where('id', $request->vehicle_route_id)->first();
        $check = TicketPrice::where('vehicle_route_id', $request->vehicle_route_id)->where('fleet_type_id', $request->fleet_type_id)->first();

        if ($check) {
            return response()->json(['error' => trans('You have added prices for this fleet type on this route')]);
        }

        $stoppages = array_values($route->stoppages);
        $stoppages = stoppageCombination($stoppages, 2);
        return view('admin.trip.ticket.route_data', compact('stoppages', 'route'));
    }



    public function ticketPriceStore(Request $request)
    {
        $request->validate([
            'fleet_type' => 'required|integer|gt:0',
            'route'      => 'required|integer|gt:0',
            'main_price' => 'required|numeric|min:0',
            'price'      => 'required|array|min:1',
            'price.*'    => 'required|numeric|min:0',
        ], [
            'main_price.required' => 'Price for Source to Destination is required.',
            'price.*.required'    => 'All Stoppage Ticket Prices are required.',
            'price.*.numeric'     => 'All Stoppage Ticket Prices must be a valid number.',
        ]);

        // Duplicate Check
        $check = TicketPrice::where('fleet_type_id', $request->fleet_type)
                            ->where('vehicle_route_id', $request->route)
                            ->exists();
                            
        if ($check) {
            $notify[] = ['error', 'Ticket price for this Bus Type and Route already exists.'];
            return back()->withNotify($notify)->withInput();
        }

        // 1. Create Main Ticket Price
        $ticketPrice = new TicketPrice();
        $ticketPrice->fleet_type_id = $request->fleet_type;
        $ticketPrice->vehicle_route_id = $request->route;
        $ticketPrice->price = $request->main_price;
        $ticketPrice->save();

        // 2. Loop through dynamic table and create Stoppage Prices
        foreach ($request->price as $key => $val) {
            $idArray = explode('-', $key);
            
            $priceByStoppage = new TicketPriceByStoppage();
            $priceByStoppage->ticket_price_id = $ticketPrice->id;
            // Ensure IDs are strictly stored as strings in the JSON array to match your schema
            $priceByStoppage->source_destination = [(string)$idArray[0], (string)$idArray[1]]; 
            $priceByStoppage->price = $val;
            $priceByStoppage->save();
        }

        $notify[] = ['success', 'Ticket price configured successfully'];
        return back()->withNotify($notify);
    }

    public function ticketPriceUpdate(Request $request, $id)
    {
        $request->validate([
            'fleet_type' => 'required|integer|gt:0',
            'route'      => 'required|integer|gt:0',
            'main_price' => 'required|numeric|min:0',
            'price'      => 'required|array|min:1',
            'price.*'    => 'required|numeric|min:0',
        ], [
            'main_price.required' => 'Price for Source to Destination is required.',
            'price.*.required'    => 'All Stoppage Ticket Prices are required.',
            'price.*.numeric'     => 'All Stoppage Ticket Prices must be a valid number.',
        ]);

        $ticketPrice = TicketPrice::findOrFail($id);

        // Duplicate Check (Must exclude the current ticket price ID)
        $check = TicketPrice::where('fleet_type_id', $request->fleet_type)
                            ->where('vehicle_route_id', $request->route)
                            ->where('id', '!=', $id)
                            ->exists();
                            
        if ($check) {
            $notify[] = ['error', 'Ticket price for this Bus Type and Route already exists.'];
            return back()->withNotify($notify)->withInput();
        }

        // 1. Update Main Ticket Price
        $ticketPrice->fleet_type_id = $request->fleet_type;
        $ticketPrice->vehicle_route_id = $request->route;
        $ticketPrice->price = $request->main_price; // Now correctly takes the mirrored destination price
        $ticketPrice->save();

        // 2. Sync Stoppage Prices (Fixes the "Ghost Record" & "0 Price" bugs)
        // By wiping the old records and replacing them, we ensure perfect synchronization 
        // in case the Admin previously removed an intermediate stop from the Route.
        TicketPriceByStoppage::where('ticket_price_id', $ticketPrice->id)->delete();

        foreach ($request->price as $key => $val) {
            $idArray = explode('-', $key);
            
            $priceByStoppage = new TicketPriceByStoppage();
            $priceByStoppage->ticket_price_id = $ticketPrice->id;
            $priceByStoppage->source_destination = [(string)$idArray[0], (string)$idArray[1]];
            $priceByStoppage->price = $val; // Now safely extracts the numeric value out of the array
            $priceByStoppage->save();
        }

        $notify[] = ['success', 'Ticket price updated successfully'];
        return back()->withNotify($notify);
    }

    public function ticketPriceDelete($id)
    {

        $data = TicketPrice::findOrFail($id);
        $data->prices()->delete();
        $data->delete();

        $notify[] = ['success', 'Price Deleted Successfully'];
        return redirect()->back()->withNotify($notify);
    }

    public function updateBookingDate(Request $request, $id)
    {

        $admin = Admin::where('username', $request->username)
            ->where('passcode', $request->passcode)
            ->first();

        $is_authorized = isset($admin->id) ? true : false;
        $message = $is_authorized ? 'Authorization success!' : 'Invalid username or passcode!';

        if ($is_authorized) {
            $request->validate([
                'date_of_journey' => 'required|date|after_or_equal:today',
            ]);
        } else {
            return redirect()->back()->withErrors(['authorization' => $message]);
        }
        $request->validate([
            'date_of_journey' => 'required|date|after_or_equal:today',
            'seats' => 'required|string', // Comma-separated string from JS hidden input
        ]);

        $data = BookedTicket::with([
            'trip' => function ($q) {
                $q->with('schedule');
            }
        ])->findOrFail($id);

        $requestedSeats = explode(',', $request->seats);

        $originalSeatCount = is_array($data->seats) ? count($data->seats) : 1;

        if (count($requestedSeats) !== $originalSeatCount) {
            return redirect()->back()->withErrors(['seats' => "You must select exactly {$originalSeatCount} seat(s)."]);
        }

        // B. Fetch already booked seats for the new date and same schedule
        $bookedTicketsData = BookedTicket::query()
            ->where('id', '!=', $id) // Exclude current ticket to avoid self-conflict
            ->whereIn('status', [Status::BOOKED_APPROVED, Status::BOOKED_PENDING])
            ->whereDate('date_of_journey', Carbon::parse($request->date_of_journey)->format('Y-m-d'))
            ->whereHas('trip', function ($query) use ($data) {
                $query->where('fleet_type_id', $data->trip->fleet_type_id)
                    ->where('start_from', $data->trip->start_from);

                $query->whereHas('schedule', function ($q) use ($data) {
                    $q->where('start_from', $data->trip->schedule->start_from);
                });
            })
            ->get(['seats']);

        // Flatten all booked seats into a single array
        $bookedSeatsArray = [];
        foreach ($bookedTicketsData as $bookedTicket) {
            $seats = is_string($bookedTicket->seats) ? json_decode($bookedTicket->seats, true) : $bookedTicket->seats;
            if (is_array($seats)) {
                foreach ($seats as $seat) {
                    if (str_contains($seat, '-')) {
                        $seat_parts = explode('-', $seat);
                        $bookedSeatsArray[] = $seat_parts[1];
                    } else {
                        $bookedSeatsArray[] = $seat;
                    }
                }
            }
        }
        $bookedSeatsArray = array_unique($bookedSeatsArray);

        // C. Fetch permanently disabled seats for this fleet
        $fleetType = FleetType::find($data->trip->fleet_type_id);
        $disabledSeats = $fleetType->disabled_seats ? $fleetType->disabled_seats : [];

        // Combine booked and disabled seats to check against
        $unavailableSeats = array_merge($bookedSeatsArray, $disabledSeats);

        // D. Check for overlaps (if any requested seat is inside the unavailable array)
        $conflict = array_intersect($requestedSeats, $unavailableSeats);
        if (!empty($conflict)) {
            $conflictStr = implode(', ', $conflict);
            return redirect()->back()->withErrors(['seats' => "The following seats are already booked or unavailable on this date: {$conflictStr}"]);
        }
        // ----------------------------------------

        // 4. Save the update
        $data->date_of_journey = $request->date_of_journey;
        $data->is_rebooked = 1;
        $data->seats = $requestedSeats;
        $data->save();

        $slips = $data->activeSlipSeriesNumbers;

        foreach ($slips as $key => $slip) {
            if (isset($requestedSeats[$key])) {
                $slip->seat = $requestedSeats[$key];
                $slip->save();
            }
        }

        $notify[] = ['success', "Booking Date and Seats Updated Successfully"];
        return redirect()->back()->withNotify($notify);
    }

    public function rebookingOptions(Request $request, $id)
    {
        $ticket = $this->rebookingTicket($id);
        $slipId = $request->integer('slip_id') ?: null;
        $targetSlips = $this->rebookingSlips($ticket, $slipId);
        $routeParams = $slipId ? [$ticket->id, 'slip_id' => $slipId] : [$ticket->id];

        $trips = Trip::active()
            ->where('id', '!=', $ticket->trip_id)
            ->with(['route', 'schedule', 'fleetType'])
            ->get()
            ->filter(function ($trip) use ($ticket) {
                $fare = $this->fareForTrip($trip, $ticket);

                return $this->tripSupportsBooking($trip, $ticket)
                    && $fare !== null
                    && abs($fare - (float) $ticket->unit_price) < 0.01;
            })
            ->map(fn ($trip) => $this->tripOption($trip, $ticket))
            ->values();

        return response()->json([
            'booking' => $this->bookingSummary($ticket, null, null, $targetSlips),
            'trips' => $trips,
            'max_date' => now()->addDays(getAllowedAdvanceBookingDays(true))->format('Y-m-d'),
            'availability_url' => route('admin.trip.ticket.rebook.availability', $routeParams),
            'confirm_url' => route('admin.trip.ticket.rebook.confirm', $routeParams),
        ]);
    }

    public function rebookingAvailability(Request $request, $id)
    {
        $validated = $request->validate([
            'type' => 'required|in:change_date,new_trip,change_seat',
            'date' => 'nullable|required_unless:type,change_seat|date|after_or_equal:today',
            'trip_id' => 'nullable|required_if:type,new_trip|integer',
        ]);

        $ticket = $this->rebookingTicket($id);
        $targetSlips = $this->rebookingSlips($ticket, $request->integer('slip_id') ?: null);
        [$trip, $date] = $this->resolveRebookingTarget($ticket, $validated);
        $availability = $this->seatAvailability($ticket, $trip, $date, false, $targetSlips);

        $fleetType = $trip->fleetType;
        $busLayout = new BusLayout($trip);
        $html = view('templates.basic.partials.seat_layout', compact('fleetType', 'busLayout'))->render();

        return response()->json([
            'html' => $html,
            'booked_seats' => $availability['booked'],
            'disabled_seats' => $availability['disabled'],
            'required_seats' => $targetSlips->count(),
            'selected_seats' => $targetSlips->pluck('seat')->values(),
            'before' => $this->bookingSummary($ticket, null, null, $targetSlips),
            'after' => $this->bookingSummary($ticket, $trip, $date, $targetSlips),
        ]);
    }

    public function confirmRebooking(Request $request, $id)
    {
        $validated = $request->validate([
            'type' => 'required|in:change_date,new_trip,change_seat',
            'date' => 'nullable|required_unless:type,change_seat|date|after_or_equal:today',
            'trip_id' => 'nullable|required_if:type,new_trip|integer',
            'seats' => 'required|array|min:1',
            'seats.*' => 'required|string|max:30',
        ]);
        $slipId = $request->integer('slip_id') ?: null;

        $result = DB::transaction(function () use ($id, $validated, $slipId) {
            $ticket = BookedTicket::booked()
                ->with(['trip.route', 'trip.schedule', 'trip.fleetType', 'pickup', 'drop', 'activeSlipSeriesNumbers', 'deposit.userDiscount'])
                ->lockForUpdate()
                ->findOrFail($id);
            $targetSlips = $this->rebookingSlips($ticket, $slipId);
            [$trip, $date] = $this->resolveRebookingTarget($ticket, $validated);

            $requestedSeats = array_values(array_unique($validated['seats']));
            $requiredSeats = $targetSlips->count();

            if (count($requestedSeats) !== $requiredSeats) {
                throw ValidationException::withMessages([
                    'seats' => "Select exactly {$requiredSeats} seat(s) for this ticket.",
                ]);
            }

            $originalSeats = $targetSlips->pluck('seat')->sort()->values()->all();
            $comparisonSeats = collect($requestedSeats)->sort()->values()->all();
            if ($validated['type'] === 'change_seat' && $comparisonSeats === $originalSeats) {
                throw ValidationException::withMessages([
                    'seats' => 'Select a different seat before confirming the seat change.',
                ]);
            }

            if ($validated['type'] === 'change_date'
                && $date === Carbon::parse($ticket->date_of_journey)->format('Y-m-d')) {
                throw ValidationException::withMessages([
                    'date' => 'Select a different travel date before confirming the date change.',
                ]);
            }

            $availability = $this->seatAvailability($ticket, $trip, $date, true, $targetSlips);
            $unavailable = array_merge($availability['booked'], $availability['disabled_full']);
            $conflicts = array_values(array_intersect($requestedSeats, $unavailable));

            if ($conflicts) {
                throw ValidationException::withMessages([
                    'seats' => 'These seats are no longer available: ' . implode(', ', $conflicts),
                ]);
            }

            return $this->applyRebooking($ticket, $targetSlips, $trip, $date, $requestedSeats);
        });

        return response()->json([
            'success' => true,
            'message' => 'Rebooking confirmed. The ticket remains paid, the PNR and reference number are unchanged, and no new voucher is required.',
            'print_url' => route('admin.trip.reservationSlip', $result->id),
        ]);
    }

    private function rebookingTicket($id)
    {
        return BookedTicket::booked()
            ->with([
                'trip.route',
                'trip.schedule',
                'trip.fleetType',
                'pickup',
                'drop',
                'slipSeriesNumbers',
                'activeSlipSeriesNumbers',
                'deposit.userDiscount',
                'user',
            ])
            ->findOrFail($id);
    }

    private function rebookingSlips(BookedTicket $ticket, ?int $slipId = null)
    {
        $slips = $ticket->activeSlipSeriesNumbers->values();

        if (!$slipId) {
            return $slips;
        }

        $slip = $slips->firstWhere('id', $slipId);

        if (!$slip) {
            throw ValidationException::withMessages([
                'slip_id' => 'The selected reference number is not active for this booking.',
            ]);
        }

        return collect([$slip]);
    }

    private function resolveRebookingTarget(BookedTicket $ticket, array $data): array
    {
        $type = $data['type'];
        $date = $type === 'change_seat'
            ? Carbon::parse($ticket->date_of_journey)->format('Y-m-d')
            : Carbon::parse($data['date'])->format('Y-m-d');
        $trip = $ticket->trip;

        if ($type === 'new_trip') {
            $trip = Trip::active()->with(['route', 'schedule', 'fleetType'])->findOrFail($data['trip_id']);
            $fare = $this->fareForTrip($trip, $ticket);

            if ($trip->id === $ticket->trip_id) {
                throw ValidationException::withMessages([
                    'trip_id' => 'Select a different trip for a New Trip rebooking.',
                ]);
            }

            if (!$this->tripSupportsBooking($trip, $ticket)) {
                throw ValidationException::withMessages([
                    'trip_id' => 'The selected trip does not contain the original pickup and drop-off stoppages in the correct order.',
                ]);
            }

            if ($fare === null || abs($fare - (float) $ticket->unit_price) >= 0.01) {
                throw ValidationException::withMessages([
                    'trip_id' => 'The selected trip fare must match the original booking fare.',
                ]);
            }
        }

        if (Carbon::parse($date)->isAfter(now()->addDays(getAllowedAdvanceBookingDays(true))->endOfDay())) {
            throw ValidationException::withMessages([
                'date' => 'The travel date exceeds the allowed advance-booking period.',
            ]);
        }

        $departure = Carbon::parse($date . ' ' . $trip->schedule->start_from);
        $cutoffMinutes = getBookingCutoffMinutes(true);
        if (now()->gte($departure->copy()->subMinutes($cutoffMinutes))) {
            throw ValidationException::withMessages([
                'date' => $cutoffMinutes === 0
                    ? 'Counter booking closes at departure time.'
                    : "Counter booking closes {$cutoffMinutes} minute(s) before departure.",
            ]);
        }

        $dayOff = array_map('intval', $trip->day_off ?? []);
        if (in_array((int) Carbon::parse($date)->format('w'), $dayOff, true)) {
            throw ValidationException::withMessages([
                'date' => 'The selected trip is not available on ' . Carbon::parse($date)->format('l') . '.',
            ]);
        }

        return [$trip, $date];
    }

    private function tripSupportsBooking(Trip $trip, BookedTicket $ticket): bool
    {
        $stoppages = array_map('strval', array_values($trip->route->stoppages ?? []));
        $start = array_search((string) $trip->start_from, $stoppages, true);
        $end = array_search((string) $trip->end_to, $stoppages, true);
        $pickup = array_search((string) $ticket->pickup_point, $stoppages, true);
        $drop = array_search((string) $ticket->dropping_point, $stoppages, true);

        if ($start === false || $end === false || $pickup === false || $drop === false) {
            return false;
        }

        if ($start < $end) {
            return $pickup >= $start && $drop <= $end && $pickup < $drop;
        }

        return $pickup <= $start && $drop >= $end && $pickup > $drop;
    }

    private function fareForTrip(Trip $trip, BookedTicket $ticket): ?float
    {
        $price = TicketPrice::where('fleet_type_id', $trip->fleet_type_id)
            ->where('vehicle_route_id', $trip->vehicle_route_id)
            ->with('prices')
            ->first();

        if (!$price) {
            return null;
        }

        $segment = [(string) $ticket->pickup_point, (string) $ticket->dropping_point];

        foreach ($price->prices as $segmentPrice) {
            $pricedSegment = array_map('strval', $segmentPrice->source_destination ?? []);
            if ($pricedSegment === $segment || $pricedSegment === array_reverse($segment)) {
                return (float) $segmentPrice->price;
            }
        }

        return null;
    }

    private function applyRebooking(BookedTicket $ticket, $targetSlips, Trip $trip, string $date, array $requestedSeats): BookedTicket
    {
        $targetSlips = collect($targetSlips)->values();
        $allActiveSlips = $ticket->activeSlipSeriesNumbers->values();
        $originalTicketDate = Carbon::parse($ticket->date_of_journey)->format('Y-m-d');
        $isPartial = $targetSlips->count() < $allActiveSlips->count();
        $movesTripOrDate = (int) $trip->id !== (int) $ticket->trip_id || $date !== $originalTicketDate;
        $oldSeats = $targetSlips->pluck('seat')->values()->all();

        if ($isPartial && $movesTripOrDate) {
            $newTicket = $ticket->replicate();
            $newTicket->trip_id = $trip->id;
            $newTicket->date_of_journey = $date;
            $newTicket->seats = $requestedSeats;
            $newTicket->ticket_count = count($requestedSeats);
            $newTicket->sub_total = count($requestedSeats) * (float) $ticket->unit_price;
            $newTicket->passenger_manifest = null;
            $newTicket->is_rebooked = 1;
            $newTicket->save();

            foreach ($targetSlips as $index => $slip) {
                $slip->booked_ticket_id = $newTicket->id;
                $slip->seat = $requestedSeats[$index];
                $slip->save();
            }

            $this->movePassengerManifest($ticket, $newTicket, $oldSeats, $requestedSeats);
            $this->splitDepositForRebooking($ticket, $newTicket, $oldSeats, $requestedSeats, $allActiveSlips->count());

            $ticket->is_rebooked = 1;
            $this->syncTicketSeats($ticket);

            return $newTicket->load(['trip.route', 'trip.schedule', 'trip.fleetType', 'pickup', 'drop', 'activeSlipSeriesNumbers', 'deposit.userDiscount']);
        }

        foreach ($targetSlips as $index => $slip) {
            $slip->seat = $requestedSeats[$index];
            $slip->save();
        }

        $ticket->trip_id = $trip->id;
        $ticket->date_of_journey = $date;
        $ticket->is_rebooked = 1;
        $this->replacePassengerManifestSeats($ticket, $oldSeats, $requestedSeats);
        $this->syncTicketSeats($ticket);

        return $ticket->fresh(['trip.route', 'trip.schedule', 'trip.fleetType', 'pickup', 'drop', 'activeSlipSeriesNumbers', 'deposit.userDiscount']);
    }

    private function syncTicketSeats(BookedTicket $ticket): void
    {
        $seats = $ticket->slipSeriesNumbers()
            ->whereDoesntHave('refund')
            ->whereDoesntHave('cancellation')
            ->whereDoesntHave('voidRecord')
            ->pluck('seat')
            ->values()
            ->all();

        $ticket->seats = $seats;
        $ticket->ticket_count = count($seats);
        $ticket->sub_total = count($seats) * (float) $ticket->unit_price;
        $ticket->save();
    }

    private function movePassengerManifest(BookedTicket $sourceTicket, BookedTicket $targetTicket, array $oldSeats, array $newSeats): void
    {
        $manifest = collect($sourceTicket->passenger_manifest ?? []);

        if ($manifest->isEmpty()) {
            return;
        }

        [$moving, $remaining] = $this->partitionManifestBySeats($manifest, $oldSeats, $newSeats);
        $sourceTicket->passenger_manifest = $remaining ?: null;
        $sourceTicket->save();
        $targetTicket->passenger_manifest = $moving ?: null;
        $targetTicket->save();
    }

    private function replacePassengerManifestSeats(BookedTicket $ticket, array $oldSeats, array $newSeats): void
    {
        $manifest = collect($ticket->passenger_manifest ?? []);

        if ($manifest->isEmpty()) {
            return;
        }

        [$updated, $unchanged] = $this->partitionManifestBySeats($manifest, $oldSeats, $newSeats);
        $ticket->passenger_manifest = array_values(array_merge($unchanged, $updated));
        $ticket->save();
    }

    private function partitionManifestBySeats($manifest, array $oldSeats, array $newSeats): array
    {
        $moving = [];
        $remaining = [];
        $seatLookup = array_flip($oldSeats);

        foreach ($manifest as $entry) {
            $entry = is_array($entry) ? $entry : (array) $entry;
            $seat = $entry['seat'] ?? null;

            if ($seat !== null && array_key_exists($seat, $seatLookup)) {
                $entry['seat'] = $newSeats[$seatLookup[$seat]] ?? $seat;
                $moving[] = $entry;
                continue;
            }

            $remaining[] = $entry;
        }

        return [$moving, $remaining];
    }

    private function splitDepositForRebooking(BookedTicket $sourceTicket, BookedTicket $targetTicket, array $oldSeats, array $newSeats, int $originalSeatCount): void
    {
        $deposit = $sourceTicket->deposit()->with('userDiscount')->lockForUpdate()->first();

        if (!$deposit || $originalSeatCount <= count($oldSeats)) {
            return;
        }

        $movingCount = count($oldSeats);
        $remainingCount = $originalSeatCount - $movingCount;
        $newDeposit = $deposit->replicate();
        $newDeposit->booked_ticket_id = $targetTicket->id;
        $newDeposit->trx = generateReqID('GVF-RB');
        $this->scaleMonetaryAttributes($newDeposit, $movingCount, $originalSeatCount);
        $newDeposit->save();

        $this->scaleMonetaryAttributes($deposit, $remainingCount, $originalSeatCount);
        $deposit->save();

        if (!$deposit->userDiscount) {
            return;
        }

        $discount = $deposit->userDiscount;
        $newDiscount = $discount->replicate();
        $newDiscount->deposit_id = $newDeposit->id;
        $newDiscount->amount = $this->proportionalAmount($discount->amount, $movingCount, $originalSeatCount);

        [$movingManifest, $remainingManifest] = $this->partitionManifestBySeats(
            collect($discount->passenger_manifest ?? []),
            $oldSeats,
            $newSeats
        );

        $newDiscount->passenger_manifest = $movingManifest ?: null;
        $newDiscount->save();

        $discount->amount = $this->proportionalAmount($discount->amount, $remainingCount, $originalSeatCount);
        $discount->passenger_manifest = $remainingManifest ?: null;
        $discount->save();
    }

    private function scaleMonetaryAttributes($model, int $count, int $total): void
    {
        foreach (['amount', 'charge', 'final_amount', 'method_amount'] as $field) {
            if (array_key_exists($field, $model->getAttributes()) && $model->{$field} !== null) {
                $model->{$field} = $this->proportionalAmount($model->{$field}, $count, $total);
            }
        }
    }

    private function proportionalAmount($amount, int $count, int $total): float
    {
        return round(((float) $amount * $count) / max($total, 1), 2);
    }

    private function seatAvailability(BookedTicket $ticket, Trip $trip, string $date, bool $lock = false, $targetSlips = null): array
    {
        $query = BookedTicket::whereIn('status', [Status::BOOKED_APPROVED, Status::BOOKED_PENDING])
            ->where('id', '!=', $ticket->id)
            ->where('trip_id', $trip->id)
            ->whereDate('date_of_journey', $date)
            ->with('activeSlipSeriesNumbers');

        if ($lock) {
            $query->lockForUpdate();
        }

        $booked = $query->get()
            ->flatMap(fn ($booking) => $booking->activeSlipSeriesNumbers->pluck('seat'))
            ->values();
        $targetSlipIds = collect($targetSlips ?: $ticket->activeSlipSeriesNumbers)->pluck('id')->all();
        $ticketDate = Carbon::parse($ticket->date_of_journey)->format('Y-m-d');

        if ((int) $trip->id === (int) $ticket->trip_id && $date === $ticketDate) {
            $booked = $booked->merge(
                $ticket->activeSlipSeriesNumbers
                    ->whereNotIn('id', $targetSlipIds)
                    ->pluck('seat')
            );
        }

        $booked = $booked->unique()->values()->all();
        $disabled = array_values((array) ($trip->fleetType->disabled_seats ?? []));
        $disabledFull = [];

        foreach ($trip->fleetType->deck_seats ?? [] as $deckIndex => $seatCount) {
            foreach ($disabled as $seat) {
                $disabledFull[] = ($deckIndex + 1) . '-' . $seat;
            }
        }

        return [
            'booked' => $booked,
            'disabled' => $disabled,
            'disabled_full' => $disabledFull,
        ];
    }

    private function bookingSummary(BookedTicket $ticket, ?Trip $trip = null, ?string $date = null, $slips = null): array
    {
        $trip ??= $ticket->trip;
        $date ??= Carbon::parse($ticket->date_of_journey)->format('Y-m-d');
        $slips = collect($slips ?: $ticket->activeSlipSeriesNumbers)->values();
        $selectedSeats = $slips->pluck('seat')->all();
        $passengers = collect($ticket->passenger_manifest ?? [])
            ->filter(fn ($passenger) => in_array($passenger['seat'] ?? null, $selectedSeats, true))
            ->values();
        $passengerName = $passengers->pluck('name')->filter()->implode(', ')
            ?: $ticket->deposit?->userDiscount?->passenger_name
            ?: $ticket->user?->fullname
            ?: 'Guest';
        $passengerType = $passengers->isNotEmpty()
            ? $passengers->map(function ($passenger) {
                return ($passenger['passenger_type'] ?? 'regular') === 'discounted'
                    ? ($passenger['discount_name'] ?? 'Discounted')
                    : 'Regular';
            })->unique()->implode(', ')
            : getPassengerType($ticket->deposit);

        return [
            'id' => $ticket->id,
            'pnr' => $ticket->pnr_number,
            'reference' => $slips->pluck('id')->implode(', '),
            'date' => $date,
            'date_display' => Carbon::parse($date)->format('Y-m-d'),
            'time' => Carbon::parse($trip->schedule->start_from)->format('g:i A'),
            'bus_type' => $trip->fleetType->name,
            'route' => $ticket->pickup->name . ' via ' . $ticket->drop->name,
            'seats' => $slips->pluck('seat')->values(),
            'trip_id' => $trip->id,
            'fare' => (float) $ticket->unit_price,
            'passenger_name' => $passengerName,
            'passenger_type' => $passengerType,
        ];
    }

    private function tripOption(Trip $trip, BookedTicket $ticket): array
    {
        return [
            'id' => $trip->id,
            'label' => $trip->fleetType->name . ' - ' . Carbon::parse($trip->schedule->start_from)->format('g:i A'),
            'route' => $ticket->pickup->name . ' via ' . $ticket->drop->name,
            'schedule' => Carbon::parse($trip->schedule->start_from)->format('g:i A'),
            'bus_type' => $trip->fleetType->name,
            'fare' => (float) $ticket->unit_price,
        ];
    }

    public function cancellationAcknowledgment($id)
    {
        $cancellation = TicketCancellation::with([
            'slipSeriesNumber',
            'bookedTicket.trip.schedule',
            'bookedTicket.trip.fleetType',
            'bookedTicket.pickup',
            'bookedTicket.drop',
            'bookedTicket.user',
            'bookedTicket.deposit.userDiscount',
            'processedBy',
            'authorizedBy',
        ])->findOrFail($id);

        $pdf = Pdf::setOptions([
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
        ])->loadView('admin.pdf.cancellation-acknowledgment', [
            'cancellation' => $cancellation,
            'pageTitle' => 'Cancellation Acknowledgment',
        ]);

        $pdf->setPaper([0, 0, 144, 500], 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Cancellation Acknowledgment.pdf"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function cancelBooking($id)
    {
        $notify[] = ['error', 'Legacy cancellation is disabled. Please cancel tickets through the authorized cancellation modal.'];
        return to_route('admin.vehicle.ticket.booked')->withNotify($notify);
    }

    public function getSeatLayout(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|integer',
            'date' => 'required|date'
        ]);

        // 1. Fetch the original ticket to get trip and schedule parameters
        $ticket = BookedTicket::with([
            'trip' => function ($q) {
                $q->with('schedule');
            }
        ])->findOrFail($request->ticket_id);

        // How many seats does the passenger need to rebook?
        $requiredSeatsCount = is_array($ticket->seats) ? count($ticket->seats) : 1;

        // 2. Run your existing checker for the NEW date
        $bookedTicketsData = BookedTicket::whereIn('status', [Status::BOOKED_APPROVED, Status::BOOKED_PENDING])
            ->where('id', '!=', $request->ticket_id)
            ->whereDate('date_of_journey', Carbon::parse($request->date)->format('Y-m-d'))
            ->where('trip_id', $ticket->trip_id)
            ->get(['seats']);

        // 3. Extract and flatten all booked seat numbers into a single 1D array
        $bookedSeatsArray = [];
        foreach ($bookedTicketsData as $bookedTicket) {
            $seats = is_string($bookedTicket->seats) ? json_decode($bookedTicket->seats, true) : $bookedTicket->seats;
            if (is_array($seats)) {
                $bookedSeatsArray = array_merge($bookedSeatsArray, $seats);
            }
        }

        // 4. Fetch dependencies for the Blade partial
        $fleetType = FleetType::findOrFail($ticket->trip->fleet_type_id);
        // Instantiate your BusLayout service here so the blade template can use it
        $trip = Trip::with(['fleetType', 'route', 'schedule', 'startFrom', 'endTo', 'assignedVehicle.vehicle', 'bookedTickets'])
            ->where('status', Status::ENABLE)
            ->where('id', $ticket->trip_id)
            ->firstOrFail();

        $busLayout = new BusLayout($trip); // Adjust namespace based on your app

        // 5. Render the HTML view
        $html = view('templates.basic.partials.seat_layout', compact('fleetType', 'busLayout'))->render();

        $disabled_seats = $fleetType->disabled_seats ? $fleetType->disabled_seats : [];
        $seats = [];

        foreach ($bookedSeatsArray as $seat) {
            if (str_contains($seat, '-')) {
                $seat_parts = explode('-', $seat);
                $seats[] = $seat_parts[1];
            } else {
                $seats[] = $seat; // Fallback just in case
            }
        }

        return response()->json([
            'status' => 'success',
            'html' => $html,
            'booked_seats' => array_unique($seats),
            'disabled_seats' => $disabled_seats,
            'required_seats' => $requiredSeatsCount
        ]);
    }

    public function checkTicketPrice(Request $request)
    {
        $check = TicketPrice::where('vehicle_route_id', $request->vehicle_route_id)->where('fleet_type_id', $request->fleet_type_id)->first();

        if (!$check) {
            return response()->json(['error' => 'Ticket price not added for this fleet-route combination yet. Please add ticket price before creating a trip.']);
        }
    }
}
