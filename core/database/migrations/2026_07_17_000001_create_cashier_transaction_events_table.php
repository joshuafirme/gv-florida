<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_transaction_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins');
            $table->unsignedBigInteger('booked_ticket_id')->nullable()->index();
            $table->unsignedBigInteger('slip_series_number_id')->nullable()->index();
            $table->unsignedBigInteger('deposit_id')->nullable()->index();
            $table->string('event_key', 191)->unique();
            $table->string('status', 30);
            $table->timestamp('processed_at')->index();
            $table->string('source', 30)->nullable();
            $table->string('pnr', 50)->nullable();
            $table->string('reference_no', 50)->nullable();
            $table->string('passenger_name')->nullable();
            $table->string('passenger_type', 100)->nullable();
            $table->string('passenger_id', 100)->nullable();
            $table->date('journey_date')->nullable();
            $table->time('departure_time')->nullable();
            $table->string('trip_class')->nullable();
            $table->string('trip_route')->nullable();
            $table->string('seat_no', 30)->nullable();
            $table->string('drop_off')->nullable();
            $table->string('km_post', 100)->nullable();
            $table->string('payment_method', 100)->nullable();
            $table->decimal('base_fare', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->text('reason')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'processed_at'], 'cashier_events_admin_processed_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_transaction_events');
    }
};
