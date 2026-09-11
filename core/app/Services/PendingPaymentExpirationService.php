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

    public function __construct(
        private readonly ?PaynamicsPaymentBroadcaster $paymentBroadcaster = null
    ) {
    }

    public function expiresAt(CarbonInterface $createdAt): CarbonImmutable
    {
        return CarbonImmutable::instance($createdAt)
            ->addMinutes(self::EXPIRATION_MINUTES);
    }

    public function expireDue(?CarbonInterface $now = null): int
    {
        $currentTime = $now ? CarbonImmutable::instance($now) : CarbonImmutable::now();
        $legacyCutoff = $currentTime->subMinutes(self::EXPIRATION_MINUTES);
        $expiredCount = 0;

        Deposit::query()
            ->whereIn('status', [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING])
            ->where(function ($query) use ($currentTime, $legacyCutoff) {
                $query->where(function ($configuredExpiry) use ($currentTime) {
                    $configuredExpiry->whereNotNull('expiry_limit')
                        ->where('expiry_limit', '!=', '')
                        ->where('expiry_limit', '<=', $currentTime->format('Y-m-d H:i:s'));
                })->orWhere(function ($fallbackExpiry) use ($legacyCutoff) {
                    $fallbackExpiry->where(function ($missingExpiry) {
                        $missingExpiry->whereNull('expiry_limit')
                            ->orWhere('expiry_limit', '');
                    })->where('created_at', '<=', $legacyCutoff);
                });
            })
            ->select('id')
            ->chunkById(100, function ($deposits) use ($currentTime, &$expiredCount) {
                foreach ($deposits as $deposit) {
                    if ($this->expireIfDue($deposit, $currentTime)) {
                        $expiredCount++;
                    }
                }
            });

        return $expiredCount;
    }

    public function expireIfDue(Deposit $deposit, ?CarbonInterface $now = null): bool
    {
        $currentTime = $now ? CarbonImmutable::instance($now) : CarbonImmutable::now();

        $expiredDeposit = DB::transaction(function () use ($deposit, $currentTime) {
            $lockedDeposit = Deposit::query()
                ->with('bookedTicket')
                ->whereKey($deposit->getKey())
                ->whereIn('status', [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING])
                ->lockForUpdate()
                ->first();

            if (!$lockedDeposit || !$this->isDue($lockedDeposit, $currentTime)) {
                return null;
            }

            $lockedDeposit->status = Status::PAYMENT_EXPIRED;
            $lockedDeposit->save();

            $ticket = $lockedDeposit->bookedTicket;
            if ($ticket && (int) $ticket->status === Status::BOOKED_PENDING) {
                $ticket->status = Status::BOOKED_EXPIRED;
                $ticket->save();
            }

            return $lockedDeposit;
        });

        if (!$expiredDeposit) {
            return false;
        }

        $expiredDeposit->refresh();
        $this->broadcastExpiration($expiredDeposit);

        return true;
    }

    public function isDue(Deposit $deposit, ?CarbonInterface $now = null): bool
    {
        $currentTime = $now ? CarbonImmutable::instance($now) : CarbonImmutable::now();

        return $this->expiresAtForDeposit($deposit)->lessThanOrEqualTo($currentTime);
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

    private function broadcastExpiration(Deposit $deposit): void
    {
        if (!filled($deposit->pchannel)) {
            return;
        }

        try {
            $broadcaster = $this->paymentBroadcaster ?? app(PaynamicsPaymentBroadcaster::class);
            $broadcaster->paymentUpdated($deposit->trx, [
                'state' => 'expired',
                'is_paid' => false,
                'transaction_id' => $deposit->trx,
                'payment_method' => $deposit->pchannel ?: 'Paynamics',
                'amount' => (float) $deposit->final_amount,
                'amount_display' => number_format((float) $deposit->final_amount, 2),
                'updated_at' => $deposit->updated_at?->toIso8601String(),
                'details' => [
                    'message' => 'Payment window expired. The reserved seats have been released.',
                    'payment_channel' => $deposit->pchannel ?: 'Paynamics',
                    'pay_reference' => $deposit->pay_reference,
                    'request_id' => $deposit->trx,
                    'response_code' => 'EXPIRED',
                    'timestamp' => $deposit->updated_at?->format('M j, Y g:i:s A'),
                ],
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
