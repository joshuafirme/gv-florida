<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Models\GatewayCurrency;
use App\Models\PaynamicsPaymentChannel;
use App\Models\PaynamicsPaymentMethod;
use App\Services\PaynamicsPaymentMethodImporter;
use App\Services\PaymentGatewayService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('alias');
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('gateway_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('currency');
            $table->string('symbol')->nullable();
            $table->string('method_code');
            $table->string('gateway_alias');
            $table->boolean('online_enabled')->default(true);
            $table->boolean('kiosk_enabled')->default(true);
            $table->decimal('min_amount', 18, 2)->default(0);
            $table->decimal('max_amount', 18, 2)->default(0);
            $table->decimal('fixed_charge', 18, 2)->default(0);
            $table->decimal('percent_charge', 8, 2)->default(0);
            $table->decimal('rate', 18, 2)->default(1);
            $table->timestamps();
        });

        Schema::create('paynamics_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('paynamics_payment_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paynamics_payment_method_id')
                ->constrained('paynamics_payment_methods')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('icon_url', 2048)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('online_enabled')->default(true);
            $table->boolean('kiosk_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function test_import_is_idempotent_and_preserves_enabled_values(): void
    {
        $payload = [
            'pmethod' => [
                [
                    'name' => 'E-Wallet',
                    'value' => 'wallet',
                    'types' => [
                        [
                            'name' => 'GCash',
                            'value' => 'gc',
                            'icon_url' => 'https://icons.example/gcash.png',
                        ],
                        [
                            'name' => 'PayMaya',
                            'value' => 'paymaya_ph',
                            'icon_url' => 'https://icons.example/paymaya.png',
                        ],
                    ],
                ],
            ],
        ];

        $importer = app(PaynamicsPaymentMethodImporter::class);
        $importer->import($payload);

        PaynamicsPaymentMethod::where('code', 'wallet')->update(['is_enabled' => false]);
        PaynamicsPaymentChannel::where('code', 'gc')->update([
            'is_enabled' => false,
            'online_enabled' => false,
            'icon_url' => 'https://custom.example/gcash.png',
        ]);

        $payload['pmethod'][0]['name'] = 'Digital Wallet';
        $importer->import($payload);

        $this->assertDatabaseCount('paynamics_payment_methods', 1);
        $this->assertDatabaseCount('paynamics_payment_channels', 2);
        $this->assertDatabaseHas('paynamics_payment_methods', [
            'code' => 'wallet',
            'name' => 'Digital Wallet',
            'is_enabled' => false,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('paynamics_payment_channels', [
            'code' => 'gc',
            'is_enabled' => false,
            'online_enabled' => false,
            'icon_url' => 'https://custom.example/gcash.png',
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('paynamics_payment_channels', [
            'code' => 'paymaya_ph',
            'icon_url' => 'https://icons.example/paymaya.png',
        ]);
    }

    public function test_only_cash_and_paynamics_are_exposed_to_both_booking_flows(): void
    {
        $this->seedGateways();
        $this->seedPaynamicsChannel();

        $service = app(PaymentGatewayService::class);
        $onlineOptions = $service->getEnabledGatewayCurrencies()->pluck('gateway_alias')->all();
        $kioskOptions = $service->getEnabledGatewayCurrencies()->pluck('gateway_alias')->all();

        $this->assertSame(['cash', 'paynamics'], $onlineOptions);
        $this->assertSame($onlineOptions, $kioskOptions);
        $this->assertDatabaseHas('gateways', ['alias' => 'legacy-card', 'status' => 1]);
    }

    public function test_disabled_gateways_categories_and_channels_are_rejected(): void
    {
        $this->seedGateways();
        [$method, $channel] = $this->seedPaynamicsChannel();
        $service = app(PaymentGatewayService::class);

        $this->assertSame('wallet', $service->validatePaynamicsChannel('wallet', 'gc')->paymentMethod->code);
        $this->assertSame('gc', $service->validatePaynamicsChannel('wallet', 'gc')->code);

        $channel->update([
            'online_enabled' => false,
            'kiosk_enabled' => false,
        ]);
        $this->assertFalse($service->isPaynamicsChannelEnabled('gc'));
        $this->assertNotContains('paynamics', $service->getEnabledGatewayCurrencies()->pluck('gateway_alias'));

        try {
            $service->validatePaynamicsChannel('wallet', 'gc');
            $this->fail('A disabled channel should not be accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The selected payment method is no longer available. Please choose another payment method.',
                $exception->errors()['gateway'][0]
            );
        }

        $channel->update([
            'online_enabled' => true,
            'kiosk_enabled' => true,
        ]);
        $method->update(['is_enabled' => false]);
        $this->assertFalse($service->isPaynamicsChannelEnabled('gc'));

        Gateway::where('alias', 'cash')->update(['status' => 0]);
        $service->clearCache();

        $this->expectException(ValidationException::class);
        $service->validateGatewayCurrency(1001, 'PHP');
    }

    public function test_cash_and_paynamics_can_be_enabled_per_booking_channel(): void
    {
        $this->seedGateways();
        [, $channel] = $this->seedPaynamicsChannel();

        GatewayCurrency::where('gateway_alias', 'cash')->update([
            'online_enabled' => true,
            'kiosk_enabled' => false,
        ]);
        GatewayCurrency::where('gateway_alias', 'paynamics')->update([
            'online_enabled' => true,
            'kiosk_enabled' => true,
        ]);
        $channel->update([
            'online_enabled' => false,
            'kiosk_enabled' => true,
        ]);

        $service = app(PaymentGatewayService::class);
        $service->clearCache();

        $this->assertSame(
            ['cash'],
            $service->getEnabledGatewayCurrencies(false)->pluck('gateway_alias')->all()
        );
        $this->assertSame(
            ['paynamics'],
            $service->getEnabledGatewayCurrencies(true)->pluck('gateway_alias')->all()
        );
        $this->assertTrue($service->getEnabledPaynamicsMethods(false)->isEmpty());
        $this->assertCount(1, $service->getEnabledPaynamicsMethods(true));

        try {
            $service->validateGatewayCurrency(126, 'PHP', false);
            $this->fail('Paynamics should be unavailable for Online bookings.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('gateway', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        $service->validateGatewayCurrency(1001, 'PHP', true);
    }

    public function test_payment_settings_routes_require_admin_setting_permission(): void
    {
        $route = app('router')->getRoutes()->getByName('admin.payment.settings.edit');

        $this->assertNotNull($route);
        $this->assertContains('admin', $route->gatherMiddleware());
        $this->assertContains('role:admin.setting.system', $route->gatherMiddleware());
    }

    public function test_online_bank_transfer_is_listed_first(): void
    {
        $this->seedGateways();
        $this->seedPaynamicsChannel();

        $method = PaynamicsPaymentMethod::create([
            'name' => 'Online Bank Transfer',
            'code' => 'onlinebanktransfer',
            'is_enabled' => true,
            'sort_order' => 99,
        ]);

        PaynamicsPaymentChannel::create([
            'paynamics_payment_method_id' => $method->id,
            'name' => 'BPI Online',
            'code' => 'bpi_online',
            'icon_url' => 'https://icons.example/bpi.png',
            'is_enabled' => true,
            'sort_order' => 0,
        ]);

        $methods = app(PaymentGatewayService::class)->getEnabledPaynamicsMethods();

        $this->assertSame('onlinebanktransfer', $methods->first()->code);
        $this->assertSame('https://icons.example/bpi.png', $methods->first()->channels->first()->icon_url);
    }

    private function seedGateways(): void
    {
        foreach ([
            ['code' => 1001, 'name' => 'Cash', 'alias' => 'cash'],
            ['code' => 126, 'name' => 'Paynamics', 'alias' => 'paynamics'],
            ['code' => 103, 'name' => 'Legacy Card', 'alias' => 'legacy-card'],
        ] as $gateway) {
            DB::table('gateways')->insert($gateway + [
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('gateway_currencies')->insert([
                'name' => $gateway['name'],
                'currency' => 'PHP',
                'method_code' => $gateway['code'],
                'gateway_alias' => $gateway['alias'],
                'min_amount' => 1,
                'max_amount' => 100000,
                'fixed_charge' => 0,
                'percent_charge' => 0,
                'rate' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Cache::flush();
    }

    private function seedPaynamicsChannel(): array
    {
        $method = PaynamicsPaymentMethod::create([
            'name' => 'E-Wallet',
            'code' => 'wallet',
            'is_enabled' => true,
            'sort_order' => 0,
        ]);

        $channel = PaynamicsPaymentChannel::create([
            'paynamics_payment_method_id' => $method->id,
            'name' => 'GCash',
            'code' => 'gc',
            'is_enabled' => true,
            'sort_order' => 0,
        ]);

        return [$method, $channel];
    }
}
