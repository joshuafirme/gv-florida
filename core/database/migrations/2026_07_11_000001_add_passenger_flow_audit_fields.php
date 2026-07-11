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
        Schema::table('booked_tickets', function (Blueprint $table) {
            $table->json('passenger_manifest')->nullable()->after('seats');
        });

        Schema::table('user_discounts', function (Blueprint $table) {
            $table->json('passenger_manifest')->nullable()->after('passenger_name');
            $table->string('authorization_method')->nullable()->after('passenger_manifest');
            $table->unsignedBigInteger('authorized_by_admin_id')->nullable()->after('authorization_method');
            $table->string('authorized_by_name')->nullable()->after('authorized_by_admin_id');
            $table->string('authorization_reference')->nullable()->after('authorized_by_name');
            $table->timestamp('authorized_at')->nullable()->after('authorization_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            $table->dropColumn('passenger_manifest');
        });

        Schema::table('user_discounts', function (Blueprint $table) {
            $table->dropColumn([
                'passenger_manifest',
                'authorization_method',
                'authorized_by_admin_id',
                'authorized_by_name',
                'authorization_reference',
                'authorized_at',
            ]);
        });
    }
};
