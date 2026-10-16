<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('user_discounts', 'user_discounts_passenger_name_index')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            Schema::table('user_discounts', function (Blueprint $table) {
                $table->index('passenger_name', 'user_discounts_passenger_name_index');
            });

            return;
        }

        $this->runOnlineDdl(
            'ALTER TABLE `user_discounts` '
            . 'ADD INDEX `user_discounts_passenger_name_index` (`passenger_name`), '
            . 'ALGORITHM=INPLACE, LOCK=NONE'
        );
    }

    public function down(): void
    {
        if (!Schema::hasIndex('user_discounts', 'user_discounts_passenger_name_index')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            Schema::table('user_discounts', function (Blueprint $table) {
                $table->dropIndex('user_discounts_passenger_name_index');
            });

            return;
        }

        $this->runOnlineDdl(
            'ALTER TABLE `user_discounts` '
            . 'DROP INDEX `user_discounts_passenger_name_index`, '
            . 'ALGORITHM=INPLACE, LOCK=NONE'
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
