<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE trips MODIFY trip_status ENUM('on_time','boarding','delayed','cancelled','departed','arrived') NOT NULL DEFAULT 'on_time'");

        Schema::table('assigned_vehicles', function (Blueprint $table) {
            $table->text('remarks')->nullable()->after('end_at');
        });
    }

    public function down(): void
    {
        DB::table('trips')
            ->whereIn('trip_status', ['departed', 'arrived'])
            ->update(['trip_status' => 'on_time']);

        DB::statement("ALTER TABLE trips MODIFY trip_status ENUM('on_time','boarding','delayed','cancelled') NOT NULL DEFAULT 'on_time'");

        Schema::table('assigned_vehicles', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};
