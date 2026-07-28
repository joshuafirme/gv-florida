<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check()
            && auth('admin')->user()->isPermitted('admin.setting.system');
    }

    protected function prepareForValidation(): void
    {
        $methods = collect($this->input('methods', []))
            ->map(function ($method, $id) {
                $method['is_enabled'] = $this->boolean("methods.{$id}.is_enabled");
                return $method;
            })
            ->all();

        $channels = collect($this->input('channels', []))
            ->map(function ($channel, $id) {
                $channel['online_enabled'] = $this->boolean("channels.{$id}.online_enabled");
                $channel['kiosk_enabled'] = $this->boolean("channels.{$id}.kiosk_enabled");
                return $channel;
            })
            ->all();

        $gateways = collect($this->input('gateways', []))
            ->map(function ($gateway, $key) {
                $gateway['online_enabled'] = $this->boolean("gateways.{$key}.online_enabled");
                $gateway['kiosk_enabled'] = $this->boolean("gateways.{$key}.kiosk_enabled");
                return $gateway;
            })
            ->all();

        $this->merge([
            'methods' => $methods,
            'channels' => $channels,
            'gateways' => $gateways,
        ]);
    }

    public function rules(): array
    {
        return [
            'methods' => ['required', 'array'],
            'methods.*.id' => ['required', 'integer', Rule::exists('paynamics_payment_methods', 'id')],
            'methods.*.is_enabled' => ['required', 'boolean'],
            'channels' => ['required', 'array'],
            'channels.*.id' => ['required', 'integer', Rule::exists('paynamics_payment_channels', 'id')],
            'channels.*.icon_url' => ['nullable', 'url:http,https', 'max:2048'],
            'channels.*.online_enabled' => ['required', 'boolean'],
            'channels.*.kiosk_enabled' => ['required', 'boolean'],
            'gateways' => ['required', 'array'],
            'gateways.cash.description' => ['nullable', 'string', 'max:255'],
            'gateways.paynamics.description' => ['nullable', 'string', 'max:255'],
            'gateways.cash.online_enabled' => ['required', 'boolean'],
            'gateways.cash.kiosk_enabled' => ['required', 'boolean'],
            'gateways.paynamics.online_enabled' => ['required', 'boolean'],
            'gateways.paynamics.kiosk_enabled' => ['required', 'boolean'],
            'gateways.cash.min_amount' => ['required', 'numeric', 'gte:0'],
            'gateways.cash.max_amount' => ['required', 'numeric', 'gt:gateways.cash.min_amount'],
            'gateways.cash.fixed_charge' => ['required', 'numeric', 'gte:0'],
            'gateways.cash.percent_charge' => ['required', 'numeric', 'between:0,100'],
            'gateways.cash.rate' => ['required', 'numeric', 'gt:0'],
            'gateways.paynamics.min_amount' => ['required', 'numeric', 'gte:0'],
            'gateways.paynamics.max_amount' => ['required', 'numeric', 'gt:gateways.paynamics.min_amount'],
            'gateways.paynamics.fixed_charge' => ['required', 'numeric', 'gte:0'],
            'gateways.paynamics.percent_charge' => ['required', 'numeric', 'between:0,100'],
            'gateways.paynamics.rate' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
