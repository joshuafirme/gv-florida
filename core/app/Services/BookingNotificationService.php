<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\BookedTicket;
use App\Models\PassengerNotification;
use App\Models\SlipSeriesNumber;
use Carbon\Carbon;

class BookingNotificationService
{
    public const TEMPLATES = [
        'ticket_rebooked' => ['Ticket Rebooked', 'Your ticket was rebooked.'],
        'rebooking_approved' => ['Rebooking Approved', 'Your rebooking request was approved.'],
        'rebooking_denied' => ['Rebooking Denied', 'Your rebooking request was denied.'],
        'rebooking_completed' => ['Rebooking Completed', 'Your rebooking was completed.'],
        'trip_schedule_changed' => ['Trip Schedule Changed', 'Your trip schedule was updated.'],
        'trip_cancelled' => ['Trip Cancelled', 'Your scheduled trip was cancelled.'],
        'ticket_cancelled' => ['Ticket Cancelled', 'Your ticket was cancelled.'],
        'ticket_refunded' => ['Ticket Refunded', 'Your ticket was refunded.'],
        'validation_completed' => ['Validation Completed', 'Your ticket validation was completed.'],
        'booking_updated' => ['Booking Updated', 'Important details or the status of your booking were updated.'],
    ];

    public static function manualTemplates(): array
    {
        return self::TEMPLATES;
    }

    public function send(
        BookedTicket $ticket,
        string $eventType,
        string $dedupeKey,
        ?Admin $sentBy = null,
        ?SlipSeriesNumber $slip = null,
        ?string $introOverride = null
    ): ?PassengerNotification {
        if (!$ticket->user_id || !isset(self::TEMPLATES[$eventType])) {
            return null;
        }

        $ticket->loadMissing([
            'user',
            'trip.schedule',
            'pickup',
            'drop',
            'deposit.userDiscount',
            'paymentSourceDeposit.userDiscount',
            'slipSeriesNumbers',
        ]);

        [$title, $defaultIntro] = self::TEMPLATES[$eventType];
        $details = $this->latestDetails($ticket, $slip);
        $message = trim($introOverride ?: $defaultIntro) . ' ' . $this->detailsSentence($details);

        $notification = PassengerNotification::query()->firstOrCreate(
            ['dedupe_key' => $dedupeKey],
            [
                'user_id' => $ticket->user_id,
                'booked_ticket_id' => $ticket->id,
                'sent_by_admin_id' => $sentBy?->id,
                'event_type' => $eventType,
                'title' => $title,
                'message' => $message,
                'channel' => 'Pusher / In-app',
                'sent_by' => $sentBy?->name ?: $sentBy?->username ?: 'System',
                'status' => 'Sent',
                'metadata' => $details,
            ]
        );

        $broadcaster = app(PassengerNotificationBroadcaster::class);

        if (!$notification->wasRecentlyCreated) {
            if ($notification->status !== 'Failed') {
                return null;
            }

            $notification->update(['status' => 'Sent']);
            if (!$broadcaster->broadcast($notification->fresh())) {
                $notification->update(['status' => 'Failed']);
            }

            return $notification->fresh();
        }

        if (!$broadcaster->broadcast($notification)) {
            $notification->update(['status' => 'Failed']);
        }

        return $notification->fresh();
    }

    public function stateFingerprint(BookedTicket $ticket): string
    {
        $ticket->loadMissing('trip.schedule');

        return hash('sha256', implode('|', [
            $ticket->id,
            $ticket->updated_at?->format('Y-m-d H:i:s.u'),
            $ticket->trip?->updated_at?->format('Y-m-d H:i:s.u'),
            $ticket->trip?->schedule?->updated_at?->format('Y-m-d H:i:s.u'),
            $ticket->status,
            $ticket->date_of_journey,
            json_encode($ticket->seats),
        ]));
    }

    private function latestDetails(BookedTicket $ticket, ?SlipSeriesNumber $slip): array
    {
        $seat = $slip?->seat ?: collect($ticket->seats ?: [])->filter()->implode(', ');
        $passenger = $slip
            ? app(TicketPassengerResolver::class)->forSeat($ticket, (string) $slip->seat)
            : null;
        $payment = $ticket->payment_record;
        $passengerName = $passenger['name'] ?? null;
        $passengerName = $passengerName
            ?: $payment?->userDiscount?->passenger_name
            ?: $ticket->user?->fullname
            ?: 'Passenger';

        return [
            'passenger_name' => $passengerName,
            'trip_date' => $ticket->date_of_journey
                ? Carbon::parse($ticket->date_of_journey)->format('M d, Y')
                : '-',
            'departure_time' => $ticket->trip?->schedule?->start_from
                ? Carbon::parse($ticket->trip->schedule->start_from)->format('g:i A')
                : '-',
            'origin' => $ticket->pickup?->name ?: $ticket->trip?->startFrom?->name ?: '-',
            'destination' => $ticket->drop?->name ?: $ticket->trip?->endTo?->name ?: '-',
            'seat_number' => formatSeatLabel($seat ?: '-'),
            'pnr' => $ticket->pnr_number ?: '-',
        ];
    }

    private function detailsSentence(array $details): string
    {
        return sprintf(
            'Passenger: %s. Trip: %s at %s, %s to %s. Seat: %s. PNR: %s.',
            $details['passenger_name'],
            $details['trip_date'],
            $details['departure_time'],
            $details['origin'],
            $details['destination'],
            $details['seat_number'],
            $details['pnr']
        );
    }
}
