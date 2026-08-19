<?php

namespace Tests\Unit;

use App\Services\PaynamicsPaymentBroadcaster;
use PHPUnit\Framework\TestCase;

class PaynamicsPaymentBroadcasterTest extends TestCase
{
    public function test_payment_channels_are_scoped_without_exposing_transaction_ids(): void
    {
        $firstTransaction = 'GVF-PRIVATE-TRANSACTION-001';
        $secondTransaction = 'GVF-PRIVATE-TRANSACTION-002';
        $firstChannel = PaynamicsPaymentBroadcaster::channelFor($firstTransaction);

        $this->assertSame($firstChannel, PaynamicsPaymentBroadcaster::channelFor($firstTransaction));
        $this->assertNotSame($firstChannel, PaynamicsPaymentBroadcaster::channelFor($secondTransaction));
        $this->assertStringStartsWith('paynamics-payment-', $firstChannel);
        $this->assertStringNotContainsString($firstTransaction, $firstChannel);
    }
}
