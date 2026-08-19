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

    public function test_payment_status_reconciliation_is_post_only_and_requires_the_active_booking_session(): void
    {
        $route = app('router')->getRoutes()->getByName('user.paynamics.status');

        $this->assertNotNull($route);
        $this->assertSame(['POST'], $route->methods());
        $this->postJson(route('user.paynamics.status'))->assertNotFound();
    }
}
