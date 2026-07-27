<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePaymentSettingsRequest;
use App\Models\PaynamicsPaymentChannel;
use App\Models\PaynamicsPaymentMethod;
use App\Services\PaymentGatewayService;
use Illuminate\Support\Facades\DB;

class PaymentSettingController extends Controller
{
    public function __construct(private readonly PaymentGatewayService $paymentGateways)
    {
    }

    public function edit()
    {
        $pageTitle = 'Payment Settings';
        $cashGateway = $this->paymentGateways->gateway(PaymentGatewayService::CASH);
        $paynamicsGateway = $this->paymentGateways->gateway(PaymentGatewayService::PAYNAMICS);

        abort_unless($cashGateway?->singleCurrency && $paynamicsGateway?->singleCurrency, 404, 'Cash and Paynamics gateway currencies must be configured.');

        $methods = PaynamicsPaymentMethod::with('channels')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.payment_settings.edit', compact(
            'pageTitle',
            'cashGateway',
            'paynamicsGateway',
            'methods'
        ));
    }

    public function update(UpdatePaymentSettingsRequest $request)
    {
        $cashGateway = $this->paymentGateways->gateway(PaymentGatewayService::CASH);
        $paynamicsGateway = $this->paymentGateways->gateway(PaymentGatewayService::PAYNAMICS);

        abort_unless($cashGateway?->singleCurrency && $paynamicsGateway?->singleCurrency, 404, 'Cash and Paynamics gateway currencies must be configured.');

        DB::transaction(function () use ($request, $cashGateway, $paynamicsGateway) {
            $this->updateCurrency($cashGateway->singleCurrency, $request->input('gateways.cash'));
            $this->updateCurrency($paynamicsGateway->singleCurrency, $request->input('gateways.paynamics'));

            foreach ($request->input('methods', []) as $method) {
                PaynamicsPaymentMethod::whereKey($method['id'])->update([
                    'is_enabled' => (bool) $method['is_enabled'],
                ]);
            }

            foreach ($request->input('channels', []) as $channel) {
                PaynamicsPaymentChannel::whereKey($channel['id'])->update([
                    'icon_url' => filled($channel['icon_url'] ?? null)
                        ? trim($channel['icon_url'])
                        : null,
                    'online_enabled' => (bool) $channel['online_enabled'],
                    'kiosk_enabled' => (bool) $channel['kiosk_enabled'],
                ]);
            }
        });

        $this->paymentGateways->clearCache();

        $notify[] = ['success', 'Payment settings updated successfully.'];
        return back()->withNotify($notify);
    }

    private function updateCurrency($currency, array $settings): void
    {
        $currency->description = $settings['description'] ?? null;
        $currency->online_enabled = (bool) $settings['online_enabled'];
        $currency->kiosk_enabled = (bool) $settings['kiosk_enabled'];
        $currency->min_amount = $settings['min_amount'];
        $currency->max_amount = $settings['max_amount'];
        $currency->fixed_charge = $settings['fixed_charge'];
        $currency->percent_charge = $settings['percent_charge'];
        $currency->rate = $settings['rate'];
        $currency->save();
    }
}
