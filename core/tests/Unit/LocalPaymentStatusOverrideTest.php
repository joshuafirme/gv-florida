<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Http\Controllers\Admin\DepositController;
use App\Models\Admin;
use App\Models\BookedTicket;
use App\Models\Deposit;
use App\Services\CashierTransactionRecorder;
use App\Services\PaymentSuccessNotifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class LocalPaymentStatusOverrideTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('booked_ticket_id')->nullable();
            $table->unsignedTinyInteger('status')->default(Status::PAYMENT_INITIATE);
            $table->unsignedBigInteger('processed_by_admin_id')->nullable();
            $table->string('processed_by_name')->nullable();
            $table->timestamps();
        });

        Schema::create('booked_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kiosk_id')->nullable();
            $table->unsignedTinyInteger('status')->default(Status::BOOKED_PENDING);
            $table->text('seats')->nullable();
            $table->timestamps();
        });

        Schema::create('slip_series_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('seat');
            $table->unsignedBigInteger('booked_ticket_id');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    public function test_status_override_is_rejected_outside_the_local_environment(): void
    {
        $this->expectException(NotFoundHttpException::class);

        app(DepositController::class)->overrideStatus(Request::create('/admin/deposit/status-override', 'POST'));
    }

    public function test_local_admin_can_override_a_payment_status(): void
    {
        $this->app['env'] = 'local';

        $userId = DB::table('users')->insertGetId([
            'email' => 'passenger@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ticketId = DB::table('booked_tickets')->insertGetId([
            'status' => Status::BOOKED_PENDING,
            'seats' => json_encode(['D1', 'D2']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $depositId = DB::table('deposits')->insertGetId([
            'user_id' => $userId,
            'booked_ticket_id' => $ticketId,
            'status' => Status::PAYMENT_PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = (new Admin())->forceFill([
            'id' => 7,
            'name' => 'Local Admin',
        ]);
        auth('admin')->setUser($admin);

        $recorder = \Mockery::mock(CashierTransactionRecorder::class);
        $recorder->shouldReceive('recordSold')
            ->once()
            ->with(\Mockery::on(fn (Deposit $deposit) => $deposit->id === $depositId));
        $this->app->instance(CashierTransactionRecorder::class, $recorder);

        $notifier = \Mockery::mock(PaymentSuccessNotifier::class);
        $notifier->shouldReceive('send')
            ->once()
            ->withArgs(fn (
                Deposit $deposit,
                BookedTicket $ticket,
                bool $isManual,
                array $sendVia
            ) => $deposit->id === $depositId
                && $ticket->id === $ticketId
                && $isManual === false
                && $sendVia === ['email'])
            ->andReturnTrue();
        $this->app->instance(PaymentSuccessNotifier::class, $notifier);

        $request = Request::create('/admin/deposit/status-override', 'POST', [
            'deposit_id' => $depositId,
            'status' => Status::PAYMENT_SUCCESS,
        ]);

        app(DepositController::class)->overrideStatus($request);

        $this->assertDatabaseHas('deposits', [
            'id' => $depositId,
            'status' => Status::PAYMENT_SUCCESS,
            'processed_by_admin_id' => 7,
            'processed_by_name' => 'Local Admin',
        ]);
        $this->assertDatabaseHas('booked_tickets', [
            'id' => $ticketId,
            'status' => Status::BOOKED_APPROVED,
        ]);
        $this->assertDatabaseHas('slip_series_numbers', [
            'booked_ticket_id' => $ticketId,
            'seat' => 'D1',
        ]);
        $this->assertDatabaseHas('slip_series_numbers', [
            'booked_ticket_id' => $ticketId,
            'seat' => 'D2',
        ]);
    }
}
