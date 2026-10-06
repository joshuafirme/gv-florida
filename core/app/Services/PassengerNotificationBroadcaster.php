<?php

namespace App\Services;

use App\Models\PassengerNotification;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PassengerNotificationBroadcaster
{
    public const EVENT = 'notification-created';

    public static function channelFor(int $userId): string
    {
        return 'passenger-notifications-' . hash_hmac('sha256', (string) $userId, (string) config('app.key'));
    }

    public function broadcast(PassengerNotification $notification): bool
    {
        $appId = config('services.pusher.app_id');
        $key = config('services.pusher.key');
        $secret = config('services.pusher.secret');
        $cluster = config('services.pusher.cluster', 'ap1');

        if (!$appId || !$key || !$secret || !$cluster) {
            return false;
        }

        $path = "/apps/{$appId}/events";
        $body = json_encode([
            'name' => self::EVENT,
            'channel' => self::channelFor((int) $notification->user_id),
            'data' => json_encode([
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'channel' => $notification->channel,
                'sent_by' => $notification->sent_by,
                'status' => $notification->status,
                'created_at' => $notification->created_at?->toIso8601String(),
                'created_at_label' => showDateTime($notification->created_at),
                'history_url' => route('user.notifications.index'),
                'read_url' => route('user.notifications.read', $notification),
            ]),
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
            return true;
        } catch (\Throwable $exception) {
            Log::warning('Passenger notification Pusher event failed', [
                'notification_id' => $notification->id,
                'message' => $exception->getMessage(),
            ]);
            return false;
        }
    }
}
