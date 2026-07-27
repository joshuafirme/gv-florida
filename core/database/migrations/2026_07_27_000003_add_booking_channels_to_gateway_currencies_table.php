<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateway_currencies', function (Blueprint $table) {
            $table->boolean('online_enabled')->default(true)->after('gateway_alias');
            $table->boolean('kiosk_enabled')->default(true)->after('online_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('gateway_currencies', function (Blueprint $table) {
            $table->dropColumn(['online_enabled', 'kiosk_enabled']);
        });
    }
};
