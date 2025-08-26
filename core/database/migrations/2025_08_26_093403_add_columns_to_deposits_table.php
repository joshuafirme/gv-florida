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
        Schema::table('deposits', function (Blueprint $table) {
            $table->string('expiry_limit')->nullable()->after('method_code');
            $table->string('pay_reference')->nullable()->after('method_code');
            $table->string('pchannel')->nullable()->after('method_code');
            $table->string('pmethod')->nullable()->after('method_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            //
        });
    }
};
