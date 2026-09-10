<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Models\Deposit;
use App\Services\PaynamicsPaymentBroadcaster;
use App\Services\PendingPaymentExpirationService;
use App\Services\ScheduleBoardBroadcaster;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class PendingSeatExpirationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('booked_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id')->nullable();
            $table->unsignedBigInteger('pickup_point')->nullable();
            $table->date('date_of_journey')->nullable();
            $table->unsignedTinyInteger('status')->default(Status::BOOKED_PENDING);
            $table->text('seats')->nullable();
            $table->timestamps();
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booked_ticket_id')->nullable();
            $table->string('trx')->nullable();
            $table->string('pchannel')->nullable();
            $table->string('pay_reference')->nullable();
            $table->decimal('final_amount', 12, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(Status::PAYMENT_PENDING);
            $table->string('expiry_limit')->nullable();
            $table->timestamps();
        });
    }

    public function test_online_seat_is_held_until_its_thirty_minute_gateway_expiry(): void
    {
        $now = CarbonImmutable::parse('2026-09-10 10:20:00');
        $ticketId = $this->insertPendingTicket($now->subMinutes(20));
        $this->insertPendingDeposit($ticketId, $now->addMinutes(10));

        $heldTicketIds = BookedTicket::query()
            ->holdingSeats($now)
            ->pluck('id')
            ->all();

        $this->assertSame([$ticketId], $heldTicketIds);
    }

    public function test_seat_is_available_at_the_exact_gateway_expiry_even_before_cleanup(): void
    {
        $now = CarbonImmutable::parse('2026-09-10 10:30:00');
        $ticketId = $this->insertPendingTicket($now->subMinutes(30));
        $this->insertPendingDeposit($ticketId, $now);

        $this->assertFalse(
            BookedTicket::query()->holdingSeats($now)->whereKey($ticketId)->exists()
        );
    }

    public function test_expiration_updates_payment_and_ticket_and_broadcasts_release(): void
    {
        $now = CarbonImmutable::parse('2026-09-10 10:30:00');
        $ticketId = $this->insertPendingTicket($now->subMinutes(30));
        $depositId = $this->insertPendingDeposit($ticketId, $now);

        $paymentBroadcaster = Mockery::mock(PaynamicsPaymentBroadcaster::class);
        $paymentBroadcaster->shouldReceive('paymentUpdated')
            ->once()
            ->with('ONLINE-30-MIN', Mockery::on(
                fn (array $payload) => $payload['state'] === 'expired' && $payload['is_paid'] === false
            ));

        $scheduleBroadcaster = Mockery::mock(ScheduleBoardBroadcaster::class);
        $scheduleBroadcaster->shouldReceive('passengerTransaction')
            ->once()
            ->with(Mockery::on(
                fn (array $payload) => $payload['ticket_id'] === $ticketId
                    && $payload['status'] === Status::BOOKED_EXPIRED
            ));
        $this->app->instance(ScheduleBoardBroadcaster::class, $scheduleBroadcaster);

        $expired = (new PendingPaymentExpirationService($paymentBroadcaster))->expireDue($now);

        $this->assertSame(1, $expired);
        $this->assertDatabaseHas('deposits', [
            'id' => $depositId,
            'status' => Status::PAYMENT_EXPIRED,
        ]);
        $this->assertDatabaseHas('booked_tickets', [
            'id' => $ticketId,
            'status' => Status::BOOKED_EXPIRED,
        ]);
    }

    private function insertPendingTicket(CarbonImmutable $createdAt): int
    {
        return DB::table('booked_tickets')->insertGetId([
            'trip_id' => 8,
            'pickup_point' => 1,
            'date_of_journey' => '2026-09-12',
            'status' => Status::BOOKED_PENDING,
            'seats' => json_encode(['D1']),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function insertPendingDeposit(int $ticketId, CarbonImmutable $expiresAt): int
    {
        return DB::table('deposits')->insertGetId([
            'booked_ticket_id' => $ticketId,
            'trx' => 'ONLINE-30-MIN',
            'pchannel' => 'creditcard',
            'final_amount' => 2800,
            'status' => Status::PAYMENT_PENDING,
            'expiry_limit' => $expiresAt->format('Y-m-d H:i:s'),
            'created_at' => $expiresAt->subMinutes(30),
            'updated_at' => $expiresAt->subMinutes(30),
        ]);
    }
}
