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
        Schema::create('slip_series_numbers', function (Blueprint $table) {
            $table->bigIncrements('id')->startingValue(100000)->unique();
            $table->string('seat');
            $table->foreignId('booked_ticket_id')->constrained('booked_tickets');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slip_series');
    }
};
