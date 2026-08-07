<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const BOOKED_TICKET_PERMISSION = 'admin.vehicle.ticket.booked';

    private const AUTHORIZATION_PERMISSIONS = [
        'admin.vehicle.ticket.authorize.cancel',
        'admin.vehicle.ticket.authorize.rebook',
        'admin.vehicle.ticket.authorize.refund',
        'admin.vehicle.ticket.authorize.void',
    ];

    public function up(): void
    {
        DB::table('user_roles')->orderBy('id')->get()->each(function ($role) {
            $permissions = json_decode($role->permissions, true) ?: [];

            if (!in_array(self::BOOKED_TICKET_PERMISSION, $permissions, true)) {
                return;
            }

            DB::table('user_roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_unique(array_merge(
                    $permissions,
                    self::AUTHORIZATION_PERMISSIONS
                )))),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('user_roles')->orderBy('id')->get()->each(function ($role) {
            $permissions = json_decode($role->permissions, true) ?: [];

            DB::table('user_roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_filter(
                    $permissions,
                    fn ($permission) => !in_array($permission, self::AUTHORIZATION_PERMISSIONS, true)
                ))),
            ]);
        });
    }
};
