<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_channel_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->date('journey_date');
            $table->string('channel', 20);
            $table->boolean('is_enabled');
            $table->text('reason')->nullable();
            $table->foreignId('performed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('authorized_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['trip_id', 'journey_date', 'channel'],
                'trip_channel_availability_unique'
            );
            $table->index(
                ['trip_id', 'channel', 'journey_date', 'is_enabled'],
                'trip_channel_availability_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_channel_availabilities');
    }
};
