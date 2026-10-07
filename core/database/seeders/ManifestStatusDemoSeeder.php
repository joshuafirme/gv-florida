<?php

namespace Database\Seeders;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Models\Deposit;
use App\Models\Kiosk;
use App\Models\Trip;
use App\Models\User;
use App\Services\SeatLayoutService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ManifestStatusDemoSeeder extends Seeder
{
    private const TRIP_ID = 4;
    private const JOURNEY_DATE = '2026-10-08';

    public function run(SeatLayoutService $seatLayout): void
    {
        $trip = Trip::query()->with(['fleetType', 'ticketPrice'])->find(self::TRIP_ID);

        if (!$trip?->fleetType) {
            throw new RuntimeException('Trip 4 with a fleet type is required for the manifest demo.');
        }

        $user = User::query()->where('status', Status::ENABLE)->orderBy('id')->first();
        $kiosk = Kiosk::query()
            ->where('status', Status::ENABLE)
            ->where('counter_id', $trip->start_from)
            ->orderBy('id')
            ->first();

        if (!$user || !$kiosk) {
            throw new RuntimeException('An active user and an active kiosk at the trip origin are required.');
        }

        $records = $this->records();
        $demoTickets = BookedTicket::query()
            ->whereIn('pnr_number', collect($records)->pluck('pnr'))
            ->get();
        $occupiedSeats = BookedTicket::query()
            ->where('trip_id', $trip->id)
            ->whereDate('date_of_journey', self::JOURNEY_DATE)
            ->whereNotIn('id', $demoTickets->pluck('id'))
            ->holdingSeats()
            ->get(['seats'])
            ->flatMap(fn (BookedTicket $ticket) => $ticket->seats ?: [])
            ->all();
        $seats = $seatLayout->availableSeatIds($trip->fleetType, ['booked' => $occupiedSeats])
            ->take(count($records))
            ->values();

        if ($seats->count() < count($records)) {
            throw new RuntimeException('Four available seats are required to seed the manifest demo.');
        }

        $fare = (float) ($trip->ticketPrice?->price ?? 0);
        $now = now();

        DB::transaction(function () use ($records, $seats, $trip, $user, $kiosk, $fare, $now): void {
            foreach ($records as $index => $record) {
                $seat = (string) $seats[$index];
                $isOnline = $record['channel'] === 'online';
                $isPending = $record['status'] === Status::BOOKED_PENDING;
                $passengerManifest = [[
                    'fare' => $fare,
                    'name' => $record['passenger_name'],
                    'seat' => $seat,
                    'base_fare' => $fare,
                    'id_number' => $record['id_number'],
                    'discount_id' => null,
                    'discount_name' => $record['passenger_type'] === 'regular'
                        ? null
                        : $record['passenger_type'],
                    'passenger_type' => $record['passenger_type'] === 'regular'
                        ? 'regular'
                        : 'discounted',
                    'discount_amount' => 0,
                    'discount_percentage' => $record['passenger_type'] === 'regular' ? 0 : 20,
                ]];

                $ticket = BookedTicket::query()->firstOrNew(['pnr_number' => $record['pnr']]);
                $ticket->forceFill([
                    'user_id' => $isOnline ? $user->id : null,
                    'gender' => 0,
                    'trip_id' => $trip->id,
                    'kiosk_id' => $isOnline ? null : $kiosk->id,
                    'approved_by' => null,
                    'source_destination' => [$trip->start_from, $trip->end_to],
                    'pickup_point' => $trip->start_from,
                    'dropping_point' => $trip->end_to,
                    'seats' => [$seat],
                    'passenger_manifest' => $passengerManifest,
                    'ticket_count' => 1,
                    'unit_price' => $fare,
                    'sub_total' => $fare,
                    'date_of_journey' => self::JOURNEY_DATE,
                    'status' => $record['status'],
                    'is_rebooked' => 0,
                    'payment_source_deposit_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->save();

                $ticket->slipSeriesNumbers()->where('seat', '!=', $seat)->delete();
                $ticket->slipSeriesNumbers()->firstOrCreate(['seat' => $seat]);

                $deposit = Deposit::query()->firstOrNew(['booked_ticket_id' => $ticket->id]);
                $deposit->forceFill([
                    'user_id' => $isOnline ? $user->id : null,
                    'processed_by_admin_id' => null,
                    'processed_by_name' => null,
                    'method_code' => $isOnline ? 126 : 1001,
                    'pmethod' => $isOnline ? 'wallet' : 'cash',
                    'pchannel' => $isOnline ? 'paymaya_ph' : null,
                    'pay_reference' => null,
                    'expiry_limit' => $isPending
                        ? $now->copy()->addMinutes($isOnline ? 30 : 15)->format('Y-m-d H:i:s')
                        : null,
                    'amount' => $fare,
                    'method_currency' => 'PHP',
                    'charge' => 0,
                    'rate' => 1,
                    'final_amount' => $fare,
                    'detail' => null,
                    'btc_amount' => 0,
                    'btc_wallet' => '',
                    'trx' => $record['request_id'],
                    'payment_try' => 0,
                    'status' => $isPending ? Status::PAYMENT_PENDING : Status::PAYMENT_SUCCESS,
                    'from_api' => 0,
                    'success_url' => route('user.deposit.done'),
                    'failed_url' => urlPath('ticket'),
                    'last_cron' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->save();
            }
        });

        $this->command?->info(
            'Manifest demo seeded for Trip 4 on October 8, 2026. Pending indicators remain active for 15/30 minutes.'
        );
    }

    private function records(): array
    {
        return [
            [
                'pnr' => 'MAN4KIOSK01',
                'request_id' => 'MANIFEST-KIOSK-PAID',
                'channel' => 'kiosk',
                'status' => Status::BOOKED_APPROVED,
                'passenger_name' => 'Kiosk Passenger',
                'passenger_type' => 'regular',
                'id_number' => null,
            ],
            [
                'pnr' => 'MAN4ONLINE1',
                'request_id' => 'MANIFEST-ONLINE-PAID',
                'channel' => 'online',
                'status' => Status::BOOKED_APPROVED,
                'passenger_name' => 'Online Senior Passenger',
                'passenger_type' => 'Senior Citizen',
                'id_number' => 'SC-DEMO-1001',
            ],
            [
                'pnr' => 'MAN4KPEND1',
                'request_id' => 'MANIFEST-KIOSK-PENDING',
                'channel' => 'kiosk',
                'status' => Status::BOOKED_PENDING,
                'passenger_name' => 'Pending Kiosk Passenger',
                'passenger_type' => 'regular',
                'id_number' => null,
            ],
            [
                'pnr' => 'MAN4OPEND1',
                'request_id' => 'MANIFEST-ONLINE-PENDING',
                'channel' => 'online',
                'status' => Status::BOOKED_PENDING,
                'passenger_name' => 'Pending Online Passenger',
                'passenger_type' => 'regular',
                'id_number' => null,
            ],
        ];
    }
}
