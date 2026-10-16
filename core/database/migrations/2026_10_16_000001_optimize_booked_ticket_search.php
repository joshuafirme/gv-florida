<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing(
            'booked_tickets',
            'booked_tickets_status_pnr_index',
            ['status', 'pnr_number']
        );
    }

    public function down(): void
    {
        // Remove the earlier version's optional index when a deployment created it before timing out.
        $this->dropIndexIfPresent('cashier_transaction_events', 'cashier_events_slip_status_index');
        $this->dropIndexIfPresent('booked_tickets', 'booked_tickets_status_pnr_index');
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
