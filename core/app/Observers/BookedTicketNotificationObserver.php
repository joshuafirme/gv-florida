<?php

namespace App\Observers;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Services\BookingNotificationService;
use Illuminate\Support\Facades\DB;

class BookedTicketNotificationObserver
{
    public function updated(BookedTicket $ticket): void
    {
        if (!$ticket->wasChanged('status') || !$ticket->user_id) {
            return;
        }

        $status = (int) $ticket->status;
        if (!in_array($status, [Status::BOOKED_APPROVED, Status::BOOKED_REJECTED, Status::BOOKED_EXPIRED], true)) {
            return;
        }

        $ticketId = (int) $ticket->id;
        $version = $ticket->updated_at?->format('YmdHisv') ?: now()->format('YmdHisv');
        $intro = match ($status) {
            Status::BOOKED_APPROVED => 'Your booking is now confirmed.',
            Status::BOOKED_REJECTED => 'Your booking was not approved.',
            Status::BOOKED_EXPIRED => 'Your pending booking expired.',
        };

        $callback = static function () use ($ticketId, $status, $version, $intro): void {
            $freshTicket = BookedTicket::find($ticketId);
            if (!$freshTicket) {
                return;
            }

            app(BookingNotificationService::class)->send(
                $freshTicket,
                'booking_updated',
                "ticket-status:{$ticketId}:{$status}:{$version}",
                null,
                null,
                $intro
            );
        };

        DB::transactionLevel() > 0 ? DB::afterCommit($callback) : $callback();
    }
}
