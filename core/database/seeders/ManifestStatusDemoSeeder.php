<?php

namespace Database\Seeders;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Models\Deposit;
use App\Models\Trip;
use App\Models\User;
use App\Services\SeatLayoutService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ManifestStatusDemoSeeder extends Seeder
{
    private const TRIP_ID = 59;
    private const JOURNEY_DATE = '2026-10-06';

    public function run(SeatLayoutService $seatLayout): void
    {
        $trip = Trip::with(['fleetType', 'route'])->find(self::TRIP_ID);
        $user = User::query()->orderBy('id')->first();
        $paymentTemplate = Deposit::query()->latest('id')->first();

        if (!$trip?->fleetType || !$trip->route || !$user || !$paymentTemplate) {
            throw new RuntimeException('Trip 59, a customer, and a payment template are required for the manifest demo.');
        }

        $availableSeats = collect($seatLayout->layout($trip->fleetType)['seat_ids'])
            ->reject(fn (string $seat) => in_array(
                $seat,
                $seatLayout->disabledSeatIds($trip->fleetType),
                true
            ))
            ->take(3)
            ->values();

        if ($availableSeats->count() < 3) {
            throw new RuntimeException('Three available fleet seats are required for the manifest demo.');
        }

        $samples = [
            [
                'pnr' => 'MAN59-COUNTER',
                'name' => 'Counter Passenger',
                'seat' => $availableSeats[0],
                'online' => false,
                'pending' => false,
            ],
            [
                'pnr' => 'MAN59-ONLINE',
                'name' => 'Online Passenger',
                'seat' => $availableSeats[1],
                'online' => true,
                'pending' => false,
            ],
            [
                'pnr' => 'MAN59-PENDING',
                'name' => 'Pending Passenger',
                'seat' => $availableSeats[2],
                'online' => true,
                'pending' => true,
            ],
        ];

        foreach ($samples as $sample) {
            DB::transaction(function () use ($trip, $user, $paymentTemplate, $sample) {
                $fare = 950.00;
                $ticket = BookedTicket::firstOrNew(['pnr_number' => $sample['pnr']]);
                $ticket->forceFill([
                    'user_id' => $sample['online'] ? $user->id : 0,
                    'gender' => 0,
                    'trip_id' => $trip->id,
                    'kiosk_id' => null,
                    'approved_by' => null,
                    'source_destination' => [$trip->route->start_from, $trip->route->end_to],
                    'pickup_point' => $trip->route->start_from,
                    'dropping_point' => $trip->route->end_to,
                    'seats' => [$sample['seat']],
                    'passenger_manifest' => [[
                        'fare' => $fare,
                        'name' => $sample['name'],
                        'seat' => $sample['seat'],
                        'base_fare' => $fare,
                        'id_number' => null,
                        'discount_id' => null,
                        'discount_name' => null,
                        'passenger_type' => 'regular',
                        'discount_amount' => 0,
                        'discount_percentage' => 0,
                    ]],
                    'ticket_count' => 1,
                    'unit_price' => $fare,
                    'sub_total' => $fare,
                    'date_of_journey' => self::JOURNEY_DATE,
                    'status' => $sample['pending'] ? Status::BOOKED_PENDING : Status::BOOKED_APPROVED,
                    'is_rebooked' => 0,
                    'payment_source_deposit_id' => null,
                ]);
                $ticket->save();
                $ticket->slipSeriesNumbers()->firstOrCreate(['seat' => $sample['seat']]);

                if (!$sample['online']) {
                    return;
                }

                $transactionId = $sample['pending'] ? 'MANIFEST-PENDING-59' : 'MANIFEST-ONLINE-59';
                $deposit = Deposit::where('trx', $transactionId)->first()
                    ?: $paymentTemplate->replicate();
                $deposit->forceFill([
                    'user_id' => $user->id,
                    'processed_by_admin_id' => null,
                    'processed_by_name' => null,
                    'booked_ticket_id' => $ticket->id,
                    'amount' => $fare,
                    'charge' => 0,
                    'rate' => 1,
                    'final_amount' => $fare,
                    'trx' => $transactionId,
                    'pay_reference' => $sample['pending'] ? 'DEMO-PENDING' : 'DEMO-PAID',
                    'status' => $sample['pending'] ? Status::PAYMENT_PENDING : Status::PAYMENT_SUCCESS,
                    'expiry_limit' => $sample['pending'] ? now()->addHours(4) : null,
                ]);
                $deposit->save();
            });
        }
    }
}
