<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fleet_types', function (Blueprint $table) {
            
            $table->boolean('cr_override_seat')->nullable()->after('cr_row');
            $table->integer('cr_row_covered')->nullable()->after('cr_row');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fleet_types', function (Blueprint $table) {
            //
        });
    }
};
