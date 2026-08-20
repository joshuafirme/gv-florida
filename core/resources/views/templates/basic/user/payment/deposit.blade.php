@php
    $isKioskBooking = $isKioskBooking ?? $bookedTicket->isKioskBooking();
    $kiosk_id = $isKioskBooking ? $bookedTicket->kiosk_id : null;
    if ($isKioskBooking) {
        $layout = 'layouts.kiosk';
    }

    $seats = $bookedTicket->seats ? $bookedTicket->seats : session('seats');
    $seats = is_array($seats) ? array_values($seats) : [];
    $seatCount = count($seats);
    $seatWord = $seatCount === 1 ? 'seat' : 'seats';
    $unitPrice = getAmount($bookedTicket->unit_price);
    $discountOptions = $discounts
        ->map(function ($discount) {
            return [
                'id' => $discount->id,
                'name' => $discount->name,
                'percentage' => getAmount($discount->percentage),
            ];
        })
        ->values();
@endphp

@extends($activeTemplate . $layout)

@section('content')
    @if ($kiosk_id)
        @include('templates.basic.partials.kiosk_nav')
    @endif

    <div class="passenger-flow-wrap">
        <div class="container">
            @include('templates.basic.partials.booking_stepper', ['currentStep' => 'details'])

            <form action="{{ route('user.deposit.release-seats') }}" method="POST" class="seat-release-form"
                id="seatReleaseForm">
                @csrf
                <input type="hidden" name="booked_ticket_id" value="{{ $bookedTicket->id }}">
                <button type="submit" class="flow-back-btn">
                    <i class="las la-arrow-left"></i> Back to seat selection
                </button>
            </form>

            <form action="{{ route('user.deposit.insert') }}" method="post" class="deposit-form" id="passengerFlowForm">
                @csrf
                <input type="hidden" name="currency">
                <input type="hidden" name="amount" value="{{ getAmount($bookedTicket->sub_total) }}">
                <input type="hidden" name="passengers">
                @if ($isKioskBooking)
                    <input type="hidden" name="discount_authorized" value="0">
                    <input type="hidden" name="authorization_method">
                    <input type="hidden" name="authorized_by_admin_id">
                    <input type="hidden" name="authorized_by_name">
                    <input type="hidden" name="authorization_reference">
                @endif

                <section class="passenger-details-step js-step-panel" data-panel="details">
                    <div class="flow-panel passenger-details-header">
                        <div class="flow-title-row">
                            <div class="flow-title-icon"><i class="las la-users"></i></div>
                            <div class="flow-title-copy">
                                <h4>Passenger Details</h4>
                                <div class="trip-meta">
                                    <span><i class="las la-bus"></i>{{ $bookedTicket->pickup->name ?? $bookedTicket->trip->startFrom->name }} &rarr; {{ $bookedTicket->drop->name ?? $bookedTicket->trip->endTo->name }}</span>
                                    <span><i class="las la-calendar"></i>{{ showDateTime($bookedTicket->date_of_journey, 'M d, Y') }}</span>
                                    <span><i class="las la-clock"></i>{{ showDateTime($bookedTicket->trip->schedule->start_from, 'h:i A') }}</span>
                                </div>
                            </div>
                            <span class="passenger-count">{{ $seatCount }} {{ $seatCount === 1 ? 'Passenger' : 'Passengers' }}</span>
                        </div>
                    </div>

                    @foreach ($seats as $index => $seat)
                        <div class="passenger-card" data-seat="{{ $seat }}">
                            <div class="passenger-card__head">
                                <span>Passenger {{ $index + 1 }}</span>
                                <strong>{{ formatSeatLabel($seat) }}</strong>
                            </div>

                            <div class="passenger-card__body">
                                <div class="passenger-primary-fields">
                                    <div>
                                        <label class="flow-label">Full Name <span class="js-name-note">(optional)</span></label>
                                        <input type="text" class="flow-input js-passenger-name" placeholder="Enter passenger name">
                                    </div>
                                    <div>
                                        <label class="flow-label">Passenger Type</label>
                                        <div class="passenger-type-dropdown">
                                            <select class="passenger-type-select" tabindex="-1" aria-hidden="true">
                                                <option value="regular" data-type="regular" data-discount-id="">Regular</option>
                                                @if ($isKioskBooking)
                                                    @foreach ($discountOptions as $discount)
                                                        <option value="discounted-{{ $discount['id'] }}" data-type="discounted"
                                                            data-discount-id="{{ $discount['id'] }}"
                                                            data-discount-name="{{ $discount['name'] }}"
                                                            data-percentage="{{ $discount['percentage'] }}">
                                                            {{ $discount['name'] }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>

                                            <button type="button" class="flow-input passenger-type-trigger"
                                                aria-haspopup="listbox" aria-expanded="false"
                                                aria-controls="passengerTypeMenu{{ $index }}">
                                                <span class="js-passenger-type-label">Regular</span>
                                                <i class="las la-angle-down" aria-hidden="true"></i>
                                            </button>
                                            <div class="passenger-type-menu" id="passengerTypeMenu{{ $index }}"
                                                role="listbox" aria-label="Passenger Type">
                                                <button type="button" class="passenger-type-option is-selected"
                                                    data-value="regular" role="option" aria-selected="true">
                                                    <span>Regular</span>
                                                    <i class="las la-check" aria-hidden="true"></i>
                                                </button>
                                                @if ($isKioskBooking)
                                                    @foreach ($discountOptions as $discount)
                                                        <button type="button" class="passenger-type-option"
                                                            data-value="discounted-{{ $discount['id'] }}" role="option"
                                                            aria-selected="false">
                                                            <span>{{ $discount['name'] }}</span>
                                                            <i class="las la-check" aria-hidden="true"></i>
                                                        </button>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="discount-fields d-none">
                                    <label class="flow-label"><span class="js-id-label">ID Number</span> <span>(required)</span></label>
                                    <input type="text" class="flow-input js-id-number" placeholder="Enter passenger ID number">
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if ($isKioskBooking)
                        <div class="authorization-panel d-none" id="authorizationPanel">
                            <div class="authorization-heading">
                                <div class="auth-icon"><i class="las la-shield-alt"></i></div>
                                <div>
                                    <h5>Authorization Required</h5>
                                    <p>An authorized employee must approve this transaction before payment can continue. Please request assistance and enter the authorization code.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <button type="button" class="btn-primary-flow w-100 mt-3" id="continueToPayment">
                        <i class="las la-lock"></i> Continue to Payment
                    </button>
                    <p class="flow-security-note"><i class="las la-lock"></i> Your transaction is secure and protected.</p>
                </section>

                <section class="payment-step js-step-panel d-none" data-panel="payment">
                    <button type="button" class="flow-back-btn" id="backToDetails">
                        <i class="las la-arrow-left"></i> Back to details
                    </button>

                    <h4 class="payment-title">Payment</h4>

                    <div class="payment-section payment-method-section">
                        <label class="flow-label">Payment Method</label>
                        <div class="payment-methods">
                            @forelse ($gatewayCurrency as $data)
                                @php
                                    $description = $data->description ?: ($data->name == 'Cash' ? 'Pay at the cashier with the printed voucher' : 'Follow the payment instructions on the next screen');
                                @endphp
                                <label class="payment-method-card">
                                    <input class="gateway-input" data-gateway='@json($data)' type="radio" name="gateway"
                                        value="{{ $data->method_code }}" data-min-amount="{{ showAmount($data->min_amount) }}"
                                        data-max-amount="{{ showAmount($data->max_amount) }}"
                                        data-alias="{{ strtolower($data->method?->alias ?: $data->gateway_alias) }}"
                                        @checked($loop->first)>
                                    <span class="method-icon"><i class="las {{ $data->name == 'Cash' ? 'la-money-bill' : 'la-credit-card' }}"></i></span>
                                    <span class="method-copy">
                                        <strong>{{ __($data->name) }}</strong>
                                        <small>{{ __($description) }}</small>
                                    </span>
                                    <span class="method-check"><i class="las la-check"></i></span>
                                </label>
                            @empty
                                <div class="payment-notice mb-0">
                                    <span class="payment-notice__icon"><i class="las la-info-circle"></i></span>
                                    <div>
                                        <strong>No payment method available</strong>
                                        <p>Payment methods are currently disabled for this booking channel. Please request assistance.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @include('templates.basic.user.payment.partials.paynamics-channels')

                    <div class="payment-section payment-details-section">
                        <h5>Payment Details</h5>
                        <div class="js-payment-breakdown"></div>
                        <div class="summary-total">
                            <span>Total Amount</span>
                            <strong class="js-payment-total">{{ showAmount($bookedTicket->sub_total) }}</strong>
                        </div>

                        <div class="payment-notice">
                            <span class="payment-notice__icon"><i class="las la-info-circle"></i></span>
                            <div>
                                <strong>Please note</strong>
                                <p class="payment-instructions js-payment-instructions"></p>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary-flow w-100" id="confirmPayment">
                            <i class="las la-print"></i> Confirm &amp; Print Voucher
                        </button>
                    </div>
                </section>
            </form>

            @if ($isKioskBooking)
                <div class="modal fade discount-auth-modal" id="discountAuthorizationModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="auth-modal-icon"><i class="las la-shield-alt"></i></div>
                            <h5>Authorization Required</h5>
                            <p class="auth-modal-copy">An authorized employee must approve this transaction before payment can continue.</p>

                            <label class="flow-label text-start">Authorization Code</label>
                            <input type="password" class="flow-input" id="authPasscode" placeholder="Enter authorization code" autocomplete="new-password">

                            <div class="auth-actions auth-actions--single">
                                <button type="button" class="btn-light-flow" id="cancelAuthorization">Cancel</button>
                            </div>
                            <div class="auth-status js-auth-modal-status"></div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('style')
    <style>
        .passenger-flow-wrap {
            background: #f3f5f7;
            min-height: 100vh;
            padding: 8px 0 24px;
        }

        .passenger-flow-wrap input::placeholder,
        .passenger-flow-wrap textarea::placeholder {
            font-style: italic;
            opacity: .58;
        }

        .flow-back-btn {
            background: transparent;
            border: 0;
            color: #7b8490;
            display: inline-flex;
            gap: 6px;
            font-weight: 700;
            padding: 0;
            margin-bottom: 8px;
        }

        .flow-panel {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 8px rgba(15, 23, 42, .06);
            margin-top: 10px;
            padding: 18px;
        }

        .passenger-details-header {
            margin-top: 10px;
        }

        .flow-title-row,
        .authorization-heading {
            align-items: center;
            display: flex;
            gap: 12px;
        }

        .flow-title-copy {
            flex: 1;
            min-width: 0;
        }

        .flow-title-row h4,
        .authorization-heading h5,
        .payment-title {
            color: #111827;
            font-weight: 800;
            margin: 0;
        }

        .authorization-heading p {
            color: #7b8490;
            margin: 3px 0 0;
        }

        .trip-meta {
            align-items: center;
            color: #667085;
            display: flex;
            flex-wrap: wrap;
            font-size: 12px;
            gap: 7px 16px;
            margin-top: 5px;
        }

        .trip-meta span {
            align-items: center;
            display: inline-flex;
            gap: 5px;
        }

        .passenger-count {
            background: var(--booking-primary-soft);
            border-radius: 4px;
            color: var(--booking-primary);
            flex: 0 0 auto;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 8px;
        }

        .flow-title-icon,
        .auth-icon {
            align-items: center;
            background: var(--booking-primary-soft);
            border-radius: 8px;
            color: var(--booking-primary);
            display: flex;
            flex: 0 0 44px;
            font-size: 20px;
            height: 40px;
            justify-content: center;
            width: 40px;
        }

        .auth-icon {
            background: #fff1c7;
            color: #d97706;
        }

        .passenger-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 1px 8px rgba(15, 23, 42, .04);
            margin-top: 12px;
            position: relative;
        }

        .passenger-card.is-dropdown-open {
            z-index: 20;
        }

        .passenger-card__head {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #edf0f3;
            display: flex;
            gap: 10px;
            justify-content: flex-start;
            padding: 10px 16px;
            border-radius: 7px 7px 0 0;
        }

        .passenger-card__head span {
            color: #7b8490;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .passenger-card__head strong {
            background: var(--booking-primary-soft);
            border-radius: 4px;
            color: var(--booking-primary);
            font-size: 11px;
            font-weight: 800;
            padding: 4px 8px;
        }

        .passenger-card__body {
            padding: 10px 16px 16px;
        }

        .passenger-primary-fields {
            align-items: end;
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1.8fr) minmax(180px, 1fr);
        }

        .flow-label {
            color: #7b8490;
            display: block;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .seat-price strong {
            color: var(--booking-primary);
            font-weight: 800;
        }

        .flow-input {
            border: 1px solid #dfe3e8;
            border-radius: 8px;
            color: #111827;
            height: 42px;
            outline: none;
            padding: 0 13px;
            width: 100%;
        }

        .flow-input:focus {
            border-color: var(--booking-primary);
            box-shadow: 0 0 0 3px var(--booking-primary-focus);
        }

        .passenger-type-dropdown {
            position: relative;
        }

        .passenger-type-select {
            height: 1px;
            left: 0;
            opacity: 0;
            pointer-events: none;
            position: absolute;
            top: 0;
            width: 1px;
        }

        .passenger-type-trigger {
            align-items: center;
            background: #fff;
            cursor: pointer;
            display: flex;
            font-weight: 700;
            justify-content: space-between;
            text-align: left;
            touch-action: manipulation;
        }

        .passenger-type-trigger i {
            color: #98a2b3;
            font-size: 15px;
            transition: transform .18s ease;
        }

        .passenger-type-dropdown.is-open .passenger-type-trigger {
            border-color: var(--booking-primary);
            box-shadow: 0 0 0 3px var(--booking-primary-focus);
        }

        .passenger-type-dropdown.is-open .passenger-type-trigger i {
            transform: rotate(180deg);
        }

        .passenger-type-menu {
            background: #fff;
            border: 1px solid #edf0f3;
            border-radius: 8px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .16);
            display: none;
            left: 0;
            margin-top: 6px;
            overflow: hidden;
            position: absolute;
            right: 0;
            top: 100%;
            z-index: 30;
        }

        .passenger-type-dropdown.is-open .passenger-type-menu {
            display: block;
        }

        .passenger-type-option {
            align-items: center;
            background: #fff;
            border: 0;
            color: #344054;
            display: flex;
            font-size: 14px;
            justify-content: space-between;
            min-height: 46px;
            padding: 10px 16px;
            text-align: left;
            touch-action: manipulation;
            width: 100%;
        }

        .passenger-type-option:hover,
        .passenger-type-option:focus-visible {
            background: #f8fafc;
            outline: 0;
        }

        .passenger-type-option i {
            color: var(--booking-primary);
            font-size: 17px;
            opacity: 0;
        }

        .passenger-type-option.is-selected {
            background: var(--booking-primary-soft);
            color: var(--booking-primary);
            font-weight: 800;
        }

        .passenger-type-option.is-selected i {
            opacity: 1;
        }

        .flow-label {
            margin: 11px 0 6px;
            text-transform: none;
        }

        .payment-total-box {
            background: #f8fafc;
            border-radius: 8px;
            margin-top: 12px;
            padding: 14px;
        }

        .summary-line,
        .summary-total {
            align-items: center;
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 6px 0;
        }

        .summary-line {
            color: #667085;
            font-size: 13px;
            font-weight: 600;
        }

        .summary-line--discount {
            color: #15803d;
        }

        .summary-total {
            border-top: 1px solid #e5e7eb;
            color: #111827;
            font-weight: 800;
            margin-top: 8px;
            padding-top: 14px;
        }

        .summary-total strong {
            color: var(--booking-primary);
            font-size: 25px;
        }

        .authorization-panel {
            background: var(--booking-primary-soft);
            border: 1px solid var(--booking-primary-border);
            border-radius: 8px;
            margin-top: 12px;
            padding: 14px;
        }

        .tap-target {
            align-items: center;
            border: 4px solid #e5e7eb;
            border-radius: 999px;
            color: #cbd5e1;
            display: flex;
            font-size: 50px;
            height: 104px;
            justify-content: center;
            margin: 18px auto 10px;
            width: 104px;
        }

        .auth-actions {
            display: grid;
            gap: 10px;
            grid-template-columns: 1fr 1fr;
            margin-top: 16px;
        }

        .auth-actions--single,
        .discount-auth-modal .auth-actions {
            grid-template-columns: 1fr;
        }

        .authorization-panel .auth-actions {
            grid-template-columns: 1fr;
        }

        .discount-auth-modal .modal-content {
            border: 0;
            border-radius: 14px;
            padding: 24px;
            text-align: center;
        }

        .discount-auth-modal h5 {
            color: #111827;
            font-weight: 900;
            margin: 0 0 6px;
        }

        .auth-modal-icon {
            align-items: center;
            background: #fff1c7;
            border-radius: 14px;
            color: #d97706;
            display: inline-flex;
            font-size: 28px;
            height: 52px;
            justify-content: center;
            margin: 0 auto 14px;
            width: 52px;
        }

        .auth-modal-copy {
            color: #64748b;
            margin: 0 auto 14px;
            max-width: 360px;
        }

        .btn-primary-flow,
        .btn-light-flow {
            border: 0;
            border-radius: 8px;
            font-weight: 800;
            min-height: 42px;
            padding: 0 16px;
        }

        .btn-primary-flow {
            background: var(--booking-primary);
            color: var(--booking-on-primary);
        }

        .btn-primary-flow:hover,
        .btn-primary-flow:focus {
            background: var(--booking-primary-hover);
            color: var(--booking-on-primary);
        }

        .btn-primary-flow i {
            margin-right: 5px;
        }

        .flow-security-note {
            color: #8a94a3;
            font-size: 11px;
            margin: 7px 0 0;
            text-align: center;
        }

        .btn-light-flow {
            background: #f1f5f9;
            color: #334155;
        }

        .btn-primary-flow:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
        }

        .auth-status {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            margin-top: 10px;
            text-align: center;
        }

        .payment-step {
            margin-top: 10px;
        }

        .payment-step .payment-title {
            margin: 8px 0 12px;
        }

        .payment-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 8px rgba(15, 23, 42, .06);
            padding: 16px;
        }

        .payment-section + .payment-section {
            margin-top: 12px;
        }

        .payment-method-section .flow-label {
            margin-top: 0;
        }

        .payment-details-section h5 {
            color: #111827;
            font-weight: 800;
            margin: 0 0 10px;
        }

        .payment-methods {
            display: grid;
            gap: 8px;
        }

        .payment-method-card {
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            gap: 10px;
            min-height: 72px;
            padding: 13px 16px;
        }

        .payment-method-card:has(input:checked) {
            background: var(--booking-primary-soft);
            border-color: var(--booking-primary);
        }

        .payment-method-card input {
            display: none;
        }

        .method-icon,
        .method-check {
            color: var(--booking-primary);
            font-size: 20px;
        }

        .method-icon {
            align-items: center;
            display: inline-flex;
            justify-content: center;
            width: 28px;
        }

        .method-copy {
            flex: 1;
        }

        .method-copy strong,
        .method-copy small {
            display: block;
        }

        .method-copy small {
            color: #7b8490;
            margin-top: 2px;
        }

        .paynamics-channel-heading {
            align-items: center;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .paynamics-channel-heading h5 {
            color: #111827;
            font-weight: 800;
            margin: 0;
        }

        .paynamics-channel-heading p {
            color: #7b8490;
            font-size: 12px;
            margin: 3px 0 0;
        }

        .paynamics-channel-heading > i {
            color: var(--booking-primary);
            font-size: 24px;
        }

        .paynamics-methods {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .paynamics-method + .paynamics-method {
            border-top: 1px solid #e5e7eb;
        }

        .paynamics-method__toggle {
            align-items: center;
            background: #fff;
            border: 0;
            color: #334155;
            display: flex;
            font-weight: 800;
            justify-content: space-between;
            min-height: 46px;
            padding: 0 14px;
            text-align: left;
            width: 100%;
        }

        .paynamics-method.is-open .paynamics-method__toggle {
            background: #f8fafc;
            color: var(--booking-primary);
        }

        .paynamics-method__toggle i {
            transition: transform .2s ease;
        }

        .paynamics-method.is-open .paynamics-method__toggle i {
            transform: rotate(180deg);
        }

        .paynamics-method__channels {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            padding: 10px;
        }

        .paynamics-channel {
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            gap: 9px;
            min-height: 54px;
            padding: 8px 10px;
        }

        .paynamics-channel:has(input:checked) {
            background: var(--booking-primary-soft);
            border-color: var(--booking-primary);
        }

        .paynamics-channel input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .paynamics-channel__logo {
            align-items: center;
            display: inline-flex;
            flex: 0 0 32px;
            height: 28px;
            justify-content: center;
        }

        .paynamics-channel__logo img {
            max-height: 28px;
            max-width: 32px;
            object-fit: contain;
        }

        .paynamics-channel__logo i {
            color: var(--booking-primary);
            font-size: 22px;
        }

        .paynamics-channel__name {
            color: #334155;
            flex: 1;
            font-size: 13px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .paynamics-channel__check {
            color: var(--booking-primary);
            opacity: 0;
        }

        .paynamics-channel:has(input:checked) .paynamics-channel__check {
            opacity: 1;
        }

        .payment-instructions {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            margin: 3px 0 0;
        }

        .payment-notice {
            align-items: flex-start;
            background: #f4f7ff;
            border-radius: 7px;
            display: flex;
            gap: 12px;
            margin: 12px 0;
            padding: 13px;
        }

        .payment-notice__icon {
            color: #2563eb;
            flex: 0 0 auto;
            font-size: 25px;
            line-height: 1;
        }

        .payment-notice strong {
            color: #111827;
            display: block;
            font-size: 13px;
        }

        .payment-details-section .summary-line {
            font-size: 13px;
            padding: 5px 0;
        }

        .payment-details-section .summary-total {
            margin-top: 6px;
            padding-top: 12px;
        }

        .payment-details-section .summary-total strong {
            font-size: 25px;
        }

        #confirmPayment i {
            margin-right: 5px;
        }

        @media (max-width: 575px) {
            .flow-panel {
                padding: 16px;
            }

            .passenger-primary-fields {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .flow-title-row {
                align-items: flex-start;
            }

            .passenger-count {
                display: none;
            }

            .trip-meta {
                align-items: flex-start;
                flex-direction: column;
                gap: 3px;
            }

            .auth-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";

            const seats = @json($seats);
            const unitPrice = Number(@json($unitPrice));
            const discountsEnabled = @json($isKioskBooking);
            let passengerManifest = [];
            let totals = {
                subtotal: Number(@json(getAmount($bookedTicket->sub_total))),
                discount: 0,
                charge: 0,
                payable: Number(@json(getAmount($bookedTicket->sub_total))),
                final: Number(@json(getAmount($bookedTicket->sub_total)))
            };
            let gateway = null;
            let pendingPaymentAfterAuthorization = false;
            let authorizationTimer = null;
            let authorizationInFlight = false;
            let browserBackReleaseInProgress = false;

            const seatReleaseHistoryKey = 'gvPaymentSeatRelease';
            const seatReleaseHistoryState = String(@json($bookedTicket->id));
            const currentHistoryState = window.history.state || {};

            if (currentHistoryState[seatReleaseHistoryKey] !== seatReleaseHistoryState) {
                window.history.replaceState({
                    ...currentHistoryState,
                    [seatReleaseHistoryKey]: 'release-' + seatReleaseHistoryState
                }, document.title, window.location.href);
                window.history.pushState({
                    ...currentHistoryState,
                    [seatReleaseHistoryKey]: seatReleaseHistoryState
                }, document.title, window.location.href);
            }

            window.addEventListener('popstate', function(event) {
                const state = event.state || {};

                if (state[seatReleaseHistoryKey] !== 'release-' + seatReleaseHistoryState || browserBackReleaseInProgress) {
                    return;
                }

                const releaseForm = document.getElementById('seatReleaseForm');
                if (!releaseForm) {
                    window.history.back();
                    return;
                }

                browserBackReleaseInProgress = true;
                releaseForm.submit();
            });

            function money(amount) {
                return '{{ gs('cur_sym') }}' + Number(amount || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function formatSeatLabel(value) {
                return String(value || '').replace(/^\d+-/, '');
            }

            function showMessage(type, message) {
                if (typeof notify === 'function') {
                    notify(type, message);
                    return;
                }
                if (typeof triggerToaster === 'function') {
                    triggerToaster(type, message);
                    return;
                }
                alert(message);
            }

            function selectedGateway() {
                const gatewayElement = $('.gateway-input:checked');
                if (!gatewayElement.length) return null;
                return gatewayElement.data('gateway');
            }

            function selectedGatewayAlias() {
                return String($('.gateway-input:checked').data('alias') || '').toLowerCase();
            }

            function isPaynamicsSelected() {
                return selectedGatewayAlias() === 'paynamics';
            }

            function setStep(step) {
                const stepOrder = ['seat', 'details', 'payment', 'done'];
                const activeIndex = stepOrder.indexOf(step);
                const progress = activeIndex <= 0 ? 0 : activeIndex / (stepOrder.length - 1);

                $('.js-step-panel').addClass('d-none');
                $(`.js-step-panel[data-panel="${step}"]`).removeClass('d-none');
                $('.seat-release-form').toggleClass('d-none', step === 'payment');
                $('.flow-step').removeClass('is-active is-complete');
                $('.booking-flow-stepper').css('--booking-flow-progress', progress);

                stepOrder.forEach((stepName, index) => {
                    const stepNode = $(`.flow-step[data-step="${stepName}"]`);
                    const marker = stepNode.find('.booking-flow-step__marker');

                    if (index < activeIndex) {
                        stepNode.addClass('is-complete');
                        marker.html('<i class="las la-check"></i>');
                    } else if (index === activeIndex) {
                        stepNode.addClass('is-active');
                        marker.text(index + 1);
                    } else {
                        marker.text(index + 1);
                    }
                });
            }

            function resetAuthorization() {
                $('input[name="discount_authorized"]').val('0');
                $('input[name="authorization_method"]').val('');
                $('input[name="authorized_by_admin_id"]').val('');
                $('input[name="authorized_by_name"]').val('');
                $('input[name="authorization_reference"]').val('');
                $('.js-auth-status, .js-auth-modal-status').removeClass('text-success text-danger').text('');
            }

            function collectPassengers(showErrors = false) {
                const manifest = [];
                let errors = [];
                let discount = 0;

                $('.passenger-card').each(function() {
                    const card = $(this);
                    const selectedType = card.find('.passenger-type-select option:selected');
                    const passengerType = discountsEnabled ? (selectedType.data('type') || 'regular') : 'regular';
                    const discountId = discountsEnabled ? (selectedType.data('discount-id') || null) : null;
                    const discountName = discountsEnabled ? (selectedType.data('discount-name') || null) : null;
                    const percentage = discountsEnabled ? Number(selectedType.data('percentage') || 0) : 0;
                    const seat = String(card.data('seat'));
                    const name = $.trim(card.find('.js-passenger-name').val());
                    const idNumber = $.trim(card.find('.js-id-number').val());
                    const seatDiscount = passengerType === 'discounted' ? unitPrice * (percentage / 100) : 0;

                    if (passengerType === 'discounted') {
                        if (!name) errors.push(`Passenger name is required for seat ${formatSeatLabel(seat)}.`);
                        if (!idNumber) errors.push(`ID number is required for seat ${formatSeatLabel(seat)}.`);
                    }

                    discount += seatDiscount;
                    manifest.push({
                        seat: seat,
                        name: name,
                        passenger_type: passengerType,
                        discount_id: discountId,
                        discount_name: discountName,
                        discount_percentage: percentage,
                        id_number: passengerType === 'discounted' ? idNumber : '',
                        base_fare: unitPrice,
                        discount_amount: seatDiscount,
                        fare: unitPrice - seatDiscount
                    });
                });

                if (manifest.length !== seats.length) {
                    errors.push('Each selected seat must have a passenger type.');
                }

                passengerManifest = manifest;
                totals.discount = discount;
                totals.payable = Math.max(totals.subtotal - totals.discount, 0);

                if (showErrors && errors.length) {
                    showMessage('error', errors[0]);
                }

                return {
                    valid: errors.length === 0,
                    errors,
                    discounted: manifest.filter((item) => item.passenger_type === 'discounted')
                };
            }

            function renderSummary() {
                const state = collectPassengers();

                const discountLine = totals.discount > 0 ?
                    `<div class="summary-line"><span>Discount</span><strong>-${money(totals.discount)}</strong></div>` : '';
                $('.js-payment-breakdown').html(
                    `<div class="summary-line"><span>Base Fare (${seats.length} ${seats.length === 1 ? 'seat' : 'seats'})</span><strong>${money(totals.subtotal)}</strong></div>${discountLine}<div class="summary-line"><span>Processing Fee</span><strong class="js-processing-charge">${money(totals.charge)}</strong></div>`
                );
                $('.js-payment-total').text(money(totals.final));

                if (state.discounted.length) {
                    $('#authorizationPanel').removeClass('d-none');
                } else {
                    $('#authorizationPanel').addClass('d-none');
                    resetAuthorization();
                }

                $('#continueToPayment').html(state.discounted.length ?
                    '<i class="las la-lock"></i> Authorize & Continue' :
                    '<i class="las la-arrow-right"></i> Continue to Payment');
            }

            function calculateGateway() {
                gateway = selectedGateway();
                if (!gateway) return;

                const percentCharge = Number(gateway.percent_charge || 0);
                const fixedCharge = Number(gateway.fixed_charge || 0);
                totals.charge = (totals.payable / 100 * percentCharge) + fixedCharge;
                totals.final = (totals.payable + totals.charge) * Number(gateway.rate || 1);

                $('input[name="currency"]').val(gateway.currency);
                $('.js-processing-charge').text(money(totals.charge));
                $('.js-payment-total').text(money(totals.final));
                $('.js-paynamics-channels').toggleClass('d-none', !isPaynamicsSelected());

                if (isPaynamicsSelected()) {
                    $('.js-payment-instructions').text(gateway.description || 'Select an enabled Paynamics channel, then continue to its secure payment page.');
                    $('#confirmPayment').html('<i class="las la-lock"></i> Continue to Secure Payment');
                } else {
                    $('.js-payment-instructions').text(gateway.description || 'A payment voucher will be printed after confirmation. Present it at the Cashier Window to complete your payment.');
                    $('#confirmPayment').html('<i class="las la-print"></i> Confirm &amp; Print Voucher');
                }
            }

            function closePassengerTypeDropdown(dropdown) {
                dropdown.removeClass('is-open');
                dropdown.closest('.passenger-card').removeClass('is-dropdown-open');
                dropdown.find('.passenger-type-trigger').attr('aria-expanded', 'false');
            }

            function closePassengerTypeDropdowns(except = null) {
                $('.passenger-type-dropdown.is-open').each(function() {
                    const dropdown = $(this);
                    if (!except || dropdown[0] !== except[0]) {
                        closePassengerTypeDropdown(dropdown);
                    }
                });
            }

            function syncPassengerTypeDropdown(select) {
                const dropdown = select.closest('.passenger-type-dropdown');
                const selectedOption = select.find('option:selected');
                const value = String(select.val() || 'regular');

                dropdown.find('.js-passenger-type-label').text(selectedOption.text().trim());
                dropdown.find('.passenger-type-option').each(function() {
                    const option = $(this);
                    const isSelected = String(option.data('value')) === value;
                    option.toggleClass('is-selected', isSelected).attr('aria-selected', isSelected ? 'true' : 'false');
                });
            }

            $(document).on('click', '.passenger-type-trigger', function(event) {
                event.preventDefault();
                event.stopPropagation();

                const trigger = $(this);
                const dropdown = trigger.closest('.passenger-type-dropdown');
                const wasOpen = dropdown.hasClass('is-open');

                closePassengerTypeDropdowns();
                if (wasOpen) return;

                dropdown.addClass('is-open');
                dropdown.closest('.passenger-card').addClass('is-dropdown-open');
                trigger.attr('aria-expanded', 'true');
            });

            $(document).on('click', '.passenger-type-option', function(event) {
                event.preventDefault();
                event.stopPropagation();

                const option = $(this);
                const dropdown = option.closest('.passenger-type-dropdown');
                const select = dropdown.find('.passenger-type-select');

                select.val(String(option.data('value'))).trigger('change');
                closePassengerTypeDropdown(dropdown);
                dropdown.find('.passenger-type-trigger').trigger('focus');
            });

            $(document).on('keydown', '.passenger-type-trigger', function(event) {
                const dropdown = $(this).closest('.passenger-type-dropdown');

                if (event.key === 'Escape') {
                    closePassengerTypeDropdown(dropdown);
                    return;
                }

                if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;

                event.preventDefault();
                if (!dropdown.hasClass('is-open')) {
                    $(this).trigger('click');
                }
                dropdown.find('.passenger-type-option.is-selected').trigger('focus');
            });

            $(document).on('keydown', '.passenger-type-option', function(event) {
                const option = $(this);
                const dropdown = option.closest('.passenger-type-dropdown');
                const options = dropdown.find('.passenger-type-option');
                const currentIndex = options.index(option);

                if (event.key === 'Escape') {
                    event.preventDefault();
                    closePassengerTypeDropdown(dropdown);
                    dropdown.find('.passenger-type-trigger').trigger('focus');
                    return;
                }

                if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;

                event.preventDefault();
                const nextIndex = event.key === 'ArrowDown' ?
                    Math.min(currentIndex + 1, options.length - 1) :
                    Math.max(currentIndex - 1, 0);
                options.eq(nextIndex).trigger('focus');
            });

            $(document).on('click', function(event) {
                if (!$(event.target).closest('.passenger-type-dropdown').length) {
                    closePassengerTypeDropdowns();
                }
            });

            $(document).on('change', '.passenger-type-select', function() {
                const select = $(this);
                const selectedType = select.find('option:selected');
                const card = select.closest('.passenger-card');

                syncPassengerTypeDropdown(select);

                if (selectedType.data('type') === 'discounted') {
                    card.find('.discount-fields').removeClass('d-none');
                    card.find('.js-name-note').text('(required)');
                    card.find('.js-id-label').text(`${selectedType.data('discount-name') || 'Passenger'} ID Number`);
                } else {
                    card.find('.discount-fields').addClass('d-none');
                    card.find('.js-name-note').text('(optional)');
                    card.find('.js-id-number').val('');
                    card.find('.js-id-label').text('ID Number');
                }

                resetAuthorization();
                renderSummary();
                calculateGateway();
            });

            $(document).on('input', '.js-passenger-name, .js-id-number', function() {
                resetAuthorization();
                renderSummary();
                calculateGateway();
            });

            $('#cancelAuthorization').on('click', function() {
                pendingPaymentAfterAuthorization = false;
                clearTimeout(authorizationTimer);
                resetAuthorization();
                $('.js-auth-status, .js-auth-modal-status').addClass('text-danger').text('Authorization cancelled. Change discounted passengers to Regular or authorize again.');
                $('#discountAuthorizationModal').modal('hide');
            });

            function proceedToPayment() {
                $('input[name="passengers"]').val(JSON.stringify(passengerManifest));
                setStep('payment');
                renderSummary();
                calculateGateway();
            }

            function authorizeAndContinue() {
                if (authorizationInFlight) return;

                const state = collectPassengers(true);
                if (!state.valid) return;
                if (!state.discounted.length) return;

                const formData = new FormData();
                const passcode = $.trim($('#authPasscode').val());
                if (!passcode) {
                    return;
                }
                formData.append('authorization_method', 'code');
                formData.append('passcode', passcode);

                authorizationInFlight = true;
                $('.js-auth-modal-status').removeClass('text-success text-danger').text('Checking authorization code...');

                fetch("{{ url('api/auth-admin-passcode') }}", {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(response => {
                        if (response.is_authorized) {
                            const admin = response.admin || {};
                            $('input[name="discount_authorized"]').val('1');
                            $('input[name="authorization_method"]').val('code');
                            $('input[name="authorized_by_admin_id"]').val(admin.id || '');
                            $('input[name="authorized_by_name"]').val(admin.name || admin.username || '');
                            $('input[name="authorization_reference"]').val(`code:${admin.username || admin.id || 'authorized'}`);
                            $('.js-auth-status, .js-auth-modal-status').removeClass('text-danger').addClass('text-success').text(`Authorized by ${admin.name || admin.username || 'authorized personnel'}.`);
                            showMessage('success', response.message);
                            $('#discountAuthorizationModal').modal('hide');
                            $('#authPasscode').val('');
                            if (pendingPaymentAfterAuthorization) {
                                pendingPaymentAfterAuthorization = false;
                                proceedToPayment();
                            }
                        } else {
                            resetAuthorization();
                            $('.js-auth-status, .js-auth-modal-status').removeClass('text-success').addClass('text-danger').text(response.message);
                        }
                    })
                    .catch(() => {
                        resetAuthorization();
                        showMessage('error', 'Authorization failed. Please try again.');
                    })
                    .finally(() => {
                        authorizationInFlight = false;
                    });
            }

            $('#authPasscode').on('input', function() {
                clearTimeout(authorizationTimer);
                resetAuthorization();
                const code = $.trim($(this).val());
                $('.js-auth-modal-status').removeClass('text-success text-danger').text(code ? 'Enter the complete authorization code.' : '');

                if (code.length < 3) return;

                authorizationTimer = setTimeout(authorizeAndContinue, 650);
            });

            $('#authPasscode').on('keydown', function(event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                clearTimeout(authorizationTimer);
                authorizeAndContinue();
            });

            $('#discountAuthorizationModal').on('shown.bs.modal', function() {
                $('#authPasscode').trigger('focus');
            });

            $('#continueToPayment').on('click', function() {
                const state = collectPassengers(true);
                if (!state.valid) return;

                if (!$('.gateway-input').length) {
                    showMessage('error', 'No payment method is currently available for this booking channel.');
                    return;
                }

                if (state.discounted.length && $('input[name="discount_authorized"]').val() !== '1') {
                    pendingPaymentAfterAuthorization = true;
                    $('.js-auth-modal-status').removeClass('text-success text-danger').text('Enter the authorization code to continue.');
                    $('#authPasscode').val('');
                    $('#discountAuthorizationModal').modal('show');
                    return;
                }

                proceedToPayment();
            });

            $('#backToDetails').on('click', function() {
                setStep('details');
            });

            $('.gateway-input').on('change', function() {
                calculateGateway();
            });

            $('.paynamics-method__toggle').on('click', function() {
                const method = $(this).closest('.paynamics-method');
                const willOpen = !method.hasClass('is-open');

                $('.paynamics-method').removeClass('is-open')
                    .find('.paynamics-method__toggle').attr('aria-expanded', 'false');
                $('.paynamics-method__channels').addClass('d-none');

                if (willOpen) {
                    method.addClass('is-open');
                    method.find('.paynamics-method__toggle').attr('aria-expanded', 'true');
                    method.find('.paynamics-method__channels').removeClass('d-none');
                }
            });

            $('input[name="pchannel"]').on('change', function() {
                $('#selectedPaynamicsMethod').val($(this).data('pmethod'));
            });

            $('.paynamics-channel__logo img').on('error', function() {
                $(this).replaceWith('<i class="las la-wallet" aria-hidden="true"></i>');
            });

            $('#passengerFlowForm').on('submit', function(e) {
                const state = collectPassengers(true);
                if (!state.valid) {
                    e.preventDefault();
                    setStep('details');
                    return;
                }

                if (state.discounted.length && $('input[name="discount_authorized"]').val() !== '1') {
                    e.preventDefault();
                    setStep('details');
                    pendingPaymentAfterAuthorization = true;
                    $('.js-auth-modal-status').removeClass('text-success text-danger').text('Enter the authorization code to continue.');
                    $('#authPasscode').val('');
                    $('#discountAuthorizationModal').modal('show');
                    return;
                }

                if (!$('.gateway-input:checked').length) {
                    e.preventDefault();
                    showMessage('error', 'Please select a payment method.');
                    return;
                }

                if (isPaynamicsSelected() && !$('input[name="pchannel"]:checked').length) {
                    e.preventDefault();
                    showMessage('error', 'Please select a Paynamics payment channel.');
                    return;
                }

                $('input[name="passengers"]').val(JSON.stringify(passengerManifest));
                calculateGateway();
                $('#confirmPayment').prop('disabled', true).text('Validating payment...');
            });

            if (!$('.gateway-input:checked').length) {
                $('.gateway-input:first').prop('checked', true);
            }

            $('.passenger-type-select').each(function() {
                syncPassengerTypeDropdown($(this));
            });
            renderSummary();
            calculateGateway();
        })(jQuery);
    </script>
@endpush
