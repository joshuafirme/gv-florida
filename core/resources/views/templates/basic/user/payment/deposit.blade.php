@php
    $kiosk_id = request()->kiosk_id;
    if ($kiosk_id) {
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

            <form action="{{ route('user.deposit.release-seats') }}" method="POST" class="seat-release-form">
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
                <input type="hidden" name="discount_authorized" value="0">
                <input type="hidden" name="authorization_method">
                <input type="hidden" name="authorized_by_admin_id">
                <input type="hidden" name="authorized_by_name">
                <input type="hidden" name="authorization_reference">

                <section class="flow-panel js-step-panel" data-panel="details">
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

                    @foreach ($seats as $index => $seat)
                        <div class="passenger-card" data-seat="{{ $seat }}">
                            <div class="passenger-card__head">
                                <span class="passenger-number">{{ $index + 1 }}</span>
                                <strong>Seat {{ $seat }}</strong>
                            </div>

                            <label class="flow-label">Full Name <span class="js-name-note">(optional)</span></label>
                            <input type="text" class="flow-input js-passenger-name" placeholder="Guest">

                            <div class="discount-fields d-none">
                                <label class="flow-label">ID Number <span>(required for discounted passengers)</span></label>
                                <input type="text" class="flow-input js-id-number" placeholder="Enter passenger ID number">
                            </div>
                            <label class="flow-label">Passenger Type</label>
                            <div class="passenger-type-grid">
                                <button type="button" class="type-option is-active" data-type="regular" data-discount-id="">
                                    Regular
                                </button>
                                @foreach ($discountOptions as $discount)
                                    <button type="button" class="type-option" data-type="discounted"
                                        data-discount-id="{{ $discount['id'] }}" data-discount-name="{{ $discount['name'] }}"
                                        data-percentage="{{ $discount['percentage'] }}">
                                        {{ $discount['name'] }}
                                    </button>
                                @endforeach
                            </div>
                            <div class="discount-note js-discount-note d-none"></div>
                        </div>
                    @endforeach

                    <div class="flow-summary">
                        <div class="section-heading">
                            <div class="section-icon"><i class="las la-receipt"></i></div>
                            <h5>Fare Summary</h5>
                        </div>
                        <div class="js-breakdown"></div>
                        <div class="summary-total">
                            <span>Total Fare</span>
                            <strong class="js-details-total">{{ showAmount($bookedTicket->sub_total) }}</strong>
                        </div>
                    </div>

                    <div class="authorization-panel d-none" id="authorizationPanel">
                        <div class="authorization-heading">
                            <div class="auth-icon"><i class="las la-shield-alt"></i></div>
                            <div>
                                <h5>Authorization Required</h5>
                                <p>An authorized employee must approve this transaction before payment can continue. Please request assistance and enter the authorization code.</p>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn-primary-flow w-100 mt-3" id="continueToPayment">
                        <i class="las la-lock"></i> Continue to Payment
                    </button>
                    <p class="flow-security-note"><i class="las la-lock"></i> Your transaction is secure and protected.</p>
                </section>

                <section class="flow-panel js-step-panel d-none" data-panel="payment">
                    <button type="button" class="flow-back-btn mb-3" id="backToDetails">
                        <i class="las la-arrow-left"></i> Back to details
                    </button>

                    <h4 class="payment-title">Payment</h4>
                    <div class="payment-passenger-list js-payment-passengers"></div>

                    <label class="flow-label mt-3">Payment Method</label>
                    <div class="payment-methods">
                        @foreach ($gatewayCurrency as $data)
                            @php
                                if ($kiosk_id && $data->name == 'Paynamics') {
                                    continue;
                                }
                                if (!$kiosk_id && $data->name == 'Cash') {
                                    continue;
                                }
                                $description = $data->instruction ?: ($data->name == 'Cash' ? 'Pay at the cashier with the printed voucher' : 'Follow the payment instructions on the next screen');
                            @endphp
                            <label class="payment-method-card">
                                <input class="gateway-input" data-gateway='@json($data)' type="radio" name="gateway"
                                    value="{{ $data->method_code }}" data-min-amount="{{ showAmount($data->min_amount) }}"
                                    data-max-amount="{{ showAmount($data->max_amount) }}" @checked($loop->first)>
                                <span class="method-icon"><i class="las {{ $data->name == 'Cash' ? 'la-money-bill' : 'la-credit-card' }}"></i></span>
                                <span class="method-copy">
                                    <strong>{{ __($data->name) }}</strong>
                                    <small>{{ __($description) }}</small>
                                </span>
                                <span class="method-check"><i class="las la-check"></i></span>
                            </label>
                        @endforeach
                    </div>

                    <div class="payment-total-box">
                        <div class="js-payment-breakdown"></div>
                        <div class="payment-instructions js-payment-instructions"></div>
                        <div class="summary-total">
                            <span>Total to pay ({{ $seatCount }} {{ $seatWord }})</span>
                            <strong class="js-payment-total">{{ showAmount($bookedTicket->sub_total) }}</strong>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-flow w-100 mt-3" id="confirmPayment">
                        Confirm & Print Voucher
                    </button>
                </section>
            </form>

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
            background: #fff0f7;
            border-radius: 4px;
            color: #df2a82;
            flex: 0 0 auto;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 8px;
        }

        .flow-title-icon,
        .auth-icon {
            align-items: center;
            background: #ffe7f3;
            border-radius: 8px;
            color: #df2a82;
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
            border-bottom: 1px solid #edf0f3;
            margin-top: 10px;
            padding: 0 0 16px;
        }

        .passenger-card:last-of-type {
            border-bottom: 0;
        }

        .passenger-card__head {
            align-items: center;
            display: flex;
            gap: 9px;
            margin-bottom: 10px;
            padding: 7px 0;
        }

        .flow-label {
            color: #7b8490;
            display: block;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .passenger-card__head strong {
            color: #1f2937;
            font-weight: 800;
        }

        .passenger-number {
            align-items: center;
            background: #df2a82;
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            flex: 0 0 26px;
            font-size: 12px;
            font-weight: 800;
            height: 26px;
            justify-content: center;
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
            border-color: #df2a82;
            box-shadow: 0 0 0 3px rgba(223, 42, 130, .12);
        }

        .flow-label {
            margin: 11px 0 6px;
            text-transform: none;
        }

        .passenger-type-grid {
            display: grid;
            gap: 7px;
            grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
        }

        .type-option {
            background: #fff;
            border: 1px solid #dfe3e8;
            border-radius: 8px;
            color: #4b5563;
            font-weight: 800;
            line-height: 1.15;
            min-height: 38px;
            overflow-wrap: anywhere;
            padding: 6px 8px;
            white-space: normal;
        }

        .type-option.is-active {
            background: #df2a82;
            border-color: #df2a82;
            color: #fff;
        }

        .discount-note {
            align-items: center;
            background: #edfdf3;
            border-radius: 4px;
            color: #059669;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            margin-top: 8px;
            padding: 4px 7px;
        }

        .flow-summary,
        .payment-total-box {
            background: #f8fafc;
            border-radius: 8px;
            margin-top: 12px;
            padding: 14px;
        }

        .section-heading {
            align-items: center;
            display: flex;
            gap: 9px;
            margin-bottom: 8px;
        }

        .section-heading h5 {
            color: #1f2937;
            font-weight: 800;
            margin: 0;
        }

        .section-icon {
            align-items: center;
            background: #ffe7f3;
            border-radius: 6px;
            color: #df2a82;
            display: flex;
            font-size: 17px;
            height: 30px;
            justify-content: center;
            width: 30px;
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
            color: #df2a82;
            font-size: 25px;
        }

        .authorization-panel {
            background: #fffaf0;
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
            background: #df2a82;
            color: #fff;
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

        .payment-passenger-list {
            background: #f8fafc;
            border-radius: 8px;
            margin-top: 10px;
            padding: 10px;
        }

        .payment-passenger-row {
            align-items: center;
            display: flex;
            justify-content: space-between;
            padding: 5px 2px;
        }

        .payment-passenger-row + .payment-passenger-row {
            border-top: 1px solid #e8ebef;
        }

        .payment-passenger-row strong,
        .payment-passenger-row small {
            display: block;
        }

        .payment-passenger-row small {
            color: #7b8490;
            margin-left: auto;
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
            padding: 12px;
        }

        .payment-method-card:has(input:checked) {
            background: #fff5fa;
            border-color: #df2a82;
        }

        .payment-method-card input {
            display: none;
        }

        .method-icon,
        .method-check {
            color: #df2a82;
            font-size: 20px;
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

        .payment-instructions {
            color: #64748b;
            font-weight: 700;
            margin-top: 10px;
        }

        @media (max-width: 575px) {
            .flow-panel {
                padding: 16px;
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

            function money(amount) {
                return '{{ gs('cur_sym') }}' + Number(amount || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function escapeHtml(value) {
                return $('<div>').text(value || '').html();
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

            function setStep(step) {
                const stepOrder = ['seat', 'details', 'payment', 'done'];
                const activeIndex = stepOrder.indexOf(step);
                const progress = activeIndex <= 0 ? 0 : activeIndex / (stepOrder.length - 1);

                $('.js-step-panel').addClass('d-none');
                $(`.js-step-panel[data-panel="${step}"]`).removeClass('d-none');
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
                    const typeButton = card.find('.type-option.is-active');
                    const passengerType = typeButton.data('type') || 'regular';
                    const discountId = typeButton.data('discount-id') || null;
                    const discountName = typeButton.data('discount-name') || null;
                    const percentage = Number(typeButton.data('percentage') || 0);
                    const seat = String(card.data('seat'));
                    const name = $.trim(card.find('.js-passenger-name').val());
                    const idNumber = $.trim(card.find('.js-id-number').val());
                    const seatDiscount = passengerType === 'discounted' ? unitPrice * (percentage / 100) : 0;

                    if (passengerType === 'discounted') {
                        if (!name) errors.push(`Passenger name is required for seat ${seat}.`);
                        if (!idNumber) errors.push(`ID number is required for seat ${seat}.`);
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
                const paymentPassengers = passengerManifest.map((item) => {
                    const name = escapeHtml(item.name || 'Guest');
                    const type = escapeHtml(item.passenger_type === 'discounted' ? item.discount_name : 'Regular');
                    return `<div class="payment-passenger-row"><strong>${name}</strong><small>${type} &middot; Seat ${escapeHtml(item.seat)}</small></div>`;
                }).join('');
                const appliedDiscounts = [...new Map(state.discounted.map((item) => [
                    `${item.discount_name}-${item.discount_percentage}`,
                    `${item.discount_name} (${Number(item.discount_percentage).toFixed(0)}%)`
                ])).values()];
                const discountLabel = appliedDiscounts.length ? `Discount (${appliedDiscounts.join(', ')})` : 'Discount';
                const detailsDiscountLine = totals.discount > 0 ?
                    `<div class="summary-line summary-line--discount"><span>${escapeHtml(discountLabel)}</span><strong>-${money(totals.discount)}</strong></div>` : '';
                const detailsBreakdown = `<div class="summary-line"><span>Base Fare</span><strong>${money(totals.subtotal)}</strong></div>${detailsDiscountLine}`;

                $('.js-breakdown').html(detailsBreakdown);
                $('.js-details-total').text(money(totals.payable));
                $('.js-payment-passengers').html(paymentPassengers);

                const discountLine = totals.discount > 0 ?
                    `<div class="summary-line"><span>Discount</span><strong>-${money(totals.discount)}</strong></div>` : '';
                $('.js-payment-breakdown').html(
                    `<div class="summary-line"><span>Regular fare x ${seats.length}</span><strong>${money(totals.subtotal)}</strong></div>${discountLine}<div class="summary-line"><span>Processing charge</span><strong class="js-processing-charge">${money(totals.charge)}</strong></div>`
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
                $('.js-payment-instructions').text(gateway.instruction || (gateway.name === 'Cash' ? 'Present the voucher at the Cashier Window for payment and ticket issuance.' : 'Follow the next screen to complete payment validation.'));
            }

            $(document).on('click', '.type-option', function() {
                const button = $(this);
                const card = button.closest('.passenger-card');
                card.find('.type-option').removeClass('is-active');
                button.addClass('is-active');

                if (button.data('type') === 'discounted') {
                    card.find('.discount-fields').removeClass('d-none');
                    card.find('.js-name-note').text('(required)');
                    card.find('.js-discount-note').removeClass('d-none').html(`<i class="las la-tag"></i>&nbsp; ${escapeHtml(button.data('discount-name'))} discount applied (${Number(button.data('percentage') || 0).toFixed(0)}%)`);
                } else {
                    card.find('.discount-fields').addClass('d-none');
                    card.find('.js-name-note').text('(optional)');
                    card.find('.js-id-number').val('');
                    card.find('.js-discount-note').addClass('d-none').empty();
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

                $('input[name="passengers"]').val(JSON.stringify(passengerManifest));
                calculateGateway();
                $('#confirmPayment').prop('disabled', true).text('Validating payment...');
            });

            if (!$('.gateway-input:checked').length) {
                $('.gateway-input:first').prop('checked', true);
            }

            renderSummary();
            calculateGateway();
        })(jQuery);
    </script>
@endpush
