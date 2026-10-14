<?php

use App\Constants\Status;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LIVE_RECORDING_TOLERANCE_SECONDS = 600;

    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('status')->index();
        });

        DB::table('deposits')
            ->where('status', Status::PAYMENT_SUCCESS)
            ->select(['id', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(500, function ($deposits) {
                $events = DB::table('cashier_transaction_events')
                    ->whereIn('deposit_id', $deposits->pluck('id'))
                    ->where('status', 'Sold')
                    ->whereNotNull('processed_at')
                    ->get(['deposit_id', 'processed_at', 'created_at'])
                    ->groupBy('deposit_id');

                foreach ($deposits as $deposit) {
                    $recordedAtCompletion = $events->get($deposit->id, collect())
                        ->first(function ($event) {
                            if (!$event->created_at || !$event->processed_at) {
                                return false;
                            }

                            return abs(Carbon::parse($event->created_at)->diffInSeconds(
                                Carbon::parse($event->processed_at),
                                false
                            )) <= self::LIVE_RECORDING_TOLERANCE_SECONDS;
                        });

                    // Live Sold events preserve the true completion time. Events created
                    // much later were report backfills and inherited mutable updated_at;
                    // for those legacy rows, booking creation is the safest immutable
                    // boundary available before completed_at existed.
                    $completedAt = $recordedAtCompletion?->processed_at
                        ?: $deposit->created_at
                        ?: $deposit->updated_at;

                    DB::table('deposits')
                        ->where('id', $deposit->id)
                        ->update(['completed_at' => $completedAt]);

                    DB::table('cashier_transaction_events')
                        ->where('deposit_id', $deposit->id)
                        ->where('status', 'Sold')
                        ->update(['processed_at' => $completedAt]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropIndex(['completed_at']);
            $table->dropColumn('completed_at');
        });
    }
};
