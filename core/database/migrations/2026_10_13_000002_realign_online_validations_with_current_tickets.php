<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('online_ticket_validations')
            ->orderBy('id')
            ->chunkById(500, function ($validations) {
                $slips = DB::table('slip_series_numbers')
                    ->whereIn('id', $validations->pluck('slip_series_number_id'))
                    ->get(['id', 'booked_ticket_id'])
                    ->keyBy('id');
                $ticketIds = $slips->pluck('booked_ticket_id')->filter()->unique()->values();
                $tickets = DB::table('booked_tickets')
                    ->whereIn('id', $ticketIds)
                    ->get(['id', 'payment_source_deposit_id'])
                    ->keyBy('id');
                $directDeposits = DB::table('deposits')
                    ->whereIn('booked_ticket_id', $ticketIds)
                    ->orderByDesc('id')
                    ->get(['id', 'booked_ticket_id'])
                    ->unique('booked_ticket_id')
                    ->keyBy('booked_ticket_id');

                foreach ($validations as $validation) {
                    $ticketId = $slips->get($validation->slip_series_number_id)?->booked_ticket_id;
                    if (!$ticketId) {
                        continue;
                    }

                    $depositId = $directDeposits->get($ticketId)?->id
                        ?: $tickets->get($ticketId)?->payment_source_deposit_id;

                    if ((int) $validation->booked_ticket_id === (int) $ticketId
                        && (int) $validation->deposit_id === (int) $depositId) {
                        continue;
                    }

                    DB::table('online_ticket_validations')
                        ->where('id', $validation->id)
                        ->update([
                            'booked_ticket_id' => $ticketId,
                            'deposit_id' => $depositId,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // The previous ticket link is not reliably reconstructable after a rebooking.
    }
};
