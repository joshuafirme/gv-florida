<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('icon', 40)->default('info');
            $table->text('content');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('pet_friendly_trip_codes', function (Blueprint $table) {
            $table->id();
            $table->string('place', 120);
            $table->string('code', 60)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_friendly_trip_codes');
        Schema::dropIfExists('booking_reminders');
    }
};
