<section class="payment-section paynamics-channel-section d-none js-paynamics-channels" aria-live="polite">
    <div class="paynamics-channel-heading">
        <div>
            <h5>Choose a Payment Channel</h5>
        </div>
        <i class="las la-shield-alt" aria-hidden="true"></i>
    </div>

    <div class="paynamics-methods">
        @forelse ($paynamicsMethods as $method)
            @php($isOpen = $method->code === 'onlinebanktransfer' || ($loop->first && !$paynamicsMethods->contains('code', 'onlinebanktransfer')))
            <div class="paynamics-method {{ $isOpen ? 'is-open' : '' }}">
                <button type="button" class="paynamics-method__toggle" aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                    aria-controls="paynamics-method-{{ $method->id }}">
                    <span>{{ $method->name }}</span>
                    <i class="las la-angle-down" aria-hidden="true"></i>
                </button>
                <div class="paynamics-method__channels {{ $isOpen ? '' : 'd-none' }}"
                    id="paynamics-method-{{ $method->id }}">
                    @foreach ($method->channels as $channel)
                        <label class="paynamics-channel">
                            <input type="radio" name="pchannel" value="{{ $channel->code }}"
                                data-pmethod="{{ $method->code }}">
                            <span class="paynamics-channel__logo">
                                @if ($channel->icon_url)
                                    <img src="{{ $channel->icon_url }}" alt="" loading="lazy"
                                        referrerpolicy="no-referrer">
                                @else
                                    <i class="las la-wallet" aria-hidden="true"></i>
                                @endif
                            </span>
                            <span class="paynamics-channel__name">{{ $channel->name }}</span>
                            <span class="paynamics-channel__check"><i class="las la-check"></i></span>
                        </label>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="payment-notice mb-0">
                <span class="payment-notice__icon"><i class="las la-info-circle"></i></span>
                <div>
                    <strong>No Paynamics channel available</strong>
                    <p>No Paynamics channels are enabled for this booking channel.</p>
                </div>
            </div>
        @endforelse
    </div>

    <input type="hidden" name="pmethod" id="selectedPaynamicsMethod">
</section>
