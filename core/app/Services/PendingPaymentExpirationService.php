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

    public function expiresAt(CarbonInterface $createdAt): CarbonImmutable
    {
        return CarbonImmutable::instance($createdAt)
            ->addMinutes(self::EXPIRATION_MINUTES);
    }

    public function expireDue(?CarbonInterface $now = null): int
    {
        $currentTime = $now ? CarbonImmutable::instance($now) : CarbonImmutable::now();
        $legacyCutoff = $currentTime
            ->subMinutes(self::EXPIRATION_MINUTES);
        $expiredCount = 0;

        Deposit::query()
            ->where('status', Status::PAYMENT_PENDING)
            ->where('created_at', '<=', $legacyCutoff)
            ->select('id')
            ->chunkById(100, function ($deposits) use ($currentTime, &$expiredCount) {
                $ids = $deposits->pluck('id');

                $expiredCount += DB::transaction(function () use ($ids, $currentTime) {
                    $dueDeposits = Deposit::query()
                        ->with('bookedTicket')
                        ->whereIn('id', $ids)
                        ->where('status', Status::PAYMENT_PENDING)
                        ->lockForUpdate()
                        ->get()
                        ->filter(function (Deposit $deposit) use ($currentTime) {
                            $expiresAt = $this->expiresAtForDeposit($deposit);

                            return $expiresAt->lessThanOrEqualTo($currentTime);
                        });

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

    public function expiresAtForDeposit(Deposit $deposit): CarbonImmutable
    {
        if ($deposit->expiry_limit) {
            try {
                return CarbonImmutable::parse($deposit->expiry_limit);
            } catch (\Throwable) {
                // Legacy malformed values fall back to the voucher rule below.
            }
        }

        return $this->expiresAt($deposit->created_at);
    }
}
