<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_cancellations', function (Blueprint $table) {
            $table->text('reason')->change();
            $table->text('remarks')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('ticket_cancellations')
            ->whereNull('remarks')
            ->update(['remarks' => DB::raw('reason')]);
        DB::statement('UPDATE ticket_cancellations SET reason = LEFT(reason, 100)');

        Schema::table('ticket_cancellations', function (Blueprint $table) {
            $table->string('reason', 100)->change();
            $table->text('remarks')->nullable(false)->change();
        });
    }
};
