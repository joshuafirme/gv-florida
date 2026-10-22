<?php

namespace Tests\Feature;

use App\Constants\Status;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\BookedTicket;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PendingOnlinePaymentResumeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('booked_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('kiosk_id')->nullable();
            $table->string('pnr_number')->nullable();
            $table->unsignedTinyInteger('status')->default(Status::BOOKED_PENDING);
            $table->timestamps();
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('booked_ticket_id');
            $table->string('trx');
            $table->string('pchannel')->nullable();
            $table->string('pmethod')->nullable();
            $table->unsignedTinyInteger('status')->default(Status::PAYMENT_INITIATE);
            $table->timestamp('expiry_limit')->nullable();
            $table->string('processing_token')->nullable();
            $table->unsignedBigInteger('processing_admin_id')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_lease_expires_at')->nullable();
            $table->timestamps();
        });

        Storage::fake('local');
    }

    public function test_owner_resumes_the_existing_paynamics_qr_transaction(): void
    {
        $ticket = $this->pendingTicket(userId: 7);
        $user = new User();
        $user->id = 7;
        $user->exists = true;
        $this->actingAs($user);

        Storage::disk('local')->put('paynamics/responses/PAY-EXISTING-001.json', json_encode([
            'request_id' => 'PAY-EXISTING-001',
            'response_code' => 'GR033',
            'payment_action_info' => 'https://payments.example.test/qr/PAY-EXISTING-001',
        ]));

        $response = app(PaymentController::class)->resumePayment($ticket);

        $this->assertSame('https://payments.example.test/qr/PAY-EXISTING-001', $response->getTargetUrl());
        $this->assertSame('PAY-EXISTING-001', session('Track'));
        $this->assertSame($ticket->id, session('booked_ticket_id'));
        $this->assertSame(1, DB::table('deposits')->count());
        $this->assertSame('PAY-EXISTING-001', DB::table('deposits')->value('trx'));
    }

    public function test_a_passenger_cannot_resume_another_users_payment(): void
    {
        $ticket = $this->pendingTicket(userId: 7);
        $user = new User();
        $user->id = 8;
        $user->exists = true;
        $this->actingAs($user);

        try {
            app(PaymentController::class)->resumePayment($ticket);
            $this->fail('A passenger was able to resume another user\'s payment.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_the_legacy_deposit_url_also_resumes_instead_of_showing_passenger_details(): void
    {
        $ticket = $this->pendingTicket(userId: 7);
        $user = new User();
        $user->id = 7;
        $user->exists = true;
        $this->actingAs($user);

        Storage::disk('local')->put('paynamics/responses/PAY-EXISTING-001.json', json_encode([
            'request_id' => 'PAY-EXISTING-001',
            'response_code' => 'GR033',
            'payment_action_info' => 'https://payments.example.test/qr/PAY-EXISTING-001',
        ]));

        $request = Request::create('/payment/deposit', 'GET', [
            'booked_ticket_id' => $ticket->id,
        ]);
        $response = app(PaymentController::class)->deposit($request);

        $this->assertSame('https://payments.example.test/qr/PAY-EXISTING-001', $response->getTargetUrl());
        $this->assertSame(1, DB::table('deposits')->count());
    }

    private function pendingTicket(int $userId): BookedTicket
    {
        $ticketId = DB::table('booked_tickets')->insertGetId([
            'user_id' => $userId,
            'pnr_number' => 'PNR-RESUME-001',
            'status' => Status::BOOKED_PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('deposits')->insert([
            'user_id' => $userId,
            'booked_ticket_id' => $ticketId,
            'trx' => 'PAY-EXISTING-001',
            'pchannel' => 'ubp_qr_ph',
            'pmethod' => 'onlinebanktransfer',
            'status' => Status::PAYMENT_PENDING,
            'expiry_limit' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return BookedTicket::findOrFail($ticketId);
    }
}
