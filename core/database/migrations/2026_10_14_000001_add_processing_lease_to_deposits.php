<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->foreignId('processing_admin_id')
                ->nullable()
                ->after('completed_at')
                ->constrained('admins')
                ->nullOnDelete();
            $table->string('processing_token', 64)->nullable()->after('processing_admin_id')->index();
            $table->timestamp('processing_started_at')->nullable()->after('processing_token');
            $table->timestamp('processing_lease_expires_at')->nullable()->after('processing_started_at');
            $table->index(
                ['status', 'processing_lease_expires_at'],
                'deposits_status_processing_lease_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropIndex('deposits_status_processing_lease_index');
            $table->dropIndex(['processing_token']);
            $table->dropConstrainedForeignId('processing_admin_id');
            $table->dropColumn([
                'processing_token',
                'processing_started_at',
                'processing_lease_expires_at',
            ]);
        });
    }
};
