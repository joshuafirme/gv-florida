<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_priority_seat_accesses', function (Blueprint $table) {
            $table->boolean('walk_in_enabled')->default(true)->change();
        });
    }

    public function down(): void
    {
        Schema::table('trip_priority_seat_accesses', function (Blueprint $table) {
            $table->boolean('walk_in_enabled')->default(false)->change();
        });
    }
};
