<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleet_types', function (Blueprint $table) {
            $table->json('custom_seat_layout')->nullable()->after('seat_layout');
        });
    }

    public function down(): void
    {
        Schema::table('fleet_types', function (Blueprint $table) {
            $table->dropColumn('custom_seat_layout');
        });
    }
};
