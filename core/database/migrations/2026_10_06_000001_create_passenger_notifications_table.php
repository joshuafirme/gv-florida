<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passenger_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('booked_ticket_id')->nullable()->index();
            $table->unsignedBigInteger('sent_by_admin_id')->nullable()->index();
            $table->string('event_type', 60)->index();
            $table->string('dedupe_key', 191)->unique();
            $table->string('title');
            $table->text('message');
            $table->string('channel', 60)->default('Pusher / In-app');
            $table->string('sent_by', 100)->default('System');
            $table->string('status', 30)->default('Sent');
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passenger_notifications');
    }
};
