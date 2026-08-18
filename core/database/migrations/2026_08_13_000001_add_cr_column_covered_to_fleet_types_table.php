<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_types', function (Blueprint $table) {
            $table->unsignedInteger('cr_column_covered')->nullable()->after('cr_row_covered');
        });

        // The existing field was used as Column Covered before Row Covered was restored.
        DB::table('fleet_types')
            ->whereNotNull('cr_row_covered')
            ->update([
                'cr_column_covered' => DB::raw('cr_row_covered'),
                'cr_row_covered' => 1,
            ]);
    }

    public function down(): void
    {
        DB::table('fleet_types')
            ->whereNotNull('cr_column_covered')
            ->update(['cr_row_covered' => DB::raw('cr_column_covered')]);

        Schema::table('fleet_types', function (Blueprint $table) {
            $table->dropColumn('cr_column_covered');
        });
    }
};
