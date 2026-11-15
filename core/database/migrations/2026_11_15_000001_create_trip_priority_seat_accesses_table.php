<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_priority_seat_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->date('journey_date');
            $table->boolean('walk_in_enabled')->default(false);
            $table->text('reason');
            $table->foreignId('updated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('authorized_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('authorized_at');
            $table->timestamps();

            $table->unique(
                ['trip_id', 'journey_date'],
                'trip_priority_seat_access_trip_date_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_priority_seat_accesses');
    }
};
