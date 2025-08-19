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
            $table->string('disabled_seats')->nullable()->after('deck_seats');
            $table->string('prefixes')->nullable()->after('deck_seats');
            $table->string('last_row')->nullable()->after('deck_seats');
            $table->integer('cr_row')->nullable()->after('deck_seats');
            $table->string('cr_position')->nullable()->after('deck_seats');
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
