<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE trips MODIFY trip_status ENUM('on_time','preparing','boarding','departed','cancelled','breakdown','delayed','arrived') NOT NULL DEFAULT 'on_time'"
        );
    }

    public function down(): void
    {
        DB::table('trips')
            ->where('trip_status', 'preparing')
            ->update(['trip_status' => 'on_time']);
        DB::table('trips')
            ->where('trip_status', 'breakdown')
            ->update(['trip_status' => 'delayed']);

        DB::statement(
            "ALTER TABLE trips MODIFY trip_status ENUM('on_time','boarding','delayed','cancelled','departed','arrived') NOT NULL DEFAULT 'on_time'"
        );
    }
};
