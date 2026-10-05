<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            $table->index(
                ['status', 'kiosk_id', 'user_id'],
                'booked_tickets_online_validation_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            $table->dropIndex('booked_tickets_online_validation_lookup_index');
        });
    }
};
