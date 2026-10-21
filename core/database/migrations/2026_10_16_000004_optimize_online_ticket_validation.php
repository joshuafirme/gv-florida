<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'deposits';

    private const INDEX = 'deposits_booked_ticket_status_index';

    private const LOCK_WAIT_SECONDS = 10;

    public function up(): void
    {
        if (Schema::hasIndex(self::TABLE, self::INDEX)) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->index(['booked_ticket_id', 'status'], self::INDEX);
            });

            return;
        }

        $this->runOnlineDdl(
            'ALTER TABLE `deposits` '
            . 'ADD INDEX `deposits_booked_ticket_status_index` (`booked_ticket_id`, `status`), '
            . 'ALGORITHM=INPLACE, LOCK=NONE',
            true
        );
    }

    public function down(): void
    {
        if (!Schema::hasIndex(self::TABLE, self::INDEX)) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropIndex(self::INDEX);
            });

            return;
        }

        $this->runOnlineDdl(
            'ALTER TABLE `deposits` '
            . 'DROP INDEX `deposits_booked_ticket_status_index`, '
            . 'ALGORITHM=INPLACE, LOCK=NONE'
        );
    }

    private function runOnlineDdl(string $statement, bool $skipLockTimeout = false): void
    {
        $originalTimeout = (int) DB::selectOne(
            'SELECT @@SESSION.lock_wait_timeout AS lock_wait_timeout'
        )->lock_wait_timeout;

        DB::statement('SET SESSION lock_wait_timeout = ' . self::LOCK_WAIT_SECONDS);

        try {
            DB::statement($statement);
        } catch (QueryException $exception) {
            if (!$skipLockTimeout || (int) ($exception->errorInfo[1] ?? 0) !== 1205) {
                throw $exception;
            }

            Log::warning('Skipped the optional Online Ticket Validation payment index because deposits is metadata-locked.', [
                'mysql_error' => $exception->errorInfo[1] ?? null,
                'sql_state' => $exception->errorInfo[0] ?? null,
            ]);
        } finally {
            DB::statement("SET SESSION lock_wait_timeout = {$originalTimeout}");
        }
    }
};
