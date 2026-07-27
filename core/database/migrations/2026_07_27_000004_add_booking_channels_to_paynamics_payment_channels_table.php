<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paynamics_payment_channels', function (Blueprint $table) {
            $table->boolean('online_enabled')->default(true)->after('is_enabled');
            $table->boolean('kiosk_enabled')->default(true)->after('online_enabled');
        });

        $activeBookingGatewayCodes = DB::table('gateway_currencies')
            ->whereIn('gateway_alias', ['cash', 'paynamics'])
            ->pluck('method_code');

        DB::table('gateways')
            ->whereIn('code', $activeBookingGatewayCodes)
            ->update(['status' => 1]);
    }

    public function down(): void
    {
        Schema::table('paynamics_payment_channels', function (Blueprint $table) {
            $table->dropColumn(['online_enabled', 'kiosk_enabled']);
        });
    }
};
