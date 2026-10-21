<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\Deposit;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PendingPaymentProcessingService
{
    public const LEASE_SECONDS = 120;

    public function __construct(
        private readonly PendingPaymentExpirationService $expirationService
    ) {
    }

    public function claim(Deposit $deposit, int $adminId, ?CarbonInterface $now = null): array
    {
        $currentTime = $now ? CarbonImmutable::instance($now) : CarbonImmutable::now();

        return DB::transaction(function () use ($deposit, $adminId, $currentTime) {
            $lockedDeposit = Deposit::query()
                ->whereKey($deposit->getKey())
                ->lockForUpdate()
                ->first();

            if (!$lockedDeposit || (int) $lockedDeposit->status !== Status::PAYMENT_PENDING) {
                return ['status' => 'processed'];
            }

            if ($this->expirationService->isDue($lockedDeposit, $currentTime)) {
                return ['status' => 'expired'];
            }

            if ($this->hasActiveClaim($lockedDeposit, $currentTime)
                && (int) $lockedDeposit->processing_admin_id !== $adminId) {
                return ['status' => 'busy'];
            }

            $token = Str::random(64);
            $lockedDeposit->processing_admin_id = $adminId;
            $lockedDeposit->processing_token = $token;
            $lockedDeposit->processing_started_at = $lockedDeposit->processing_started_at ?: $currentTime;
            $lockedDeposit->processing_lease_expires_at = $currentTime->addSeconds(self::LEASE_SECONDS);
            $lockedDeposit->save();

            return [
                'status' => 'claimed',
                'token' => $token,
                'lease_expires_at' => $lockedDeposit->processing_lease_expires_at->toIso8601String(),
            ];
        });
    }

    public function renew(
        Deposit $deposit,
        int $adminId,
        string $token,
        ?CarbonInterface $now = null
    ): bool {
        $currentTime = $now ? CarbonImmutable::instance($now) : CarbonImmutable::now();

        return DB::transaction(function () use ($deposit, $adminId, $token, $currentTime) {
            $lockedDeposit = Deposit::query()
                ->whereKey($deposit->getKey())
                ->lockForUpdate()
                ->first();

            if (!$lockedDeposit
                || (int) $lockedDeposit->status !== Status::PAYMENT_PENDING
                || !$this->ownsActiveClaim($lockedDeposit, $adminId, $token, $currentTime)) {
                return false;
            }

            $lockedDeposit->processing_lease_expires_at = $currentTime->addSeconds(self::LEASE_SECONDS);
            $lockedDeposit->save();

            return true;
        });
    }

    public function release(Deposit $deposit, int $adminId, string $token): void
    {
        Deposit::query()
            ->whereKey($deposit->getKey())
            ->where('status', Status::PAYMENT_PENDING)
            ->where('processing_admin_id', $adminId)
            ->where('processing_token', $token)
            ->update([
                'processing_admin_id' => null,
                'processing_token' => null,
                'processing_started_at' => null,
                'processing_lease_expires_at' => null,
            ]);
    }

    public function hasActiveClaim(Deposit $deposit, ?CarbonInterface $now = null): bool
    {
        $currentTime = $now ? CarbonImmutable::instance($now) : CarbonImmutable::now();

        return filled($deposit->processing_token)
            && $deposit->processing_lease_expires_at
            && CarbonImmutable::parse($deposit->processing_lease_expires_at)->greaterThan($currentTime);
    }

    public function ownsActiveClaim(
        Deposit $deposit,
        int $adminId,
        string $token,
        ?CarbonInterface $now = null
    ): bool {
        return hash_equals((string) $deposit->processing_token, $token)
            && (int) $deposit->processing_admin_id === $adminId
            && $this->hasActiveClaim($deposit, $now);
    }
}
