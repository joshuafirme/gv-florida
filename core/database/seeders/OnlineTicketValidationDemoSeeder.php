<?php

namespace Database\Seeders;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Services\CashierTransactionRecorder;
use App\Services\SeatConflictService;
use App\Services\SeatLayoutService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OnlineTicketValidationDemoSeeder extends Seeder
{
    public function run(
        SeatLayoutService $seatLayout,
        SeatConflictService $seatConflicts,
        CashierTransactionRecorder $transactionRecorder
    ): void {
        $template = BookedTicket::query()
            ->whereNotNull('user_id')
            ->where(fn ($query) => $query->whereNull('kiosk_id')->orWhere('kiosk_id', 0))
            ->whereHas('deposit', fn ($query) => $query->where('status', Status::PAYMENT_SUCCESS))
            ->with(['deposit', 'trip.fleetType'])
            ->latest('id')
            ->first();

        if (!$template?->deposit || !$template->trip?->fleetType) {
            throw new RuntimeException('A successful online booking is required as the demo payment template.');
        }

        $journeyDate = today()->addDay()->format('Y-m-d');
        $unavailable = $seatConflicts->unavailableSeats(
            $template->trip,
            $journeyDate,
            $template->pickup_point,
            $template->dropping_point
        );
        $seats = $seatLayout->availableSeatIds($template->trip->fleetType, ['booked' => $unavailable])
            ->take(2)
            ->values();

        if ($seats->count() < 2) {
            throw new RuntimeException('Two available seats are required to seed online validation demos.');
        }

        $passengers = [
            ['pnr' => 'OTVTEST001', 'name' => 'Maria Santos'],
            ['pnr' => 'OTVTEST002', 'name' => ''],
        ];

        foreach ($passengers as $index => $passenger) {
            if (BookedTicket::where('pnr_number', $passenger['pnr'])->exists()) {
                continue;
            }

            DB::transaction(function () use ($template, $journeyDate, $seats, $passenger, $index, $transactionRecorder) {
                $seat = (string) $seats[$index];
                $fare = (float) $template->unit_price;
                $manifest = [[
                    'fare' => $fare,
                    'name' => $passenger['name'],
                    'seat' => $seat,
                    'base_fare' => $fare,
                    'id_number' => null,
                    'discount_id' => null,
                    'discount_name' => null,
                    'passenger_type' => 'regular',
                    'discount_amount' => 0,
                    'discount_percentage' => 0,
                ]];

                $ticket = $template->replicate();
                $ticket->forceFill([
                    'series_number' => null,
                    'kiosk_id' => null,
                    'seats' => [$seat],
                    'passenger_manifest' => $manifest,
                    'ticket_count' => 1,
                    'sub_total' => $fare,
                    'date_of_journey' => $journeyDate,
                    'pnr_number' => $passenger['pnr'],
                    'status' => Status::BOOKED_APPROVED,
                    'is_rebooked' => 0,
                    'payment_source_deposit_id' => null,
                ]);
                $ticket->save();

                $deposit = $template->deposit->replicate();
                $deposit->forceFill([
                    'booked_ticket_id' => $ticket->id,
                    'processed_by_admin_id' => null,
                    'processed_by_name' => null,
                    'amount' => $fare,
                    'charge' => 0,
                    'final_amount' => $fare,
                    'trx' => 'OTV-DEMO-' . ($index + 1) . '-' . now()->format('YmdHis'),
                    'status' => Status::PAYMENT_SUCCESS,
                    'expiry_limit' => now()->addMinutes(30),
                ]);
                $deposit->save();

                $ticket->slipSeriesNumbers()->create(['seat' => $seat]);
                $transactionRecorder->recordSold($deposit->fresh());
            });
        }
    }
}
