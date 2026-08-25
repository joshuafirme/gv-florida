<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'admin.online.ticket.validation.index',
        'admin.online.ticket.validation.discount',
    ];

    public function up(): void
    {
        Schema::create('online_ticket_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slip_series_number_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('booked_ticket_id')->index();
            $table->unsignedBigInteger('deposit_id')->nullable()->index();
            $table->unsignedBigInteger('validated_by_admin_id')->nullable();
            $table->timestamp('validated_at')->nullable()->index();
            $table->foreignId('discount_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('discount_applied_by_admin_id')->nullable();
            $table->unsignedBigInteger('discount_authorized_by_admin_id')->nullable();
            $table->timestamp('discount_authorized_at')->nullable();
            $table->decimal('original_fare', 14, 2)->default(0);
            $table->decimal('discount_percentage', 8, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('net_fare', 14, 2)->default(0);
            $table->string('passenger_id', 100)->nullable();
            $table->text('reason')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->timestamps();

            $table->foreign('validated_by_admin_id', 'otv_validated_admin_fk')
                ->references('id')->on('admins')->nullOnDelete();
            $table->foreign('discount_applied_by_admin_id', 'otv_discount_applied_admin_fk')
                ->references('id')->on('admins')->nullOnDelete();
            $table->foreign('discount_authorized_by_admin_id', 'otv_discount_auth_admin_fk')
                ->references('id')->on('admins')->nullOnDelete();
        });

        DB::table('user_roles')->orderBy('id')->get()->each(function ($role) {
            $permissions = json_decode($role->permissions, true) ?: [];

            if (!in_array('admin.vehicle.ticket.booked', $permissions, true)) {
                return;
            }

            DB::table('user_roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_unique(array_merge($permissions, self::PERMISSIONS)))),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_ticket_validations');

        DB::table('user_roles')->orderBy('id')->get()->each(function ($role) {
            $permissions = json_decode($role->permissions, true) ?: [];
            $permissions = array_values(array_filter(
                $permissions,
                fn ($permission) => !in_array($permission, self::PERMISSIONS, true)
            ));

            DB::table('user_roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
            ]);
        });
    }
};
