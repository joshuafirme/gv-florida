<?php

namespace Tests\Feature;

use Tests\TestCase;

class PaynamicsCallbackRouteTest extends TestCase
{
    public function test_paynamics_callback_urls_include_the_booking_transaction_id(): void
    {
        $requestId = 'TRX-CALLBACK-123';

        foreach ([
            'user.paynamics.response' => '/user/paynamics/response',
            'user.paynamics.cancel' => '/user/paynamics/cancel',
        ] as $routeName => $expectedPath) {
            $url = route($routeName, ['request_id' => $requestId]);
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            $this->assertStringEndsWith($expectedPath, (string) parse_url($url, PHP_URL_PATH));
            $this->assertSame($requestId, $query['request_id'] ?? null);
        }
    }
}
