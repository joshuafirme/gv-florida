@extends('admin.layouts.app')

@section('panel')
    @php
        $cashCurrency = $cashGateway->singleCurrency;
        $paynamicsCurrency = $paynamicsGateway->singleCurrency;
    @endphp

    <form action="{{ route('admin.payment.settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-1">Booking Payment Methods</h5>
                <p class="text-muted mb-0">These settings apply to both Online and Kiosk bookings.</p>
            </div>
            <div class="card-body">
                @foreach ([
                    'cash' => ['title' => 'Cash Payment', 'gateway' => $cashGateway, 'currency' => $cashCurrency],
                    'paynamics' => ['title' => 'Paynamics', 'gateway' => $paynamicsGateway, 'currency' => $paynamicsCurrency],
                ] as $key => $setting)
                    <div class="payment-gateway-row {{ !$loop->last ? 'border-bottom pb-4 mb-4' : '' }}">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <div>
                                <h6 class="mb-1">{{ $setting['title'] }}</h6>
                                <small class="text-muted">Configure where this payment method can be used.</small>
                            </div>
                            <div class="gateway-availability">
                                <label class="gateway-availability__option">
                                    <span>Online</span>
                                    <span class="form-check form-switch mb-0">
                                        <input type="hidden" name="gateways[{{ $key }}][online_enabled]" value="0">
                                        <input class="form-check-input" type="checkbox"
                                            name="gateways[{{ $key }}][online_enabled]" value="1"
                                            @checked(old("gateways.{$key}.online_enabled", $setting['currency']->online_enabled))>
                                    </span>
                                </label>
                                <label class="gateway-availability__option">
                                    <span>Kiosk</span>
                                    <span class="form-check form-switch mb-0">
                                        <input type="hidden" name="gateways[{{ $key }}][kiosk_enabled]" value="0">
                                        <input class="form-check-input" type="checkbox"
                                            name="gateways[{{ $key }}][kiosk_enabled]" value="1"
                                            @checked(old("gateways.{$key}.kiosk_enabled", $setting['currency']->kiosk_enabled))>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="row gy-3">
                            <div class="col-lg-4">
                                <label class="form-label">Passenger Instructions</label>
                                <input type="text" class="form-control"
                                    name="gateways[{{ $key }}][description]"
                                    value="{{ old("gateways.{$key}.description", $setting['currency']->description) }}"
                                    placeholder="{{ $key === 'cash' ? 'Pay at the cashier with the printed voucher' : 'Choose a secure Paynamics payment channel' }}">
                            </div>
                            <div class="col-sm-6 col-lg-2">
                                <label class="form-label">Minimum</label>
                                <input type="number" class="form-control" min="0" step="0.01"
                                    name="gateways[{{ $key }}][min_amount]"
                                    value="{{ old("gateways.{$key}.min_amount", getAmount($setting['currency']->min_amount)) }}" required>
                            </div>
                            <div class="col-sm-6 col-lg-2">
                                <label class="form-label">Maximum</label>
                                <input type="number" class="form-control" min="0" step="0.01"
                                    name="gateways[{{ $key }}][max_amount]"
                                    value="{{ old("gateways.{$key}.max_amount", getAmount($setting['currency']->max_amount)) }}" required>
                            </div>
                            <div class="col-sm-4 col-lg-1">
                                <label class="form-label">Fixed Fee</label>
                                <input type="number" class="form-control" min="0" step="0.01"
                                    name="gateways[{{ $key }}][fixed_charge]"
                                    value="{{ old("gateways.{$key}.fixed_charge", getAmount($setting['currency']->fixed_charge)) }}" required>
                            </div>
                            <div class="col-sm-4 col-lg-2">
                                <label class="form-label">Percent Fee</label>
                                <input type="number" class="form-control" min="0" max="100" step="0.01"
                                    name="gateways[{{ $key }}][percent_charge]"
                                    value="{{ old("gateways.{$key}.percent_charge", getAmount($setting['currency']->percent_charge)) }}" required>
                            </div>
                            <div class="col-sm-4 col-lg-1">
                                <label class="form-label">Rate</label>
                                <input type="number" class="form-control" min="0.01" step="0.01"
                                    name="gateways[{{ $key }}][rate]"
                                    value="{{ old("gateways.{$key}.rate", getAmount($setting['currency']->rate)) }}" required>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">Paynamics Categories &amp; Channels</h5>
                <p class="text-muted mb-0">Configure Online and Kiosk availability for every Paynamics channel.</p>
            </div>
            <div class="card-body p-0">
                @forelse ($methods as $method)
                    <section class="paynamics-method {{ !$loop->last ? 'border-bottom' : '' }}"
                        data-method="{{ $method->id }}">
                        <div class="method-header">
                            <div>
                                <strong>{{ $method->name }}</strong>
                                <code>{{ $method->code }}</code>
                            </div>
                            <label class="form-check form-switch mb-0">
                                <input type="hidden" name="methods[{{ $method->id }}][id]" value="{{ $method->id }}">
                                <input type="hidden" name="methods[{{ $method->id }}][is_enabled]" value="0">
                                <input class="form-check-input method-toggle" type="checkbox"
                                    name="methods[{{ $method->id }}][is_enabled]" value="1"
                                    @checked(old("methods.{$method->id}.is_enabled", $method->is_enabled))>
                            </label>
                        </div>
                        <div class="channel-grid">
                            @foreach ($method->channels as $channel)
                                <div class="channel-toggle">
                                    <span>
                                        <strong>{{ $channel->name }}</strong>
                                        <code>{{ $channel->code }}</code>
                                    </span>
                                    <input type="hidden" name="channels[{{ $channel->id }}][id]" value="{{ $channel->id }}">
                                    <div class="channel-availability">
                                        <label class="channel-availability__option">
                                            <span>Online</span>
                                            <span class="form-check form-switch mb-0">
                                                <input type="hidden" name="channels[{{ $channel->id }}][online_enabled]" value="0">
                                                <input class="form-check-input" type="checkbox"
                                                    name="channels[{{ $channel->id }}][online_enabled]" value="1"
                                                    @checked(old("channels.{$channel->id}.online_enabled", $channel->online_enabled))>
                                            </span>
                                        </label>
                                        <label class="channel-availability__option">
                                            <span>Kiosk</span>
                                            <span class="form-check form-switch mb-0">
                                                <input type="hidden" name="channels[{{ $channel->id }}][kiosk_enabled]" value="0">
                                                <input class="form-check-input" type="checkbox"
                                                    name="channels[{{ $channel->id }}][kiosk_enabled]" value="1"
                                                    @checked(old("channels.{$channel->id}.kiosk_enabled", $channel->kiosk_enabled))>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="channel-icon-field">
                                        <span class="channel-icon-field__label">
                                            <i class="las la-link"></i> Icon URL
                                        </span>
                                        <div class="input-group">
                                            <input type="url" class="form-control"
                                                name="channels[{{ $channel->id }}][icon_url]"
                                                value="{{ old("channels.{$channel->id}.icon_url", $channel->icon_url) }}"
                                                placeholder="https://example.com/icon.png">
                                            @if ($channel->icon_url)
                                                <a class="btn btn-outline--primary" href="{{ $channel->icon_url }}"
                                                    target="_blank" rel="noopener noreferrer"
                                                    title="Preview {{ $channel->name }} icon">
                                                    <i class="las la-external-link-alt"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="text-center text-muted p-4">
                        Run the Paynamics payment method importer to configure categories and channels.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3">
            <button type="submit" class="btn btn--primary">
                <i class="las la-save"></i> Save Payment Settings
            </button>
        </div>
    </form>
@endsection

@push('style')
    <style>
        .method-header,
        .channel-toggle {
            align-items: center;
            display: flex;
            justify-content: space-between;
        }

        .gateway-availability {
            align-items: center;
            display: flex;
            gap: 8px;
        }

        .gateway-availability__option {
            align-items: center;
            background: #f7f8fa;
            border: 1px solid #e8eaed;
            border-radius: 6px;
            display: flex;
            font-size: 12px;
            font-weight: 600;
            gap: 8px;
            margin: 0;
            min-height: 40px;
            padding: 6px 10px;
        }

        .method-header {
            background: #f7f8fa;
            gap: 16px;
            padding: 14px 18px;
        }

        .method-header code,
        .channel-toggle code {
            color: #7b8490;
            display: block;
            font-size: 11px;
            margin-top: 2px;
        }

        .channel-grid {
            display: grid;
            gap: 0;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .channel-toggle {
            align-items: flex-start;
            border-right: 1px solid #e8eaed;
            border-top: 1px solid #e8eaed;
            flex-direction: column;
            gap: 12px;
            min-height: 158px;
            padding: 12px 18px;
        }

        .channel-availability {
            display: flex;
            gap: 8px;
            width: 100%;
        }

        .channel-availability__option {
            align-items: center;
            background: #f7f8fa;
            border: 1px solid #e8eaed;
            border-radius: 6px;
            display: flex;
            flex: 1;
            font-size: 11px;
            font-weight: 600;
            justify-content: space-between;
            margin: 0;
            min-width: 0;
            padding: 5px 8px;
        }

        .channel-icon-field {
            width: 100%;
        }

        .channel-icon-field__label {
            color: #616b78;
            display: block;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .channel-icon-field .form-control,
        .channel-icon-field .btn {
            min-height: 36px;
        }

        .channel-icon-field .btn {
            align-items: center;
            display: inline-flex;
            justify-content: center;
            width: 38px;
        }

        .channel-toggle:nth-child(3n) {
            border-right: 0;
        }

        .paynamics-method.is-disabled .channel-grid {
            display: none;
        }

        @media (max-width: 991px) {
            .channel-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .channel-toggle:nth-child(3n) {
                border-right: 1px solid #e8eaed;
            }

            .channel-toggle:nth-child(2n) {
                border-right: 0;
            }
        }

        @media (max-width: 575px) {
            .gateway-availability {
                align-items: stretch;
                flex-direction: column;
                width: 100%;
            }

            .gateway-availability__option {
                justify-content: space-between;
            }

            .channel-grid {
                grid-template-columns: 1fr;
            }

            .channel-toggle {
                border-right: 0 !important;
            }

            .channel-availability {
                flex-direction: column;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';

            function syncMethod(method) {
                method.toggleClass('is-disabled', !method.find('.method-toggle').is(':checked'));
            }

            $('.paynamics-method').each(function() {
                syncMethod($(this));
            });

            $('.method-toggle').on('change', function() {
                syncMethod($(this).closest('.paynamics-method'));
            });
        })(jQuery);
    </script>
@endpush
