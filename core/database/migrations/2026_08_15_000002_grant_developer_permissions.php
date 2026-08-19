<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        'admin.developer.payment.transactions',
        'admin.developer.webhook.logs',
    ];

    public function up(): void
    {
        DB::table('user_roles')->orderBy('id')->get()->each(function ($role) {
            $permissions = json_decode($role->permissions, true) ?: [];

            if (! in_array('admin.setting.system', $permissions, true)) {
                return;
            }

            $permissions = array_values(array_unique(array_merge($permissions, self::PERMISSIONS)));

            DB::table('user_roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('user_roles')->orderBy('id')->get()->each(function ($role) {
            $permissions = json_decode($role->permissions, true) ?: [];
            $permissions = array_values(array_diff($permissions, self::PERMISSIONS));

            DB::table('user_roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions),
            ]);
        });
    }
};
