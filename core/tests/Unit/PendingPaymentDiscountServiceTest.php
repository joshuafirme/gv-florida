<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Models\Admin;
use App\Models\BookedTicket;
use App\Models\Deposit;
use App\Models\Discount;
use App\Models\UserDiscount;
use App\Services\FareDiscountService;
use App\Services\PendingPaymentDiscountService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PendingPaymentDiscountServiceTest extends TestCase
{
    private PendingPaymentDiscountService $service;
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('booked_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kiosk_id')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->text('seats')->nullable();
            $table->text('passenger_manifest')->nullable();
            $table->timestamps();
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booked_ticket_id');
            $table->string('trx')->nullable();
            $table->unsignedInteger('method_code')->nullable();
            $table->string('method_currency')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('charge', 12, 2)->default(0);
            $table->decimal('rate', 12, 6)->default(1);
            $table->decimal('final_amount', 12, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(Status::PAYMENT_PENDING);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('percentage', 5, 2);
            $table->unsignedTinyInteger('status')->default(Status::ENABLE);
            $table->timestamps();
        });

        Schema::create('gateway_currencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('method_code');
            $table->string('currency');
            $table->decimal('fixed_charge', 12, 2)->default(0);
            $table->decimal('percent_charge', 8, 2)->default(0);
            $table->decimal('rate', 12, 6)->default(1);
            $table->timestamps();
        });

        Schema::create('user_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deposit_id');
            $table->decimal('percentage', 5, 2);
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->string('passenger_name')->nullable();
            $table->string('id_number')->nullable();
            $table->text('passenger_manifest')->nullable();
            $table->string('authorization_method')->nullable();
            $table->unsignedBigInteger('authorized_by_admin_id')->nullable();
            $table->string('authorized_by_name')->nullable();
            $table->string('authorization_reference')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamps();
        });

        Schema::table('user_discounts', function (Blueprint $table) {
            $table->unique('deposit_id');
        });

        $this->service = new PendingPaymentDiscountService(new FareDiscountService());
        $this->admin = new Admin(['name' => 'POS Cashier']);
        $this->admin->id = 9;
    }

    public function test_adjustments_change_only_the_selected_seat_and_recalculate_the_payment(): void
    {
        [$ticket, $deposit] = $this->pendingKioskPayment();
        $discount = Discount::create([
            'name' => 'Senior Citizen',
            'percentage' => 20,
            'status' => Status::ENABLE,
        ]);

        $this->service->adjust($deposit->id, 'D1', 'apply', $discount->id, 'SC-001', $this->admin);

        $manifest = BookedTicket::findOrFail($ticket->id)->passenger_manifest;
        $this->assertSame(1680.0, (float) $manifest[0]['fare']);
        $this->assertSame('discounted', $manifest[0]['passenger_type']);
        $this->assertSame(2100.0, (float) $manifest[1]['fare']);
        $this->assertSame('regular', $manifest[1]['passenger_type']);
        $this->assertSame(3780.0, (float) Deposit::findOrFail($deposit->id)->final_amount);
        $this->assertSame(420.0, (float) UserDiscount::where('deposit_id', $deposit->id)->value('amount'));

        $this->service->adjust($deposit->id, 'D2', 'apply', $discount->id, 'SC-002', $this->admin);
        $this->service->adjust($deposit->id, 'D1', 'remove', null, null, $this->admin);

        $manifest = BookedTicket::findOrFail($ticket->id)->passenger_manifest;
        $summary = UserDiscount::where('deposit_id', $deposit->id)->firstOrFail();

        $this->assertSame(2100.0, (float) $manifest[0]['fare']);
        $this->assertSame('regular', $manifest[0]['passenger_type']);
        $this->assertSame(1680.0, (float) $manifest[1]['fare']);
        $this->assertSame('discounted', $manifest[1]['passenger_type']);
        $this->assertSame('SC-002', $manifest[1]['id_number']);
        $this->assertSame(3780.0, (float) Deposit::findOrFail($deposit->id)->final_amount);
        $this->assertSame(420.0, (float) $summary->amount);
        $this->assertCount(1, $summary->passenger_manifest);
        $this->assertSame('D2', $summary->passenger_manifest[0]['seat']);
    }

    public function test_removing_the_last_discount_restores_the_full_amount_and_deletes_summary(): void
    {
        [$ticket, $deposit] = $this->pendingKioskPayment();
        $discount = Discount::create([
            'name' => 'PWD',
            'percentage' => 20,
            'status' => Status::ENABLE,
        ]);

        $this->service->adjust($deposit->id, 'D1', 'apply', $discount->id, 'PWD-12', $this->admin);
        $this->service->adjust($deposit->id, 'D1', 'remove', null, null, $this->admin);

        $this->assertSame(4200.0, (float) Deposit::findOrFail($deposit->id)->final_amount);
        $this->assertDatabaseMissing('user_discounts', ['deposit_id' => $deposit->id]);
        $this->assertSame('regular', BookedTicket::findOrFail($ticket->id)->passenger_manifest[0]['passenger_type']);
    }

    public function test_gateway_charges_are_recalculated_from_the_discounted_subtotal(): void
    {
        [, $deposit] = $this->pendingKioskPayment();
        DB::table('gateway_currencies')->where('method_code', 1001)->update([
            'fixed_charge' => 50,
            'percent_charge' => 2,
        ]);
        $discount = Discount::create([
            'name' => 'Senior Citizen',
            'percentage' => 20,
            'status' => Status::ENABLE,
        ]);

        $this->service->adjust($deposit->id, 'D1', 'apply', $discount->id, 'SC-001', $this->admin);

        $deposit->refresh();
        $this->assertSame(125.6, (float) $deposit->charge);
        $this->assertSame(3905.6, (float) $deposit->final_amount);
    }

    public function test_online_booking_cannot_be_adjusted_from_the_kiosk_discount_control(): void
    {
        [, $deposit] = $this->pendingKioskPayment(null);
        $discount = Discount::create([
            'name' => 'Senior Citizen',
            'percentage' => 20,
            'status' => Status::ENABLE,
        ]);

        $this->expectException(ValidationException::class);

        $this->service->adjust($deposit->id, 'D1', 'apply', $discount->id, 'SC-001', $this->admin);
    }

    private function pendingKioskPayment(?int $kioskId = 4): array
    {
        $manifest = [
            $this->regularPassenger('D1', 'Passenger One'),
            $this->regularPassenger('D2', 'Passenger Two'),
        ];
        $ticket = new BookedTicket();
        $ticket->kiosk_id = $kioskId;
        $ticket->unit_price = 2100;
        $ticket->sub_total = 4200;
        $ticket->seats = ['D1', 'D2'];
        $ticket->passenger_manifest = $manifest;
        $ticket->saveQuietly();

        DB::table('gateway_currencies')->insert([
            'method_code' => 1001,
            'currency' => 'PHP',
            'fixed_charge' => 0,
            'percent_charge' => 0,
            'rate' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $deposit = new Deposit();
        $deposit->booked_ticket_id = $ticket->id;
        $deposit->trx = 'GV-PENDING-1';
        $deposit->method_code = 1001;
        $deposit->method_currency = 'PHP';
        $deposit->amount = 4200;
        $deposit->charge = 0;
        $deposit->rate = 1;
        $deposit->final_amount = 4200;
        $deposit->status = Status::PAYMENT_PENDING;
        $deposit->save();

        return [$ticket, $deposit];
    }

    private function regularPassenger(string $seat, string $name): array
    {
        return [
            'seat' => $seat,
            'name' => $name,
            'passenger_type' => 'regular',
            'discount_id' => null,
            'discount_name' => null,
            'discount_percentage' => 0,
            'id_number' => null,
            'base_fare' => 2100,
            'discount_amount' => 0,
            'fare' => 2100,
        ];
    }
}
