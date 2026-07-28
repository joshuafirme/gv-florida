<?php

namespace App\Services;

use App\Models\PaynamicsPaymentChannel;
use App\Models\PaynamicsPaymentMethod;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaynamicsPaymentMethodImporter
{
    public function importFile(?string $path = null): void
    {
        $path ??= base_path('../assets/admin/paynamics_pmethod.json');

        if (!is_file($path)) {
            throw new RuntimeException("Paynamics payment method file was not found at {$path}.");
        }

        $payload = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $this->import($payload);
    }

    public function import(array $payload): void
    {
        DB::transaction(function () use ($payload) {
            foreach (array_values($payload['pmethod'] ?? []) as $methodOrder => $methodData) {
                $method = PaynamicsPaymentMethod::firstOrNew([
                    'code' => $methodData['value'],
                ]);

                $method->name = $methodData['name'];
                $method->sort_order = $methodOrder;
                if (!$method->exists) {
                    $method->is_enabled = true;
                }
                $method->save();

                foreach (array_values($methodData['types'] ?? []) as $channelOrder => $channelData) {
                    $channel = PaynamicsPaymentChannel::firstOrNew([
                        'code' => $channelData['value'],
                    ]);

                    $channel->paynamics_payment_method_id = $method->id;
                    $channel->name = $channelData['name'];
                    $channel->sort_order = $channelOrder;
                    if (!$channel->exists) {
                        $channel->is_enabled = true;
                    }
                    if (blank($channel->icon_url) && filled($channelData['icon_url'] ?? null)) {
                        $channel->icon_url = $channelData['icon_url'];
                    }
                    $channel->save();
                }
            }
        });
    }
}
