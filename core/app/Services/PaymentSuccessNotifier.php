<?php

namespace App\Services;

use App\Models\BookedTicket;
use App\Models\Deposit;
use App\Models\GeneralSetting;
use App\Models\User;
use Carbon\Carbon;

class PaymentSuccessNotifier
{
    public function send(
        Deposit $deposit,
        BookedTicket $bookedTicket,
        bool $isManual = false,
        ?array $sendVia = null
    ): bool {
        $user = $deposit->relationLoaded('user')
            ? $deposit->user
            : User::find($deposit->user_id);

        if (!$user) {
            return false;
        }

        $bookedTicket->loadMissing(['pickup', 'drop', 'trip.schedule']);

        $gatewayCurrency = $deposit->gatewayCurrency();
        $general = GeneralSetting::first();
        $scheduledDeparture = $bookedTicket->trip?->schedule?->start_from;

        notify($user, $isManual ? 'PAYMENT_APPROVE' : 'PAYMENT_COMPLETE', [
            'method_name' => $gatewayCurrency?->name ?? $deposit->methodName() ?? 'Online Payment',
            'method_currency' => $deposit->method_currency,
            'method_amount' => showAmount($deposit->final_amount, currencyFormat: false),
            'amount' => showAmount($deposit->amount, currencyFormat: false),
            'charge' => showAmount($deposit->charge, currencyFormat: false),
            'currency' => $general?->cur_text,
            'rate' => showAmount($deposit->rate, currencyFormat: false),
            'trx' => $deposit->trx,
            'journey_date' => showDateTime($bookedTicket->date_of_journey, 'd m, Y'),
            'departure_time' => $scheduledDeparture
                ? Carbon::parse($scheduledDeparture)->format('g:i A')
                : 'N/A',
            'seats' => formatSeatLabel($bookedTicket->seats),
            'total_seats' => count($bookedTicket->seats ?? []),
            'source' => $bookedTicket->pickup?->name ?? 'N/A',
            'destination' => $bookedTicket->drop?->name ?? 'N/A',
            'ticket' => $bookedTicket,
            'has_file' => true,
        ], $sendVia);

        return true;
    }
}
