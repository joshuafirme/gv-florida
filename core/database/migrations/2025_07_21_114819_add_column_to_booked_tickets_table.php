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
        Schema::table('booked_tickets', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable()->change();
            $table->foreignId('kiosk_id')->nullable()->after('trip_id')->constrained('kiosks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            //
        });
    }
};
