<?php

use App\Constants\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->unsignedInteger('booked_ticket_id')->nullable()->default(null)->change();
        });

        DB::table('deposits')
            ->where('booked_ticket_id', 0)
            ->update(['booked_ticket_id' => null]);

        $duplicateTicketIds = DB::table('deposits')
            ->whereNotNull('booked_ticket_id')
            ->groupBy('booked_ticket_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('booked_ticket_id');

        foreach ($duplicateTicketIds as $ticketId) {
            DB::transaction(function () use ($ticketId) {
                $deposits = DB::table('deposits')
                    ->where('booked_ticket_id', $ticketId)
                    ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [Status::PAYMENT_SUCCESS])
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->get();

                $winner = $deposits->first();
                $duplicateIds = $deposits->skip(1)->pluck('id');

                if (!$winner || $duplicateIds->isEmpty()) {
                    return;
                }

                if (Schema::hasTable('user_discounts')) {
                    $winnerHasDiscount = DB::table('user_discounts')
                        ->where('deposit_id', $winner->id)
                        ->exists();
                    $duplicateDiscounts = DB::table('user_discounts')
                        ->whereIn('deposit_id', $duplicateIds)
                        ->orderByDesc('id')
                        ->get();

                    if (!$winnerHasDiscount && $duplicateDiscounts->isNotEmpty()) {
                        $discount = $duplicateDiscounts->shift();
                        DB::table('user_discounts')
                            ->where('id', $discount->id)
                            ->update(['deposit_id' => $winner->id]);
                    }

                    DB::table('user_discounts')
                        ->whereIn('deposit_id', $duplicateIds)
                        ->delete();
                }

                if (Schema::hasTable('cashier_transaction_events')) {
                    DB::table('cashier_transaction_events')
                        ->whereIn('deposit_id', $duplicateIds)
                        ->update(['deposit_id' => $winner->id]);
                }

                DB::table('deposits')->whereIn('id', $duplicateIds)->delete();
            });
        }

        Schema::table('deposits', function (Blueprint $table) {
            $table->unique('booked_ticket_id', 'deposits_booked_ticket_unique');
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropUnique('deposits_booked_ticket_unique');
        });

        DB::table('deposits')
            ->whereNull('booked_ticket_id')
            ->update(['booked_ticket_id' => 0]);

        Schema::table('deposits', function (Blueprint $table) {
            $table->unsignedInteger('booked_ticket_id')->default(0)->nullable(false)->change();
        });
    }
};
