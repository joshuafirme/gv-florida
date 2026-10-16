<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LOCK_WAIT_SECONDS = 30;

    private const LOCK_RETRY_ATTEMPTS = 4;

    private const LOCK_RETRY_DELAY_MICROSECONDS = 2_000_000;

    private const INDEXES = [
        ['booked_tickets', 'booked_tickets_pnr_index', ['pnr_number']],
        ['booked_tickets', 'booked_tickets_journey_trip_index', ['date_of_journey', 'trip_id']],
        ['booked_tickets', 'booked_tickets_trip_index', ['trip_id']],
        ['deposits', 'deposits_trx_index', ['trx']],
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->addMissingMysqlIndexes();

            return;
        }

        foreach (self::INDEXES as [$table, $index, $columns]) {
            $this->addIndexIfMissing($table, $index, $columns);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->dropPresentMysqlIndexes();

            return;
        }

        foreach (array_reverse(self::INDEXES) as [$table, $index]) {
            $this->dropIndexIfPresent($table, $index);
        }
    }

    private function addMissingMysqlIndexes(): void
    {
        $clausesByTable = [];

        foreach (self::INDEXES as [$table, $index, $columns]) {
            if (Schema::hasIndex($table, $index)) {
                continue;
            }

            $columnList = implode(', ', array_map(fn ($column) => "`{$column}`", $columns));
            $clausesByTable[$table][] = "ADD INDEX `{$index}` ({$columnList})";
        }

        foreach ($clausesByTable as $table => $clauses) {
            $this->runOnlineDdl(
                "ALTER TABLE `{$table}` " . implode(', ', $clauses) . ', ALGORITHM=INPLACE, LOCK=NONE'
            );
        }
    }

    private function dropPresentMysqlIndexes(): void
    {
        $clausesByTable = [];

        foreach (array_reverse(self::INDEXES) as [$table, $index]) {
            if (!Schema::hasIndex($table, $index)) {
                continue;
            }

            $clausesByTable[$table][] = "DROP INDEX `{$index}`";
        }

        foreach ($clausesByTable as $table => $clauses) {
            $this->runOnlineDdl(
                "ALTER TABLE `{$table}` " . implode(', ', $clauses) . ', ALGORITHM=INPLACE, LOCK=NONE'
            );
        }
    }

    private function addIndexIfMissing(string $table, string $index, array $columns): void
    {
        if (Schema::hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index) {
            $blueprint->index($columns, $index);
        });
    }

    private function dropIndexIfPresent(string $table, string $index): void
    {
        if (!Schema::hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index) {
            $blueprint->dropIndex($index);
        });
    }

    private function runOnlineDdl(string $statement): void
    {
        $originalTimeout = (int) DB::selectOne(
            'SELECT @@SESSION.lock_wait_timeout AS lock_wait_timeout'
        )->lock_wait_timeout;

        DB::statement('SET SESSION lock_wait_timeout = ' . self::LOCK_WAIT_SECONDS);

        try {
            for ($attempt = 1; $attempt <= self::LOCK_RETRY_ATTEMPTS; $attempt++) {
                try {
                    DB::statement($statement);

                    return;
                } catch (QueryException $exception) {
                    if (!$this->isLockWaitTimeout($exception) || $attempt === self::LOCK_RETRY_ATTEMPTS) {
                        throw $exception;
                    }

                    usleep(self::LOCK_RETRY_DELAY_MICROSECONDS);
                }
            }
        } finally {
            DB::statement("SET SESSION lock_wait_timeout = {$originalTimeout}");
        }
    }

    private function isLockWaitTimeout(QueryException $exception): bool
    {
        return (int) ($exception->errorInfo[1] ?? 0) === 1205;
    }
};
