<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\Deposit;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class PendingPaymentExpirationService
{
    public const EXPIRATION_MINUTES = 15;

    public function expireDue(?CarbonInterface $now = null): int
    {
        $cutoff = ($now ? CarbonImmutable::instance($now) : CarbonImmutable::now())
            ->subMinutes(self::EXPIRATION_MINUTES);
        $expiredCount = 0;

        Deposit::query()
            ->where('status', Status::PAYMENT_PENDING)
            ->where('created_at', '<=', $cutoff)
            ->whereNotExists(function ($successfulDeposit) {
                $successfulDeposit
                    ->selectRaw('1')
                    ->from('deposits as successful_deposits')
                    ->whereColumn('successful_deposits.booked_ticket_id', 'deposits.booked_ticket_id')
                    ->whereNotNull('deposits.booked_ticket_id')
                    ->where('successful_deposits.status', Status::PAYMENT_SUCCESS);
            })
            ->select('id')
            ->chunkById(100, function ($deposits) use ($cutoff, &$expiredCount) {
                $ids = $deposits->pluck('id');

                $expiredCount += DB::transaction(function () use ($ids, $cutoff) {
                    $dueDeposits = Deposit::query()
                        ->with('bookedTicket')
                        ->whereIn('id', $ids)
                        ->where('status', Status::PAYMENT_PENDING)
                        ->where('created_at', '<=', $cutoff)
                        ->whereNotExists(function ($successfulDeposit) {
                            $successfulDeposit
                                ->selectRaw('1')
                                ->from('deposits as successful_deposits')
                                ->whereColumn('successful_deposits.booked_ticket_id', 'deposits.booked_ticket_id')
                                ->whereNotNull('deposits.booked_ticket_id')
                                ->where('successful_deposits.status', Status::PAYMENT_SUCCESS);
                        })
                        ->lockForUpdate()
                        ->get();

                    foreach ($dueDeposits as $deposit) {
                        $deposit->status = Status::PAYMENT_EXPIRED;
                        $deposit->save();

                        $ticket = $deposit->bookedTicket;
                        if ($ticket && (int) $ticket->status === Status::BOOKED_PENDING) {
                            $ticket->status = Status::BOOKED_EXPIRED;
                            $ticket->save();
                        }
                    }

                    return $dueDeposits->count();
                });
            });

        return $expiredCount;
    }
}
