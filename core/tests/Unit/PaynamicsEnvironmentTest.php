<?php

namespace Tests\Unit;

use App\Http\Controllers\Gateway\Paynamics\ProcessController;
use App\Services\PendingPaymentExpirationService;
use App\Services\Paynamics;
use App\Services\PaymentGatewayService;
use ReflectionMethod;
use Tests\TestCase;

class PaynamicsEnvironmentTest extends TestCase
{
    public function test_sandbox_environment_enables_direct_transaction_polling(): void
    {
        config()->set('paynamics.environment', 'sandbox');

        $this->assertTrue(Paynamics::isSandbox());
    }

    public function test_non_sandbox_environment_does_not_enable_direct_transaction_polling(): void
    {
        config()->set('paynamics.environment', 'production');

        $this->assertFalse(Paynamics::isSandbox());
    }

    public function test_query_response_codes_are_mapped_to_realtime_payment_states(): void
    {
        $controller = new ProcessController(
            $this->createMock(PaymentGatewayService::class),
            $this->createMock(PendingPaymentExpirationService::class)
        );
        $method = new ReflectionMethod($controller, 'providerTransactionState');

        $this->assertSame('success', $method->invoke($controller, (object) ['response_code' => 'GR001']));
        $this->assertSame('pending', $method->invoke($controller, (object) ['response_code' => 'GR033']));
        $this->assertSame('failed', $method->invoke($controller, (object) ['response_code' => 'GR003']));
        $this->assertSame('pending', $method->invoke($controller, null));
    }
}
