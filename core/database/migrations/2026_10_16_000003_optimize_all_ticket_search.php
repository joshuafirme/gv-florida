<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LOCK_WAIT_SECONDS = 10;

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
            // The optimized query works without these indexes, so a busy table must not block deployment.
            $this->runOnlineDdl(
                "ALTER TABLE `{$table}` " . implode(', ', $clauses) . ', ALGORITHM=INPLACE, LOCK=NONE',
                true
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

    private function runOnlineDdl(string $statement, bool $skipLockTimeout = false): void
    {
        $originalTimeout = (int) DB::selectOne(
            'SELECT @@SESSION.lock_wait_timeout AS lock_wait_timeout'
        )->lock_wait_timeout;

        DB::statement('SET SESSION lock_wait_timeout = ' . self::LOCK_WAIT_SECONDS);

        try {
            DB::statement($statement);
        } catch (QueryException $exception) {
            if (!$skipLockTimeout || !$this->isLockWaitTimeout($exception)) {
                throw $exception;
            }

            Log::warning('Skipped optional All Tickets search indexes because the table is metadata-locked.', [
                'mysql_error' => $exception->errorInfo[1] ?? null,
                'sql_state' => $exception->errorInfo[0] ?? null,
            ]);
        } finally {
            DB::statement("SET SESSION lock_wait_timeout = {$originalTimeout}");
        }
    }

    private function isLockWaitTimeout(QueryException $exception): bool
    {
        return (int) ($exception->errorInfo[1] ?? 0) === 1205;
    }
};
