<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSION = 'admin.report.shift.end';

    public function up(): void
    {
        DB::table('user_roles')->orderBy('id')->get()->each(function ($role) {
            $permissions = json_decode($role->permissions, true) ?: [];
            $reportRoles = [
                'admin.report.transaction',
                'admin.deposit.pending',
                'admin.vehicle.ticket.booked',
            ];

            if (array_intersect($reportRoles, $permissions) && !in_array(self::PERMISSION, $permissions, true)) {
                $permissions[] = self::PERMISSION;
                DB::table('user_roles')->where('id', $role->id)->update([
                    'permissions' => json_encode(array_values(array_unique($permissions))),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('user_roles')->orderBy('id')->get()->each(function ($role) {
            $permissions = json_decode($role->permissions, true) ?: [];
            $permissions = array_values(array_filter(
                $permissions,
                fn ($permission) => $permission !== self::PERMISSION
            ));

            DB::table('user_roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
            ]);
        });
    }
};
