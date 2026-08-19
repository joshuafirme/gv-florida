<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paynamics_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deposit_id')->nullable()->index();
            $table->string('provider', 50)->default('Paynamics')->index();
            $table->string('event_type', 100)->nullable()->index();
            $table->string('request_id', 100)->nullable()->index();
            $table->string('original_transaction_id', 100)->nullable()->index();
            $table->string('pay_reference', 100)->nullable()->index();
            $table->string('status', 20)->default('received')->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('payload');
            $table->json('response')->nullable();
            $table->json('headers')->nullable();
            $table->text('error_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('received_at')->useCurrent()->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paynamics_webhook_logs');
    }
};
