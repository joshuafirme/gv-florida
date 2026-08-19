<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PaynamicsPaymentBroadcaster
{
    public const EVENT = 'payment-status-updated';

    public static function channelFor(string $transactionId): string
    {
        return 'paynamics-payment-' . substr(hash('sha256', $transactionId), 0, 48);
    }

    public function paymentUpdated(string $transactionId, array $payload): void
    {
        $appId = config('services.pusher.app_id');
        $key = config('services.pusher.key');
        $secret = config('services.pusher.secret');
        $cluster = config('services.pusher.cluster', 'ap1');

        if (!$appId || !$key || !$secret || !$cluster) {
            return;
        }

        $path = "/apps/{$appId}/events";
        $body = json_encode([
            'name' => self::EVENT,
            'channel' => self::channelFor($transactionId),
            'data' => json_encode($payload),
        ]);
        $query = [
            'auth_key' => $key,
            'auth_timestamp' => time(),
            'auth_version' => '1.0',
            'body_md5' => md5($body),
        ];

        ksort($query);

        $query['auth_signature'] = hash_hmac(
            'sha256',
            "POST\n{$path}\n" . http_build_query($query),
            $secret
        );

        try {
            (new Client([
                'base_uri' => "https://api-{$cluster}.pusher.com",
                'timeout' => 2,
            ]))->post($path, [
                'query' => $query,
                'body' => $body,
                'headers' => ['Content-Type' => 'application/json'],
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Paynamics payment Pusher event failed', [
                'transaction_id' => $transactionId,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
