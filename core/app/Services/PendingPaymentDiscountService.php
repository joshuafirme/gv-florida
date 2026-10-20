<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\Admin;
use App\Models\BookedTicket;
use App\Models\Deposit;
use App\Models\Discount;
use App\Models\GatewayCurrency;
use App\Models\UserDiscount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PendingPaymentDiscountService
{
    public function __construct(private FareDiscountService $fareDiscounts)
    {
    }

    public function adjust(
        int $depositId,
        string $seat,
        string $action,
        ?int $discountId,
        ?string $idNumber,
        Admin $admin
    ): Deposit {
        return DB::transaction(function () use ($depositId, $seat, $action, $discountId, $idNumber, $admin) {
            $deposit = Deposit::query()->lockForUpdate()->findOrFail($depositId);

            if ((int) $deposit->status !== Status::PAYMENT_PENDING) {
                throw ValidationException::withMessages([
                    'payment' => 'Discounts can only be adjusted while the payment is pending.',
                ]);
            }

            $ticket = BookedTicket::query()->lockForUpdate()->findOrFail($deposit->booked_ticket_id);

            if (!$ticket->isKioskBooking()) {
                throw ValidationException::withMessages([
                    'payment' => 'Discount adjustments are only available for kiosk bookings.',
                ]);
            }

            $seats = collect($ticket->seats ?: collect($ticket->passenger_manifest ?: [])->pluck('seat'))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->unique()
                ->values();

            if (!$seats->contains($seat)) {
                throw ValidationException::withMessages([
                    'seat' => 'The selected seat does not belong to this payment.',
                ]);
            }

            $discount = null;
            if ($action === 'apply') {
                $discount = Discount::query()
                    ->whereKey($discountId)
                    ->where('status', Status::ENABLE)
                    ->first();

                if (!$discount) {
                    throw ValidationException::withMessages([
                        'discount_id' => 'Please select an active discount.',
                    ]);
                }
            }

            $existingManifest = collect($ticket->passenger_manifest ?: [])
                ->keyBy(fn ($entry) => trim((string) ($entry['seat'] ?? '')));
            $fallbackFare = (float) ($ticket->unit_price ?: ((float) $deposit->amount / max($seats->count(), 1)));

            $manifest = $seats->map(function ($manifestSeat) use (
                $existingManifest,
                $fallbackFare,
                $seat,
                $action,
                $discount,
                $idNumber
            ) {
                $entry = (array) $existingManifest->get($manifestSeat, []);
                $baseFare = (float) ($entry['base_fare'] ?? $fallbackFare);
                $entry = array_merge([
                    'seat' => $manifestSeat,
                    'name' => '',
                    'passenger_type' => 'regular',
                    'discount_id' => null,
                    'discount_name' => null,
                    'discount_percentage' => 0,
                    'id_number' => null,
                    'base_fare' => getAmount($baseFare),
                    'discount_amount' => 0,
                    'fare' => getAmount($baseFare),
                ], $entry);

                if ($manifestSeat !== $seat) {
                    return $entry;
                }

                if ($action === 'remove') {
                    return array_merge($entry, [
                        'passenger_type' => 'regular',
                        'discount_id' => null,
                        'discount_name' => null,
                        'discount_percentage' => 0,
                        'id_number' => null,
                        'base_fare' => getAmount($baseFare),
                        'discount_amount' => 0,
                        'fare' => getAmount($baseFare),
                    ]);
                }

                $fare = $this->fareDiscounts->discountedFare($baseFare, (float) $discount->percentage);

                return array_merge($entry, [
                    'passenger_type' => 'discounted',
                    'discount_id' => $discount->id,
                    'discount_name' => $discount->name,
                    'discount_percentage' => getAmount($discount->percentage),
                    'id_number' => trim((string) $idNumber),
                    'base_fare' => getAmount($baseFare),
                    'discount_amount' => getAmount($baseFare - $fare),
                    'fare' => getAmount($fare),
                ]);
            })->values()->all();

            $ticket->passenger_manifest = $manifest;
            $ticket->saveQuietly();

            $discountedPassengers = collect($manifest)
                ->where('passenger_type', 'discounted')
                ->values();
            $discountAmount = (float) $discountedPassengers->sum('discount_amount');
            $subtotal = (float) collect($manifest)->sum('fare');
            $grossAmount = (float) collect($manifest)->sum('base_fare');
            $gatewayCurrency = GatewayCurrency::query()
                ->where('method_code', $deposit->method_code)
                ->where('currency', $deposit->method_currency)
                ->first();

            if ($gatewayCurrency) {
                $charge = (float) $gatewayCurrency->fixed_charge
                    + ($subtotal * (float) $gatewayCurrency->percent_charge / 100);
                $rate = (float) ($gatewayCurrency->rate ?: $deposit->rate ?: 1);
            } else {
                $rate = (float) ($deposit->rate ?: 1);
                $previousSubtotal = max((float) $deposit->amount - (float) ($deposit->userDiscount?->amount ?? 0), 0);
                $charge = max(((float) $deposit->final_amount / max($rate, 0.000001)) - $previousSubtotal, 0);
            }

            $deposit->amount = getAmount($grossAmount);
            $deposit->charge = getAmount($charge);
            $deposit->rate = $rate;
            $deposit->final_amount = getAmount(($subtotal + $charge) * $rate);
            $deposit->save();

            if ($discountedPassengers->isEmpty()) {
                UserDiscount::query()->where('deposit_id', $deposit->id)->delete();
            } else {
                $summary = UserDiscount::query()->firstOrNew(['deposit_id' => $deposit->id]);
                $summary->percentage = $discountedPassengers->avg('discount_percentage') ?: 0;
                $summary->amount = getAmount($discountAmount);
                $summary->description = $discountedPassengers->pluck('discount_name')->filter()->unique()->implode(', ');
                $summary->id_number = $discountedPassengers->pluck('id_number')->filter()->implode(', ');
                $summary->passenger_name = $discountedPassengers->pluck('name')->filter()->implode(', ');
                $summary->passenger_manifest = $discountedPassengers->all();
                $summary->authorization_method = 'Pending Payment POS';
                $summary->authorized_by_admin_id = $admin->id;
                $summary->authorized_by_name = $admin->name ?: $admin->username;
                $summary->authorization_reference = $deposit->trx;
                $summary->authorized_at = now();
                $summary->save();
            }

            return $deposit->fresh();
        });
    }
}
