<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\Gateway;
use App\Models\GatewayCurrency;
use App\Models\PaynamicsPaymentChannel;
use App\Models\PaynamicsPaymentMethod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class PaymentGatewayService
{
    public const CASH = 'cash';
    public const PAYNAMICS = 'paynamics';

    private const CACHE_KEY = 'booking_payment_settings.enabled';
    private const UNAVAILABLE_MESSAGE = 'The selected payment method is no longer available. Please choose another payment method.';

    public function isGatewayEnabled(string $gateway, ?bool $isKioskBooking = null): bool
    {
        $gateway = strtolower(trim($gateway));

        if (!in_array($gateway, [self::CASH, self::PAYNAMICS], true)) {
            return false;
        }

        return $this->globallyEnabledGatewayCurrencies()
            ->contains(function (GatewayCurrency $currency) use ($gateway, $isKioskBooking) {
                if (strtolower((string) $currency->method?->alias) !== $gateway) {
                    return false;
                }

                return $isKioskBooking === null
                    || $this->enabledForBookingChannel($currency, $isKioskBooking);
            });
    }

    public function getEnabledGatewayCurrencies(bool $isKioskBooking = false): Collection
    {
        $channelColumn = $this->paynamicsChannelColumn($isKioskBooking);
        $hasPaynamicsChannels = PaynamicsPaymentChannel::query()
            ->where($channelColumn, true)
            ->whereHas('paymentMethod', fn ($query) => $query->where('is_enabled', true))
            ->exists();

        return $this->globallyEnabledGatewayCurrencies()
            ->filter(function (GatewayCurrency $currency) use ($hasPaynamicsChannels, $isKioskBooking) {
                if (!$this->enabledForBookingChannel($currency, $isKioskBooking)) {
                    return false;
                }

                return strtolower((string) $currency->method?->alias) !== self::PAYNAMICS
                    || $hasPaynamicsChannels;
            })
            ->values();
    }

    public function getEnabledPaynamicsMethods(bool $isKioskBooking = false): Collection
    {
        if (!$this->isGatewayEnabled(self::PAYNAMICS, $isKioskBooking)) {
            return collect();
        }

        $channelColumn = $this->paynamicsChannelColumn($isKioskBooking);

        return PaynamicsPaymentMethod::query()
            ->where('is_enabled', true)
            ->whereHas('channels', fn ($query) => $query->where($channelColumn, true))
            ->with(['channels' => fn ($query) => $query->where($channelColumn, true)])
            ->orderByRaw("CASE WHEN code = 'onlinebanktransfer' THEN 0 ELSE 1 END")
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function getEnabledPaynamicsChannels(?string $methodCode = null, bool $isKioskBooking = false): Collection
    {
        return $this->getEnabledPaynamicsMethods($isKioskBooking)
            ->when(
                $methodCode,
                fn (Collection $methods) => $methods->where('code', $methodCode)
            )
            ->flatMap->channels
            ->values();
    }

    public function isPaynamicsChannelEnabled(string $channelCode, bool $isKioskBooking = false): bool
    {
        if (!$this->isGatewayEnabled(self::PAYNAMICS, $isKioskBooking)) {
            return false;
        }

        $channelColumn = $this->paynamicsChannelColumn($isKioskBooking);

        return PaynamicsPaymentChannel::query()
            ->where('code', $channelCode)
            ->where($channelColumn, true)
            ->whereHas('paymentMethod', fn ($query) => $query->where('is_enabled', true))
            ->exists();
    }

    public function validatePaynamicsChannel(
        string $methodCode,
        string $channelCode,
        bool $isKioskBooking = false
    ): PaynamicsPaymentChannel
    {
        if (!$this->isGatewayEnabled(self::PAYNAMICS, $isKioskBooking)) {
            $this->unavailable();
        }

        $channelColumn = $this->paynamicsChannelColumn($isKioskBooking);
        $channel = PaynamicsPaymentChannel::query()
            ->where('code', $channelCode)
            ->where($channelColumn, true)
            ->whereHas('paymentMethod', function ($query) use ($methodCode) {
                $query->where('code', $methodCode)->where('is_enabled', true);
            })
            ->with('paymentMethod')
            ->first();

        if (!$channel) {
            $this->unavailable();
        }

        return $channel;
    }

    public function validateGatewayCurrency(
        string|int $methodCode,
        string $currency,
        bool $isKioskBooking = false
    ): GatewayCurrency
    {
        $gatewayCurrency = $this->getEnabledGatewayCurrencies($isKioskBooking)
            ->first(function (GatewayCurrency $gatewayCurrency) use ($methodCode, $currency) {
                return (string) $gatewayCurrency->method_code === (string) $methodCode
                    && strcasecmp((string) $gatewayCurrency->currency, $currency) === 0;
            });

        if (!$gatewayCurrency) {
            $this->unavailable();
        }

        return $gatewayCurrency;
    }

    public function gateway(string $gateway): ?Gateway
    {
        $gateway = strtolower(trim($gateway));

        return Gateway::query()
            ->whereRaw('LOWER(alias) = ?', [$gateway])
            ->whereHas('currencies')
            ->orderBy('code')
            ->first();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function globallyEnabledGatewayCurrencies(): Collection
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function () {
            return GatewayCurrency::query()
                ->whereHas('method', function ($query) {
                    $query->where('status', Status::ENABLE)
                        ->where(function ($gatewayQuery) {
                            $gatewayQuery->whereRaw('LOWER(alias) = ?', [self::CASH])
                                ->orWhereRaw('LOWER(alias) = ?', [self::PAYNAMICS]);
                        });
                })
                ->with('method')
                ->orderByRaw("CASE WHEN LOWER(gateway_alias) = 'cash' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();
        });
    }

    private function enabledForBookingChannel(GatewayCurrency $currency, bool $isKioskBooking): bool
    {
        return $isKioskBooking
            ? (bool) $currency->kiosk_enabled
            : (bool) $currency->online_enabled;
    }

    private function paynamicsChannelColumn(bool $isKioskBooking): string
    {
        return $isKioskBooking ? 'kiosk_enabled' : 'online_enabled';
    }

    private function unavailable(): never
    {
        throw ValidationException::withMessages([
            'gateway' => self::UNAVAILABLE_MESSAGE,
        ]);
    }
}
