<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->index(['status', 'expiry_limit'], 'deposits_status_expiry_limit_index');
            $table->index(['status', 'created_at'], 'deposits_status_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropIndex('deposits_status_expiry_limit_index');
            $table->dropIndex('deposits_status_created_at_index');
        });
    }
};
