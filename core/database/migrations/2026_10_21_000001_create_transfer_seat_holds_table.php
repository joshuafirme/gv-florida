<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_seat_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slip_series_number_id')->constrained('slip_series_numbers')->cascadeOnDelete();
            $table->foreignId('booked_ticket_id')->constrained('booked_tickets')->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->date('date_of_journey');
            $table->foreignId('pickup_point_id')->constrained('counters')->cascadeOnDelete();
            $table->foreignId('dropping_point_id')->constrained('counters')->cascadeOnDelete();
            $table->string('seat_no', 30);
            $table->char('token_hash', 64)->unique();
            $table->foreignId('held_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(
                ['trip_id', 'date_of_journey', 'seat_no', 'expires_at'],
                'transfer_seat_holds_availability_index'
            );
            $table->index(
                ['slip_series_number_id', 'held_by_admin_id', 'expires_at'],
                'transfer_seat_holds_owner_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_seat_holds');
    }
};
