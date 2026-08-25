<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\Admin;
use App\Models\CashierTransactionEvent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransactionAuthorizationService
{
    public const CANCELLATION = 'Ticket Cancellation';
    public const CHANNEL_ACCESS = 'Trip Channel Access';
    public const DISCOUNT_OVERRIDE = 'Online Ticket Discount Override';
    public const REBOOKING = 'Ticket Rebooking';
    public const REFUND = 'Ticket Refund';
    public const SEAT_LOCKING = 'Admin Seat Locking';
    public const VOID = 'Ticket Void';

    private const REQUIRED_PERMISSIONS = [
        self::CANCELLATION => 'admin.vehicle.ticket.authorize.cancel',
        self::CHANNEL_ACCESS => 'admin.trip.channel-access.index',
        self::DISCOUNT_OVERRIDE => 'admin.online.ticket.validation.discount',
        self::REBOOKING => 'admin.vehicle.ticket.authorize.rebook',
        self::REFUND => 'admin.vehicle.ticket.authorize.refund',
        self::SEAT_LOCKING => 'admin.trip.seat-locks.index',
        self::VOID => 'admin.vehicle.ticket.authorize.void',
    ];

    public static function lookup(string $code): string
    {
        return hash_hmac('sha256', trim($code), (string) config('app.key'));
    }

    public static function hash(string $code): string
    {
        return Hash::make(trim($code));
    }

    public function authorize(string $code, string $transactionType, array $context = []): Admin
    {
        $normalizedCode = trim($code);
        $owner = $normalizedCode === ''
            ? null
            : Admin::with('role')
                ->where('authorization_code_lookup', self::lookup($normalizedCode))
                ->first();

        $validCode = $owner
            && $owner->authorization_code_hash
            && Hash::check($normalizedCode, $owner->authorization_code_hash);
        $permitted = $validCode && $this->canAuthorize($owner, $transactionType);

        if (!$validCode || (int) $owner->status !== Status::ENABLE || !$permitted) {
            $failure = !$validCode
                ? 'Invalid authorization code'
                : ((int) $owner->status !== Status::ENABLE
                    ? 'Authorization code owner is inactive'
                    : 'Authorization code owner lacks permission');

            $this->recordFailedAttempt($transactionType, $context, $owner, $failure);

            throw ValidationException::withMessages([
                'authorization_code' => 'The authorization code is invalid or the code owner is not permitted to authorize this transaction.',
            ]);
        }

        return $owner;
    }

    public function canAuthorize(Admin $admin, string $transactionType): bool
    {
        $permissions = json_decode((string) $admin->role?->permissions, true) ?: [];
        $requiredPermission = self::REQUIRED_PERMISSIONS[$transactionType] ?? null;

        return $requiredPermission
            && (int) $admin->role?->status === Status::ENABLE
            && in_array($requiredPermission, $permissions, true);
    }

    private function recordFailedAttempt(
        string $transactionType,
        array $context,
        ?Admin $codeOwner,
        string $failure
    ): void {
        $performedBy = auth('admin')->user();

        CashierTransactionEvent::create([
            'admin_id' => $performedBy?->id,
            'booked_ticket_id' => $context['booked_ticket_id'] ?? null,
            'slip_series_number_id' => $context['slip_series_number_id'] ?? null,
            'event_key' => 'authorization-failed:' . Str::uuid(),
            'status' => 'Authorization Failed',
            'processed_at' => now(),
            'source' => 'Admin',
            'pnr' => $context['pnr'] ?? null,
            'reference_no' => $context['reference_no'] ?? null,
            'seat_no' => $context['seat_no'] ?? null,
            'amount' => 0,
            'reason' => $context['reason'] ?? null,
            'snapshot' => [
                'audit_type' => 'transaction_authorization',
                'authorization_result' => 'Failed',
                'transaction_type' => $transactionType,
                'failure' => $failure,
                'performed_by_admin_id' => $performedBy?->id,
                'performed_by_name' => $performedBy?->name,
                'authorization_code_owner_id' => $codeOwner?->id,
                'authorization_code_owner_name' => $codeOwner?->name,
                'attempted_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
