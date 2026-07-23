<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ScheduleBoardBroadcaster
{
    public const CHANNEL = 'schedule-board';
    public const EVENT = 'passenger-transaction';
    public const DISPATCH_STATUS_EVENT = 'dispatch-status-updated';
    public const SCHEDULE_DATA_EVENT = 'schedule-data-updated';

    public function passengerTransaction(array $payload = []): void
    {
        $this->trigger(self::CHANNEL, self::EVENT, array_merge([
            'timestamp' => now()->toIso8601String(),
        ], $payload));
    }

    public function dispatchStatusUpdated(array $payload): void
    {
        $this->trigger(self::CHANNEL, self::DISPATCH_STATUS_EVENT, array_merge([
            'timestamp' => now()->toIso8601String(),
        ], $payload));
    }

    public function scheduleDataUpdated(array $payload = []): void
    {
        $this->trigger(self::CHANNEL, self::SCHEDULE_DATA_EVENT, array_merge([
            'timestamp' => now()->toIso8601String(),
        ], $payload));
    }

    private function trigger(string $channel, string $event, array $payload): void
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
            'name' => $event,
            'channel' => $channel,
            'data' => json_encode($payload),
        ]);

        $query = [
            'auth_key' => $key,
            'auth_timestamp' => time(),
            'auth_version' => '1.0',
            'body_md5' => md5($body),
        ];

        ksort($query);

        $signature = hash_hmac(
            'sha256',
            "POST\n{$path}\n" . http_build_query($query),
            $secret
        );

        $query['auth_signature'] = $signature;

        try {
            (new Client([
                'base_uri' => "https://api-{$cluster}.pusher.com",
                'timeout' => 2,
            ]))->post($path, [
                'query' => $query,
                'body' => $body,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Schedule board Pusher event failed', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
