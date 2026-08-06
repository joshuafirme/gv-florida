<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\Admin;
use App\Models\BookedTicket;
use App\Models\CashierTransactionEvent;
use App\Models\Deposit;
use App\Models\SlipSeriesNumber;
use App\Models\TicketCancellation;
use App\Models\TicketRefund;
use App\Models\TicketVoid;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CashierTransactionRecorder
{
    public function __construct(
        private readonly TicketPassengerResolver $passengerResolver
    ) {
    }

    public function recordSold(Deposit $deposit): void
    {
        if ((int) $deposit->status !== Status::PAYMENT_SUCCESS) {
            return;
        }

        $deposit->loadMissing($this->depositRelations());
        $ticket = $deposit->bookedTicket;
        if (!$ticket) {
            return;
        }

        foreach ($ticket->slipSeriesNumbers as $slip) {
            $snapshot = $this->ticketSnapshot($ticket, $slip);
            $this->store(
                "sold:{$deposit->id}:{$slip->id}",
                $deposit->processed_by_admin_id ? (int) $deposit->processed_by_admin_id : null,
                'Sold',
                $snapshot,
                (float) $snapshot['fare'] + (float) $snapshot['surcharge_amount'],
                null,
                $deposit->updated_at ?: now()
            );
        }
    }

    public function recordRefund(TicketRefund $refund): void
    {
        $refund->loadMissing($this->actionRelations());
        $snapshot = $this->ticketSnapshot($refund->bookedTicket, $refund->slipSeriesNumber);

        $this->store(
            "refunded:{$refund->id}",
            (int) $refund->processed_by_admin_id,
            'Refunded',
            $snapshot,
            -(float) $refund->refund_amount,
            $refund->reason,
            $refund->created_at ?: now()
        );
    }

    public function recordCancellation(TicketCancellation $cancellation): void
    {
        $cancellation->loadMissing($this->actionRelations());
        $snapshot = $this->ticketSnapshot($cancellation->bookedTicket, $cancellation->slipSeriesNumber);

        $this->store(
            "cancelled:{$cancellation->id}",
            (int) $cancellation->processed_by_admin_id,
            'Cancelled',
            $snapshot,
            0,
            $cancellation->reason,
            $cancellation->created_at ?: now()
        );
    }

    public function recordVoid(TicketVoid $ticketVoid): void
    {
        $ticketVoid->loadMissing($this->actionRelations());
        $snapshot = $this->ticketSnapshot($ticketVoid->bookedTicket, $ticketVoid->slipSeriesNumber);
        $audit = $ticketVoid->transaction_snapshot ?: [];

        $snapshot = array_merge($snapshot, array_filter([
            'source' => $audit['booking_source'] ?? null,
            'pnr' => $audit['pnr'] ?? null,
            'reference_no' => $audit['reference'] ?? null,
            'passenger_name' => $audit['passenger_name'] ?? null,
            'passenger_type' => $audit['passenger_type'] ?? null,
            'passenger_id' => $audit['passenger_id'] ?? null,
            'journey_date' => $audit['date_of_journey_raw'] ?? null,
            'departure_time' => $audit['departure_time'] ?? null,
            'trip_class' => $audit['bus_type'] ?? null,
            'trip_route' => $audit['route_name'] ?? null,
            'seat_no' => $audit['seat'] ?? null,
            'payment_method' => $audit['payment_method'] ?? null,
            'fare' => $audit['fare'] ?? null,
        ], fn ($value) => $value !== null && $value !== ''));

        $this->store(
            "voided:{$ticketVoid->id}",
            (int) $ticketVoid->processed_by_admin_id,
            'Voided',
            $snapshot,
            -(float) $ticketVoid->returned_amount,
            $ticketVoid->reason,
            $ticketVoid->created_at ?: now()
        );
    }

    public function recordRebooking(
        BookedTicket $ticket,
        Collection $slips,
        int $adminId,
        string $reason,
        string $batchKey,
        array $history = []
    ): void {
        $ticket->loadMissing($this->ticketRelations());
        $slips = $slips->values();

        if ($slips->isEmpty()) {
            $snapshot = $this->bookingSnapshot($ticket);
            $snapshot = $this->withRebookingHistory($snapshot, $history, null, 0);
            $this->store(
                "rebooked:{$batchKey}:booking",
                $adminId,
                'Rebooked',
                $snapshot,
                0,
                $reason,
                now()
            );

            return;
        }

        foreach ($slips as $index => $slip) {
            $snapshot = $this->ticketSnapshot($ticket, $slip);
            $snapshot = $this->withRebookingHistory($snapshot, $history, $slip, $index);
            $this->store(
                "rebooked:{$batchKey}:{$slip->id}",
                $adminId,
                'Rebooked',
                $snapshot,
                0,
                $reason,
                now()
            );
        }
    }

    private function bookingSnapshot(BookedTicket $ticket): array
    {
        $placeholder = new SlipSeriesNumber();
        $placeholder->seat = collect($ticket->seats ?? [])->first();

        return $this->ticketSnapshot($ticket, $placeholder);
    }

    private function withRebookingHistory(
        array $snapshot,
        array $history,
        ?SlipSeriesNumber $slip,
        int $index
    ): array {
        $sequenceQuery = CashierTransactionEvent::query()->where('status', 'Rebooked');
        if ($slip?->id) {
            $sequenceQuery->where('slip_series_number_id', $slip->id);
        } else {
            $sequenceQuery->where('booked_ticket_id', $snapshot['booked_ticket_id'] ?? null)
                ->whereNull('slip_series_number_id');
        }

        $sequence = $sequenceQuery->count() + 1;
        $reference = $slip?->id ? (string) $slip->id : null;
        $previousSeat = $reference
            ? ($history['previous']['seats_by_reference'][$reference] ?? null)
            : ($history['previous']['seats'][$index] ?? null);
        $newSeat = $reference
            ? ($history['new']['seats_by_reference'][$reference] ?? $slip?->seat)
            : ($history['new']['seats'][$index] ?? $snapshot['seat_no'] ?? null);

        $snapshot['rebooking'] = array_merge($history, [
            'sequence' => $sequence,
            'previous_seat' => $previousSeat,
            'new_seat' => $newSeat,
        ]);
        $snapshot['rebooking_sequence'] = $sequence;

        return $snapshot;
    }

    public function backfillForDate(Admin $admin, Carbon $date): void
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        Deposit::successful()
            ->where('processed_by_admin_id', $admin->id)
            ->whereBetween('updated_at', [$start, $end])
            ->with($this->depositRelations())
            ->get()
            ->each(fn ($deposit) => $this->safely(fn () => $this->recordSold($deposit)));

        TicketRefund::where('processed_by_admin_id', $admin->id)
            ->whereBetween('created_at', [$start, $end])
            ->with($this->actionRelations())
            ->get()
            ->each(fn ($refund) => $this->safely(fn () => $this->recordRefund($refund)));

        TicketCancellation::where('processed_by_admin_id', $admin->id)
            ->whereBetween('created_at', [$start, $end])
            ->with($this->actionRelations())
            ->get()
            ->each(fn ($cancellation) => $this->safely(fn () => $this->recordCancellation($cancellation)));

        TicketVoid::where('processed_by_admin_id', $admin->id)
            ->whereBetween('created_at', [$start, $end])
            ->with($this->actionRelations())
            ->get()
            ->each(fn ($ticketVoid) => $this->safely(fn () => $this->recordVoid($ticketVoid)));
    }

    public function backfillAllForDate(Carbon $date): void
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        // Kiosk and online payments can complete without a cashier account. Record
        // them before the cashier-specific action backfill below.
        Deposit::successful()
            ->whereBetween('updated_at', [$start, $end])
            ->with($this->depositRelations())
            ->get()
            ->each(fn ($deposit) => $this->safely(fn () => $this->recordSold($deposit)));

        $adminIds = collect()
            ->merge(
                Deposit::successful()
                    ->whereNotNull('processed_by_admin_id')
                    ->whereBetween('updated_at', [$start, $end])
                    ->pluck('processed_by_admin_id')
            )
            ->merge(
                TicketRefund::whereNotNull('processed_by_admin_id')
                    ->whereBetween('created_at', [$start, $end])
                    ->pluck('processed_by_admin_id')
            )
            ->merge(
                TicketCancellation::whereNotNull('processed_by_admin_id')
                    ->whereBetween('created_at', [$start, $end])
                    ->pluck('processed_by_admin_id')
            )
            ->merge(
                TicketVoid::whereNotNull('processed_by_admin_id')
                    ->whereBetween('created_at', [$start, $end])
                    ->pluck('processed_by_admin_id')
            )
            ->merge(
                CashierTransactionEvent::whereBetween('processed_at', [$start, $end])
                    ->pluck('admin_id')
            )
            ->filter()
            ->map(fn ($adminId) => (int) $adminId)
            ->unique()
            ->values();

        Admin::whereIn('id', $adminIds)
            ->get()
            ->each(fn (Admin $admin) => $this->backfillForDate($admin, $date));
    }

    private function store(
        string $eventKey,
        ?int $adminId,
        string $status,
        array $snapshot,
        float $amount,
        ?string $reason,
        $processedAt
    ): void {
        CashierTransactionEvent::updateOrCreate(
            ['event_key' => $eventKey],
            [
                'admin_id' => $adminId,
                'booked_ticket_id' => $snapshot['booked_ticket_id'] ?? null,
                'slip_series_number_id' => $snapshot['slip_series_number_id'] ?? null,
                'deposit_id' => $snapshot['deposit_id'] ?? null,
                'status' => $status,
                'processed_at' => $processedAt,
                'source' => $snapshot['source'] ?? null,
                'pnr' => $snapshot['pnr'] ?? null,
                'reference_no' => $snapshot['reference_no'] ?? null,
                'passenger_name' => $snapshot['passenger_name'] ?? null,
                'passenger_type' => $snapshot['passenger_type'] ?? null,
                'passenger_id' => $snapshot['passenger_id'] ?? null,
                'journey_date' => $snapshot['journey_date'] ?? null,
                'departure_time' => $this->normalizeTime($snapshot['departure_time'] ?? null),
                'trip_class' => $snapshot['trip_class'] ?? null,
                'trip_route' => $snapshot['trip_route'] ?? null,
                'seat_no' => $snapshot['seat_no'] ?? null,
                'drop_off' => $snapshot['drop_off'] ?? null,
                'km_post' => $snapshot['km_post'] ?? null,
                'payment_method' => $snapshot['payment_method'] ?? null,
                'base_fare' => (float) ($snapshot['base_fare'] ?? $snapshot['fare'] ?? 0),
                'discount_amount' => (float) ($snapshot['discount_amount'] ?? 0),
                'surcharge_amount' => (float) ($snapshot['surcharge_amount'] ?? 0),
                'amount' => round($amount, 2),
                'reason' => $reason,
                'snapshot' => $snapshot,
            ]
        );
    }

    private function ticketSnapshot(BookedTicket $ticket, SlipSeriesNumber $slip): array
    {
        $ticket->loadMissing($this->ticketRelations());
        $deposit = $ticket->payment_record;
        $passenger = $this->passengerResolver->forSeat($ticket, (string) $slip->seat);
        $manifestEntry = $passenger['entry'];
        $slipCount = max($ticket->slipSeriesNumbers->count(), 1);
        $baseFare = (float) ($manifestEntry['base_fare'] ?? $ticket->unit_price ?? 0);
        $discountAmount = (float) ($manifestEntry['discount_amount'] ?? 0);
        $surchargeAmount = (float) ($deposit?->charge ?? 0) / $slipCount;

        if (!$passenger['manifest_found'] && $deposit?->userDiscount) {
            $percentage = (float) ($deposit->userDiscount->percentage ?? 0);
            $discountAmount = $percentage > 0
                ? $baseFare * ($percentage / 100)
                : (float) ($deposit->userDiscount->amount ?? 0) / $slipCount;
        }

        $fare = (float) ($manifestEntry['fare'] ?? max($baseFare - $discountAmount, 0));

        return [
            'booked_ticket_id' => $ticket->id,
            'slip_series_number_id' => $slip->id,
            'deposit_id' => $deposit?->id,
            'source' => $ticket->kiosk_id ? 'Kiosk' : ($ticket->user_id ? 'Online' : 'Counter'),
            'processed_by' => $deposit?->processedBy?->name
                ?: $deposit?->processedBy?->username
                ?: ($ticket->kiosk_id ? 'Kiosk' : ($ticket->user_id ? 'Online' : 'Counter')),
            'pnr' => $ticket->pnr_number,
            'reference_no' => (string) $slip->id,
            'passenger_name' => $passenger['name'],
            'passenger_type' => $passenger['type'],
            'passenger_id' => $passenger['id_number'],
            'journey_date' => $ticket->date_of_journey
                ? Carbon::parse($ticket->date_of_journey)->format('Y-m-d')
                : null,
            'departure_time' => $ticket->trip?->schedule?->start_from,
            'trip_class' => $ticket->trip?->fleetType?->name,
            'trip_route' => $ticket->trip?->route?->name
                ?: trim(($ticket->pickup?->name ?: '') . ' - ' . ($ticket->drop?->name ?: ''), ' -'),
            'seat_no' => $slip->seat,
            'drop_off' => $ticket->drop?->name,
            'km_post' => $ticket->drop?->km_post,
            'payment_method' => $this->paymentMethod($deposit),
            'base_fare' => round($baseFare, 2),
            'discount_amount' => round($discountAmount, 2),
            'surcharge_amount' => round($surchargeAmount, 2),
            'fare' => round($fare, 2),
        ];
    }

    private function paymentMethod(?Deposit $deposit): string
    {
        if (!$deposit) {
            return '-';
        }

        if ($deposit->pchannel) {
            return readPaymentChannel($deposit->pchannel);
        }

        return $deposit->gatewayCurrency()?->name ?: '-';
    }

    private function normalizeTime($time): ?string
    {
        if (!$time) {
            return null;
        }

        try {
            return Carbon::parse($time)->format('H:i:s');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function depositRelations(): array
    {
        return array_merge(['bookedTicket'], $this->ticketRelations('bookedTicket.'));
    }

    private function actionRelations(): array
    {
        return array_merge(['bookedTicket', 'slipSeriesNumber'], $this->ticketRelations('bookedTicket.'));
    }

    private function ticketRelations(string $prefix = ''): array
    {
        return [
            $prefix . 'trip.route',
            $prefix . 'trip.schedule',
            $prefix . 'trip.fleetType',
            $prefix . 'pickup',
            $prefix . 'drop',
            $prefix . 'user',
            $prefix . 'kiosk',
            $prefix . 'deposit.userDiscount',
            $prefix . 'deposit.processedBy:id,name,username',
            $prefix . 'paymentSourceDeposit.userDiscount',
            $prefix . 'paymentSourceDeposit.processedBy:id,name,username',
            $prefix . 'slipSeriesNumbers',
        ];
    }
}
