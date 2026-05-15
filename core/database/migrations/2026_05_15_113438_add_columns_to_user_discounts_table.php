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
        Schema::table('user_discounts', function (Blueprint $table) {
            $table->string('id_number')->nullable()->after('description');
            $table->string('passenger_name')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_discounts', function (Blueprint $table) {
            //
        });
    }
};
