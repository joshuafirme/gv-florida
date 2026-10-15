<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSION = 'admin.counter.schedule-board.manage';

    public function up(): void
    {
        Schema::create('schedule_board_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counter_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('show_all_trips')->default(true);
            $table->unsignedSmallInteger('departure_grace_minutes')->default(60);
            $table->timestamps();
        });

        Schema::create('schedule_board_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['counter_id', 'trip_id']);
        });

        Schema::create('schedule_board_advertisements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counter_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160)->nullable();
            $table->string('image');
            $table->dateTime('starts_at')->nullable()->index();
            $table->dateTime('ends_at')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('schedule_board_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counter_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160)->nullable();
            $table->text('content');
            $table->dateTime('starts_at')->nullable()->index();
            $table->dateTime('ends_at')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
        });

        DB::table('user_roles')->orderBy('id')->get()->each(function ($role) {
            $permissions = json_decode($role->permissions, true) ?: [];

            if (in_array('admin.counter.index', $permissions, true)
                && !in_array(self::PERMISSION, $permissions, true)) {
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

        Schema::dropIfExists('schedule_board_notices');
        Schema::dropIfExists('schedule_board_advertisements');
        Schema::dropIfExists('schedule_board_trips');
        Schema::dropIfExists('schedule_board_settings');
    }
};
