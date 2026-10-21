<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Models\CashierTransactionEvent;
use App\Models\PassengerNotification;
use Carbon\Carbon;

class PassengerNotificationPresenter
{
    public function present(PassengerNotification $notification): array
    {
        $notification->loadMissing([
            'bookedTicket.trip.route',
            'bookedTicket.trip.schedule',
            'bookedTicket.trip.fleetType',
            'bookedTicket.pickup',
            'bookedTicket.drop',
            'bookedTicket.user',
            'bookedTicket.deposit.gateway',
            'bookedTicket.paymentSourceDeposit.gateway',
            'bookedTicket.transactionEvents.admin',
        ]);

        $ticket = $notification->bookedTicket;
        $metadata = $notification->metadata ?: [];
        $payment = $ticket?->payment_record;
        [$ticketStatus, $ticketTone] = $this->ticketStatus($ticket);
        [$paymentStatus, $paymentTone] = $this->paymentStatus($payment?->status);

        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'tone' => $notification->uiTone(),
            'icon' => $notification->uiIcon(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'created_at_label' => $notification->created_at
                ? showDateTime($notification->created_at, 'D, M d, Y, g:i A')
                : '',
            'created_at_human' => $notification->created_at ? diffForHumans($notification->created_at) : '',
            'movement_title' => $this->movementTitle($notification->event_type),
            'movement' => $this->movementRows($notification, $ticket),
            'details' => array_values(array_filter([
                $this->row('Passenger', $metadata['passenger_name'] ?? $ticket?->user?->fullname),
                $this->row('PNR', $metadata['pnr'] ?? $ticket?->pnr_number),
                $this->row('Route', $this->routeLabel($ticket, $metadata)),
                $this->row('Bus Class', $ticket?->trip?->fleetType?->name),
                $this->row('Travel Date', $this->dateLabel($ticket?->date_of_journey ?? $metadata['trip_date'] ?? null)),
                $this->row('Departure Time', $this->timeLabel($ticket?->trip?->schedule?->start_from ?? $metadata['departure_time'] ?? null)),
                $this->row('Seat(s)', formatSeatLabel($ticket?->seats ?: ($metadata['seat_number'] ?? '-'))),
                $this->row('Payment Method', $this->paymentMethod($payment)),
                $this->row('Fare', $this->money($payment?->final_amount ?? $payment?->amount ?? $ticket?->sub_total), 'amount'),
                $this->row('Ticket Status', $ticketStatus, 'badge', $ticketTone),
                $this->row('Payment Status', $paymentStatus, 'badge', $paymentTone),
            ])),
            'voucher_url' => $ticket && (int) $ticket->user_id === (int) $notification->user_id
                ? route('user.ticket.print', $ticket)
                : null,
            'read_url' => route('user.notifications.read', $notification),
            'detail_url' => route('user.notifications.show', $notification),
        ];
    }

    private function movementRows(PassengerNotification $notification, ?BookedTicket $ticket): array
    {
        if (!$ticket) {
            return [];
        }

        $event = $this->matchingEvent($notification, $ticket);
        if (!$event) {
            return array_values(array_filter([
                $this->row('Handled by', $notification->sent_by),
            ]));
        }

        $history = data_get($event->snapshot, 'rebooking', []);
        $previous = $history['previous'] ?? [];
        $new = $history['new'] ?? [];
        $rows = [];

        if ($event->status === 'Rebooked') {
            $rows[] = $this->changeRow(
                'Seat',
                $history['previous_seat'] ?? $this->seats($previous['seats'] ?? []),
                $history['new_seat'] ?? $this->seats($new['seats'] ?? [])
            );
            $rows[] = $this->changeRow(
                'Travel Date',
                $this->dateLabel($previous['journey_date'] ?? null),
                $this->dateLabel($new['journey_date'] ?? null)
            );
            $rows[] = $this->changeRow('Trip', $previous['trip'] ?? null, $new['trip'] ?? null);
        }

        $rows[] = $this->row('Reason', $event->reason);
        $rows[] = $this->row('Handled by', $event->processed_by_label ?: $notification->sent_by);

        return array_values(array_filter($rows));
    }

    private function matchingEvent(PassengerNotification $notification, BookedTicket $ticket): ?CashierTransactionEvent
    {
        $status = match (true) {
            str_contains($notification->event_type, 'rebook') => 'Rebooked',
            str_contains($notification->event_type, 'refund') => 'Refunded',
            str_contains($notification->event_type, 'cancel') => 'Cancelled',
            str_contains($notification->event_type, 'validation') => 'Validated',
            default => null,
        };

        if (!$status) {
            return null;
        }

        return $ticket->transactionEvents
            ->where('status', $status)
            ->filter(fn (CashierTransactionEvent $event) => !$notification->created_at
                || !$event->processed_at
                || $event->processed_at->lte($notification->created_at->copy()->addMinute()))
            ->sortByDesc(fn (CashierTransactionEvent $event) => $event->processed_at?->timestamp ?? $event->id)
            ->first();
    }

    private function movementTitle(string $eventType): string
    {
        return str_contains($eventType, 'rebook') ? 'Movement Details' : 'Update Details';
    }

    private function routeLabel(?BookedTicket $ticket, array $metadata): ?string
    {
        $origin = $ticket?->pickup?->name ?: ($metadata['origin'] ?? null);
        $destination = $ticket?->drop?->name ?: ($metadata['destination'] ?? null);

        return $origin && $destination ? "{$origin} to {$destination}" : ($origin ?: $destination);
    }

    private function paymentMethod($payment): ?string
    {
        if (!$payment) {
            return null;
        }

        if ($payment->pchannel) {
            return getPaynamicsPChannel($payment->pchannel, true) ?: $payment->pchannel;
        }

        return $payment->gateway?->name ?: ($payment->method_currency ?: 'Online Payment');
    }

    private function ticketStatus(?BookedTicket $ticket): array
    {
        return match ($ticket?->status) {
            Status::BOOKED_APPROVED => ['Booked', 'success'],
            Status::BOOKED_PENDING => ['Pending', 'warning'],
            Status::BOOKED_REFUNDED => ['Refunded', 'warning'],
            Status::BOOKED_CANCELLED => ['Cancelled', 'danger'],
            Status::BOOKED_VOIDED => ['Voided', 'danger'],
            Status::BOOKED_EXPIRED => ['Expired', 'danger'],
            Status::BOOKED_REJECTED => ['Rejected', 'danger'],
            default => ['Unavailable', 'neutral'],
        };
    }

    private function paymentStatus($status): array
    {
        return match ($status) {
            Status::PAYMENT_SUCCESS => ['Paid', 'success'],
            Status::PAYMENT_PENDING => ['Pending', 'warning'],
            Status::PAYMENT_REJECT => ['Rejected', 'danger'],
            Status::PAYMENT_EXPIRED => ['Expired', 'danger'],
            Status::PAYMENT_INITIATE => ['Initiated', 'neutral'],
            default => ['Unavailable', 'neutral'],
        };
    }

    private function row(string $label, $value, string $type = 'text', ?string $tone = null): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        return array_filter([
            'label' => $label,
            'value' => (string) $value,
            'type' => $type,
            'tone' => $tone,
        ], fn ($item) => $item !== null);
    }

    private function changeRow(string $label, $from, $to): ?array
    {
        if (!$from || !$to || (string) $from === (string) $to) {
            return null;
        }

        return [
            'label' => $label,
            'from' => (string) $from,
            'value' => (string) $to,
            'type' => 'change',
        ];
    }

    private function dateLabel($value): ?string
    {
        if (!$value || $value === '-') {
            return $value ?: null;
        }

        try {
            return Carbon::parse($value)->format('M j, Y');
        } catch (\Throwable $exception) {
            return (string) $value;
        }
    }

    private function timeLabel($value): ?string
    {
        if (!$value || $value === '-') {
            return $value ?: null;
        }

        try {
            return Carbon::parse($value)->format('g:i A');
        } catch (\Throwable $exception) {
            return (string) $value;
        }
    }

    private function money($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (gs('cur_sym') ?: 'PHP ') . number_format((float) $value, 2);
    }

    private function seats($seats): ?string
    {
        $value = formatSeatLabel($seats ?: []);

        return $value && $value !== '-' ? $value : null;
    }
}
