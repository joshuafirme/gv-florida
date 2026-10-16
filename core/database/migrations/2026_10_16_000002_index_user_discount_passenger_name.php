<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_discounts', function (Blueprint $table) {
            $table->index('passenger_name', 'user_discounts_passenger_name_index');
        });
    }

    public function down(): void
    {
        Schema::table('user_discounts', function (Blueprint $table) {
            $table->dropIndex('user_discounts_passenger_name_index');
        });
    }
};
