@extends($activeTemplate . $layout)

@section('content')
    <div class="voucher-flow-wrap">
        <div class="container">
            @include('templates.basic.partials.booking_stepper', ['currentStep' => 'done'])

            <section class="voucher-panel">
                <div class="done-icon"><i class="las la-clock"></i></div>
                <h3>{{ $ticket->ticket_count }} Seats Reserved &mdash; Pay at Counter</h3>
                <p>Present this booking voucher at the <strong>Cashier Window</strong> for ticket issuance or verification.</p>

                @php
                    $qr = base64_encode(QrCode::format('svg')->size(150)->generate($ticket->pnr_number));
                    $expiresAt = $deposit->created_at->copy()->addMinutes(15);
                    $manifest = $ticket->passenger_manifest ?: [];
                    $kioskReturnUrl = url('/tickets?' . http_build_query([
                        'kiosk_id' => $ticket->kiosk_id,
                        'counter_id' => $ticket->trip->start_from,
                        'pickup' => $ticket->trip->start_from,
                        'date_of_journey' => $ticket->date_of_journey,
                    ]));
                @endphp

                <div class="qr-card">
                    <img src="data:image/svg+xml;base64,{{ $qr }}" alt="Booking QR">
                </div>
                <div class="reference-label">Booking Reference (PNR)</div>
                <div class="reference-number">{{ $ticket->pnr_number }}</div>
                <div class="reference-sub">{{ count($ticket->seats ?? []) }} tickets &middot; 1 PNR</div>

                <div class="payment-window">
                    <strong>
                        <i class="las la-clock"></i>
                        Pay within <span id="payCountdown" data-expires-at="{{ $expiresAt->toIso8601String() }}">15 mins 00 secs</span>
                    </strong>
                    <span>Valid until {{ showDateTime($expiresAt, 'h:i A') }} &middot; the seat is released if unpaid by then.</span>
                </div>

                <div class="ticket-details">
                    <div class="ticket-details__head">Ticket Details</div>
                    @foreach ($manifest as $index => $passenger)
                        <div class="ticket-row">
                            <span class="ticket-index">{{ $index + 1 }}</span>
                            <div class="ticket-copy">
                                <strong>{{ $passenger['name'] ?: 'Guest' }}</strong>
                                <span>
                                    {{ $passenger['passenger_type'] === 'discounted' ? $passenger['discount_name'] : 'Regular' }}
                                    &middot; Seat {{ $passenger['seat'] }}
                                    @if ($ticket->trip?->fleetType)
                                        &middot; {{ $ticket->trip->fleetType->name }}
                                    @endif
                                </span>
                            </div>
                            <strong class="ticket-price">{{ showAmount($passenger['fare'] ?? $ticket->unit_price) }}</strong>
                        </div>
                    @endforeach
                    <div class="ticket-total">
                        <span>Total</span>
                        <strong>{{ showAmount($deposit->final_amount) }}</strong>
                    </div>
                </div>

                <div class="voucher-actions">
                    <a href="{{ $kioskReturnUrl }}" class="btn-primary-flow w-100">Book Another</a>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .voucher-flow-wrap {
            background: #f3f5f7;
            min-height: 100vh;
            padding: 18px 0 36px;
        }

        .voucher-panel {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 8px rgba(15, 23, 42, .06);
            padding: 28px 22px;
            text-align: center;
        }

        .done-icon {
            align-items: center;
            background: #fff1c7;
            border-radius: 999px;
            color: #d97706;
            display: inline-flex;
            font-size: 34px;
            height: 58px;
            justify-content: center;
            margin-bottom: 12px;
            width: 58px;
        }

        .voucher-panel h3 {
            color: #111827;
            font-weight: 900;
            margin: 0 0 4px;
        }

        .voucher-panel p {
            color: #4b5563;
            margin-bottom: 18px;
        }

        .qr-card {
            border: 1px solid #dfe3e8;
            border-radius: 8px;
            display: inline-flex;
            padding: 14px;
        }

        .qr-card img {
            display: block;
            height: 150px;
            width: 150px;
        }

        .reference-label {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            margin-top: 12px;
            text-transform: uppercase;
        }

        .reference-number {
            color: #111827;
            font-size: 25px;
            font-weight: 900;
            line-height: 1.1;
        }

        .reference-sub {
            color: #94a3b8;
            font-weight: 700;
            margin-top: 4px;
        }

        .payment-window {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            color: #b45309;
            display: grid;
            gap: 3px;
            margin: 24px 0 18px;
            padding: 13px;
        }

        .payment-window.is-expired {
            background: #fff1f2;
            border-color: #fb7185;
            color: #be123c;
        }

        .ticket-details {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            text-align: left;
        }

        .ticket-details__head {
            background: #f8fafc;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 900;
            padding: 12px 16px;
            text-transform: uppercase;
        }

        .ticket-row,
        .ticket-total {
            align-items: center;
            border-top: 1px solid #eef2f7;
            display: flex;
            gap: 12px;
            padding: 14px 16px;
        }

        .ticket-index {
            align-items: center;
            background: #ffe7f3;
            border-radius: 999px;
            color: #df2a82;
            display: inline-flex;
            font-size: 12px;
            font-weight: 900;
            height: 24px;
            justify-content: center;
            width: 24px;
        }

        .ticket-copy {
            flex: 1;
        }

        .ticket-copy strong,
        .ticket-copy span {
            display: block;
        }

        .ticket-copy span {
            color: #64748b;
            font-size: 13px;
        }

        .ticket-price,
        .ticket-total strong {
            color: #df2a82;
            font-weight: 900;
        }

        .ticket-total {
            background: #f8fafc;
            justify-content: space-between;
        }

        .voucher-actions {
            /* display: grid;
            gap: 10px;
            grid-template-columns: 1fr 1fr; */
            margin-top: 18px;
        }

        .btn-primary-flow,
        .btn-light-flow {
            align-items: center;
            border: 0;
            border-radius: 8px;
            display: inline-flex;
            font-weight: 900;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
        }

        .btn-primary-flow {
            background: #df2a82;
            color: #fff;
        }

        .btn-light-flow {
            background: #f1f5f9;
            color: #334155;
        }

        @media (max-width: 575px) {
            .voucher-actions {
                grid-template-columns: 1fr;
            }

            .ticket-row {
                align-items: flex-start;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        (function() {
            "use strict";

            const countdown = document.getElementById('payCountdown');
            if (!countdown) return;

            const expiresAt = new Date(countdown.dataset.expiresAt).getTime();

            function formatRemaining(milliseconds) {
                const totalSeconds = Math.max(Math.floor(milliseconds / 1000), 0);
                const minutes = Math.floor(totalSeconds / 60);
                const seconds = totalSeconds % 60;

                if (totalSeconds <= 0) {
                    return '0 secs';
                }

                if (minutes <= 0) {
                    return `${seconds} sec${seconds === 1 ? '' : 's'}`;
                }

                return `${minutes} min${minutes === 1 ? '' : 's'} ${String(seconds).padStart(2, '0')} sec${seconds === 1 ? '' : 's'}`;
            }

            function tick() {
                const remaining = expiresAt - Date.now();
                countdown.textContent = formatRemaining(remaining);

                if (remaining <= 0) {
                    clearInterval(timer);
                    countdown.closest('.payment-window').classList.add('is-expired');
                }
            }

            const timer = setInterval(tick, 1000);
            tick();
        })();
    </script>
@endpush
