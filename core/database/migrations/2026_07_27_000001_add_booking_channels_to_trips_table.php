<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->boolean('online_booking_enabled')->default(true);
            $table->boolean('kiosk_booking_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'online_booking_enabled',
                'kiosk_booking_enabled',
            ]);
        });
    }
};
