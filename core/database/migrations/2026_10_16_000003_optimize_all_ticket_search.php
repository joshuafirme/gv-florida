<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEXES = [
        ['booked_tickets', 'booked_tickets_pnr_index', ['pnr_number']],
        ['booked_tickets', 'booked_tickets_journey_trip_index', ['date_of_journey', 'trip_id']],
        ['booked_tickets', 'booked_tickets_trip_index', ['trip_id']],
        ['deposits', 'deposits_trx_index', ['trx']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as [$table, $index, $columns]) {
            $this->addIndexIfMissing($table, $index, $columns);
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::INDEXES) as [$table, $index]) {
            $this->dropIndexIfPresent($table, $index);
        }
    }

    private function addIndexIfMissing(string $table, string $index, array $columns): void
    {
        if (Schema::hasIndex($table, $index)) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $index) {
                $blueprint->index($columns, $index);
            });

            return;
        }

        $columnList = implode(', ', array_map(fn ($column) => "`{$column}`", $columns));
        $this->runOnlineDdl(
            "ALTER TABLE `{$table}` ADD INDEX `{$index}` ({$columnList}), ALGORITHM=INPLACE, LOCK=NONE"
        );
    }

    private function dropIndexIfPresent(string $table, string $index): void
    {
        if (!Schema::hasIndex($table, $index)) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->dropIndex($index);
            });

            return;
        }

        $this->runOnlineDdl(
            "ALTER TABLE `{$table}` DROP INDEX `{$index}`, ALGORITHM=INPLACE, LOCK=NONE"
        );
    }

    private function runOnlineDdl(string $statement): void
    {
        $originalTimeout = (int) DB::selectOne(
            'SELECT @@SESSION.lock_wait_timeout AS lock_wait_timeout'
        )->lock_wait_timeout;

        DB::statement('SET SESSION lock_wait_timeout = 15');

        try {
            DB::statement($statement);
        } finally {
            DB::statement("SET SESSION lock_wait_timeout = {$originalTimeout}");
        }
    }
};
