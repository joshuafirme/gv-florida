<?php

namespace Tests\Feature;

use App\Http\Controllers\Gateway\Paynamics\ProcessController;
use App\Models\PaynamicsWebhookLog;
use App\Services\PaymentGatewayService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaynamicsWebhookLoggingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->string('trx')->nullable();
            $table->string('pay_reference')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamps();
        });

        Schema::create('paynamics_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deposit_id')->nullable();
            $table->string('provider');
            $table->string('event_type')->nullable();
            $table->string('request_id')->nullable();
            $table->string('original_transaction_id')->nullable();
            $table->string('pay_reference')->nullable();
            $table->string('status');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('payload');
            $table->json('response')->nullable();
            $table->json('headers')->nullable();
            $table->text('error_message')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Storage::fake('local');
    }

    public function test_it_logs_and_links_an_incoming_webhook_before_returning_success(): void
    {
        $depositId = DB::table('deposits')->insertGetId([
            'trx' => 'GVF-TEST-001',
            'pay_reference' => 'PAY-001',
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $request = Request::create('/api/paynamics/notification', 'POST', [
            'request_id' => 'GVF-TEST-001',
            'response_code' => 'GR033',
            'response_message' => 'Transaction Pending',
        ], server: [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_AUTHORIZATION' => 'Basic secret',
        ]);

        $response = $this->controller()->notification($request);

        $this->assertSame(200, $response->getStatusCode());
        $log = PaynamicsWebhookLog::firstOrFail();
        $this->assertSame($depositId, $log->deposit_id);
        $this->assertSame('processed', $log->status);
        $this->assertSame(200, $log->http_status);
        $this->assertSame('GR033', $log->event_type);
        $this->assertArrayNotHasKey('authorization', $log->headers);
        Storage::disk('local')->assertExists('paynamics/webhooks/GVF-TEST-001-'.$log->id.'.json');
    }

    public function test_a_success_notification_without_a_matching_transaction_is_logged_as_failed(): void
    {
        $request = Request::create('/api/paynamics/notification', 'POST', [
            'request_id' => 'UNKNOWN-TRANSACTION',
            'response_code' => 'GR001',
        ]);

        $response = $this->controller()->notification($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertDatabaseHas('paynamics_webhook_logs', [
            'request_id' => 'UNKNOWN-TRANSACTION',
            'status' => 'failed',
            'http_status' => 422,
        ]);
    }

    private function controller(): ProcessController
    {
        return new ProcessController(app(PaymentGatewayService::class));
    }
}
