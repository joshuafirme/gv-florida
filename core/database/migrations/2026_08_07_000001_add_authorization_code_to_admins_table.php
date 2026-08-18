<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('authorization_code_hash')->nullable()->after('passcode');
            $table->char('authorization_code_lookup', 64)->nullable()->unique()->after('authorization_code_hash');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropUnique(['authorization_code_lookup']);
            $table->dropColumn(['authorization_code_hash', 'authorization_code_lookup']);
        });
    }
};
