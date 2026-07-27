<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_source_deposit_id')
                ->nullable()
                ->after('is_rebooked')
                ->index();
        });

        if (!Schema::hasTable('cashier_transaction_events')) {
            return;
        }

        DB::table('deposits')
            ->where('trx', 'like', 'GVF-RB-%')
            ->orderBy('id')
            ->eachById(function ($allocation) {
                $rebookingEvent = DB::table('cashier_transaction_events')
                    ->where('deposit_id', $allocation->id)
                    ->where('status', 'Rebooked')
                    ->orderBy('id')
                    ->first();

                if (!$rebookingEvent?->slip_series_number_id) {
                    return;
                }

                $originalSale = DB::table('cashier_transaction_events')
                    ->where('slip_series_number_id', $rebookingEvent->slip_series_number_id)
                    ->where('status', 'Sold')
                    ->where('deposit_id', '<>', $allocation->id)
                    ->orderBy('processed_at')
                    ->orderBy('id')
                    ->first();

                if (!$originalSale?->deposit_id) {
                    return;
                }

                $originalDeposit = DB::table('deposits')->find($originalSale->deposit_id);
                if (!$originalDeposit) {
                    return;
                }

                DB::table('booked_tickets')
                    ->where('id', $allocation->booked_ticket_id)
                    ->update(['payment_source_deposit_id' => $originalDeposit->id]);

                $monetary = [];
                foreach (['amount', 'charge', 'final_amount', 'method_amount'] as $field) {
                    if (Schema::hasColumn('deposits', $field)) {
                        $monetary[$field] = round(
                            (float) ($originalDeposit->{$field} ?? 0)
                            + (float) ($allocation->{$field} ?? 0),
                            8
                        );
                    }
                }
                DB::table('deposits')->where('id', $originalDeposit->id)->update($monetary);

                $this->restoreDiscount($originalDeposit->id, $allocation->id);

                DB::table('cashier_transaction_events')
                    ->where('deposit_id', $allocation->id)
                    ->where('status', 'Sold')
                    ->delete();

                DB::table('cashier_transaction_events')
                    ->where('deposit_id', $allocation->id)
                    ->where('status', 'Rebooked')
                    ->get()
                    ->each(function ($event) use ($originalDeposit) {
                        $snapshot = json_decode((string) $event->snapshot, true) ?: [];
                        $snapshot['deposit_id'] = $originalDeposit->id;

                        DB::table('cashier_transaction_events')
                            ->where('id', $event->id)
                            ->update([
                                'deposit_id' => $originalDeposit->id,
                                'snapshot' => json_encode($snapshot),
                            ]);
                    });

                DB::table('deposits')->where('id', $allocation->id)->delete();
            });

        $this->refreshRebookingFlags();
    }

    public function down(): void
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            $table->dropIndex(['payment_source_deposit_id']);
            $table->dropColumn('payment_source_deposit_id');
        });
    }

    private function restoreDiscount(int $originalDepositId, int $allocationDepositId): void
    {
        if (!Schema::hasTable('user_discounts')) {
            return;
        }

        $original = DB::table('user_discounts')->where('deposit_id', $originalDepositId)->first();
        $allocation = DB::table('user_discounts')->where('deposit_id', $allocationDepositId)->first();

        if (!$allocation) {
            return;
        }

        if (!$original) {
            DB::table('user_discounts')
                ->where('id', $allocation->id)
                ->update(['deposit_id' => $originalDepositId]);
            return;
        }

        $originalManifest = json_decode((string) $original->passenger_manifest, true) ?: [];
        $allocationManifest = json_decode((string) $allocation->passenger_manifest, true) ?: [];
        $manifest = collect(array_merge($originalManifest, $allocationManifest))
            ->keyBy(fn ($passenger) => (string) ($passenger['seat'] ?? uniqid('', true)))
            ->values()
            ->all();

        DB::table('user_discounts')->where('id', $original->id)->update([
            'amount' => round((float) $original->amount + (float) $allocation->amount, 8),
            'passenger_manifest' => $manifest ? json_encode($manifest) : null,
        ]);

        DB::table('user_discounts')->where('id', $allocation->id)->delete();
    }

    private function refreshRebookingFlags(): void
    {
        DB::table('booked_tickets')
            ->where('is_rebooked', 1)
            ->orderBy('id')
            ->eachById(function ($ticket) {
                $hasRebookedSlip = DB::table('slip_series_numbers')
                    ->where('booked_ticket_id', $ticket->id)
                    ->whereExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('cashier_transaction_events')
                            ->whereColumn(
                                'cashier_transaction_events.slip_series_number_id',
                                'slip_series_numbers.id'
                            )
                            ->where('cashier_transaction_events.status', 'Rebooked');
                    })
                    ->exists();

                DB::table('booked_tickets')
                    ->where('id', $ticket->id)
                    ->update(['is_rebooked' => $hasRebookedSlip ? 1 : 0]);
            });
    }
};
