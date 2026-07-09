<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booked_ticket_id')->constrained('booked_tickets');
            $table->foreignId('slip_series_number_id')->unique()->constrained('slip_series_numbers');
            $table->foreignId('processed_by_admin_id')->constrained('admins');
            $table->foreignId('authorized_by_admin_id')->constrained('admins');
            $table->decimal('original_fare', 12, 2);
            $table->decimal('refund_amount', 12, 2);
            $table->string('reason', 100);
            $table->text('remarks');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_refunds');
    }
};
