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
                ['status', 'pnr_number'],
                'booked_tickets_status_pnr_index'
            );
        });

        Schema::table('cashier_transaction_events', function (Blueprint $table) {
            $table->index(
                ['slip_series_number_id', 'status'],
                'cashier_events_slip_status_index'
            );
        });

    }

    public function down(): void
    {
        Schema::table('cashier_transaction_events', function (Blueprint $table) {
            $table->dropIndex('cashier_events_slip_status_index');
        });

        Schema::table('booked_tickets', function (Blueprint $table) {
            $table->dropIndex('booked_tickets_status_pnr_index');
        });
    }
};
