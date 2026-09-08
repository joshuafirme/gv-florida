<?php

return [
    'environment' => strtolower((string) env('PAYNAMICS_ENVIRONMENT', 'production')),
    'basic_auth_user' => env('PAYNAMICS_BASIC_AUTH_USER'),
    'basic_auth_pw' => env('PAYNAMICS_BASIC_AUTH_PW'),
    'merchant_key' => env('PAYNAMICS_MKEY'),
    'merchant_id' => env('PAYNAMICS_MID'),
    'endpoint' => env('PAYNAMICS_ENDPOINT', 'https://payin.payserv.net/paygate/transactions/'),
    'sandbox_poll_interval_ms' => max((int) env('PAYNAMICS_SANDBOX_POLL_INTERVAL_MS', 3000), 1000),
];
