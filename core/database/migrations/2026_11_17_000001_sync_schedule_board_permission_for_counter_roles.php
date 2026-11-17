<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COUNTER_PERMISSION = 'admin.counter.index';
    private const SCHEDULE_BOARD_PERMISSION = 'admin.counter.schedule-board.manage';

    public function up(): void
    {
        DB::table('user_roles')->orderBy('id')->get()->each(function ($role) {
            $permissions = json_decode($role->permissions, true) ?: [];

            if (!in_array(self::COUNTER_PERMISSION, $permissions, true)
                || in_array(self::SCHEDULE_BOARD_PERMISSION, $permissions, true)) {
                return;
            }

            $permissions[] = self::SCHEDULE_BOARD_PERMISSION;

            DB::table('user_roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_unique($permissions))),
            ]);
        });
    }

    public function down(): void
    {
        // Preserve permissions that may have been assigned explicitly after this migration.
    }
};
