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

            <button type="button" class="flow-back-btn" onclick="window.history.back();">
                <i class="las la-arrow-left"></i> Back to seat selection
            </button>

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
                        <div>
                            <h4>Passenger Details</h4>
                            <p>{{ $seatCount }} {{ $seatWord }} &middot; {{ $bookedTicket->pickup->name ?? $bookedTicket->trip->startFrom->name }} &rarr; {{ $bookedTicket->drop->name ?? $bookedTicket->trip->endTo->name }} &middot; {{ showDateTime($bookedTicket->trip->schedule->start_from, 'h:i A') }}</p>
                        </div>
                    </div>

                    @foreach ($seats as $index => $seat)
                        <div class="passenger-card" data-seat="{{ $seat }}">
                            <div class="passenger-card__head">
                                <div>
                                    <span>Passenger {{ $index + 1 }}</span>
                                    <strong>{{ $seat }}</strong>
                                </div>
                                <div class="seat-price">
                                    <small class="js-original-price d-none">{{ showAmount($unitPrice) }}</small>
                                    <strong class="js-seat-price">{{ showAmount($unitPrice) }}</strong>
                                </div>
                            </div>

                            <label class="flow-label">Full Name <span class="js-name-note">(optional)</span></label>
                            <input type="text" class="flow-input js-passenger-name" placeholder="Guest">

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

                            <div class="discount-fields d-none">
                                <div class="discount-note js-discount-note"></div>
                                <label class="flow-label">ID Number</label>
                                <input type="text" class="flow-input js-id-number" placeholder="Required for discounted passenger">
                            </div>
                        </div>
                    @endforeach

                    <div class="flow-summary">
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
                                <h5>Discount Authorization</h5>
                                <p>Note: discounted transactions require authorization from authorized personnel before payment can proceed.</p>
                            </div>
                        </div>

                        <div class="authorization-preview js-auth-preview"></div>
                        <div class="auth-status js-auth-status"></div>
                    </div>

                    <button type="button" class="btn-primary-flow w-100 mt-3" id="continueToPayment">
                        Continue to Payment
                    </button>
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
                        <h5>Discount Authorization</h5>
                        <p class="auth-modal-copy">Enter the authorization code to approve the discounted fare.</p>

                        <div class="authorization-preview js-auth-modal-preview"></div>

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
            gap: 14px;
        }

        .flow-title-row h4,
        .authorization-heading h5,
        .payment-title {
            color: #111827;
            font-weight: 800;
            margin: 0;
        }

        .flow-title-row p,
        .authorization-heading p {
            color: #7b8490;
            margin: 3px 0 0;
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
            border: 1px solid #edf0f3;
            border-radius: 8px;
            margin-top: 12px;
            overflow: hidden;
            padding: 0 16px 16px;
        }

        .passenger-card__head {
            align-items: center;
            background: #f8fafc;
            display: flex;
            justify-content: space-between;
            margin: 0 -16px 14px;
            padding: 10px 16px;
        }

        .passenger-card__head span,
        .flow-label {
            color: #7b8490;
            display: block;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .passenger-card__head strong,
        .seat-price strong {
            color: #df2a82;
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
            border-color: #df2a82;
            box-shadow: 0 0 0 3px rgba(223, 42, 130, .12);
        }

        .flow-label {
            margin: 11px 0 6px;
            text-transform: none;
        }

        .passenger-type-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }

        .type-option {
            background: #fff;
            border: 1px solid #dfe3e8;
            border-radius: 8px;
            color: #4b5563;
            font-weight: 800;
            min-height: 38px;
        }

        .type-option.is-active {
            background: #df2a82;
            border-color: #df2a82;
            color: #fff;
        }

        .discount-note {
            color: #059669;
            font-size: 12px;
            font-weight: 800;
            margin-top: 10px;
        }

        .flow-summary,
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
            color: #5f6b7a;
            font-weight: 700;
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
            font-size: 24px;
        }

        .authorization-panel {
            border: 1px solid #ffe0ef;
            border-radius: 8px;
            margin-top: 12px;
            padding: 14px;
        }

        .authorization-preview {
            background: #f8fafc;
            border-radius: 8px;
            color: #334155;
            font-weight: 700;
            margin: 12px 0 4px;
            padding: 10px;
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
                const lines = passengerManifest.map((item) => {
                    let label = `Seat ${item.seat} &middot; ${item.passenger_type === 'discounted' ? item.discount_name : 'Regular'}`;
                    return `<div class="summary-line"><span>${label}</span><strong>${money(item.fare)}</strong></div>`;
                }).join('');
                const detailsDiscountLine = totals.discount > 0 ?
                    `<div class="summary-line"><span>Discount</span><strong>-${money(totals.discount)}</strong></div>` : '';
                const detailsBreakdown = `${lines}<div class="summary-line"><span>Base Fare</span><strong>${money(totals.subtotal)}</strong></div>${detailsDiscountLine}`;

                $('.js-breakdown').html(detailsBreakdown);
                $('.js-details-total').text(money(totals.payable));
                $('.js-payment-passengers').html(lines);

                const discountLine = totals.discount > 0 ?
                    `<div class="summary-line"><span>Discount</span><strong>-${money(totals.discount)}</strong></div>` : '';
                $('.js-payment-breakdown').html(
                    `<div class="summary-line"><span>Regular fare x ${seats.length}</span><strong>${money(totals.subtotal)}</strong></div>${discountLine}<div class="summary-line"><span>Processing charge</span><strong class="js-processing-charge">${money(totals.charge)}</strong></div>`
                );
                $('.js-payment-total').text(money(totals.final));

                if (state.discounted.length) {
                    $('#authorizationPanel').removeClass('d-none');
                    const authorizationPreview = state.discounted.map((item) => {
                        return `<div class="summary-line"><span>${item.name} &middot; Seat ${item.seat}<br><small>${item.discount_name} &middot; ID ${item.id_number}</small></span><strong>-${money(item.discount_amount)}</strong></div>`;
                    }).join('');
                    $('.js-auth-preview, .js-auth-modal-preview').html(authorizationPreview);
                } else {
                    $('#authorizationPanel').addClass('d-none');
                    resetAuthorization();
                }

                $('#continueToPayment').text(state.discounted.length ? 'Authorize & Continue to Payment' : 'Continue to Payment');
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
                    const percentage = Number(button.data('percentage') || 0);
                    const fare = unitPrice - (unitPrice * (percentage / 100));
                    card.find('.discount-fields').removeClass('d-none');
                    card.find('.js-name-note').text('(required)');
                    card.find('.js-discount-note').text(`${button.data('discount-name')} discount - ${percentage.toFixed(0)}% off`);
                    card.find('.js-original-price').removeClass('d-none');
                    card.find('.js-seat-price').text(money(fare));
                } else {
                    card.find('.discount-fields').addClass('d-none');
                    card.find('.js-name-note').text('(optional)');
                    card.find('.js-id-number').val('');
                    card.find('.js-original-price').addClass('d-none');
                    card.find('.js-seat-price').text(money(unitPrice));
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
