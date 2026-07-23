<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_seat_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->date('date_of_journey');
            $table->string('seat_no', 30);
            $table->boolean('is_active')->default(true)->index();
            $table->text('reason');
            $table->foreignId('locked_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('lock_authorized_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('locked_at');
            $table->text('unlock_reason')->nullable();
            $table->foreignId('unlocked_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('unlock_authorized_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['trip_id', 'date_of_journey', 'seat_no'],
                'admin_seat_locks_trip_date_seat_unique'
            );
            $table->index(
                ['trip_id', 'date_of_journey', 'is_active'],
                'admin_seat_locks_trip_date_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_seat_locks');
    }
};
