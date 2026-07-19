@extends($activeTemplate . $layout)

@section('content')
    @if ($layout === 'layouts.kiosk')
        @include('templates.basic.partials.kiosk_nav')
    @endif

    <div class="voucher-flow-wrap">
        <div class="container">
            @include('templates.basic.partials.booking_stepper', ['currentStep' => 'done'])

            <section class="voucher-panel">
                @php
                    $seatCount = count($ticket->seats ?? []);
                    $seatWord = $seatCount === 1 ? 'Seat' : 'Seats';
                    $ticketWord = $seatCount === 1 ? 'ticket' : 'tickets';
                    $dateOfJourneyQuery = \Carbon\Carbon::parse($ticket->date_of_journey)->format('m/d/Y');
                @endphp
                <div class="done-icon"><i class="las la-clock"></i></div>
                <h3>{{ $seatCount }} {{ $seatWord }} Reserved &mdash; Pay at Counter</h3>
                <p>Present this booking voucher at the <strong>Cashier Window</strong> for ticket issuance or verification.</p>

                @php
                    $qr = base64_encode(QrCode::format('svg')->size(150)->generate($ticket->pnr_number));
                    $expiresAt = $deposit->created_at->copy()->addMinutes(15);
                    $manifest = $ticket->passenger_manifest ?: [];
                    $kioskReturnUrl = url('/tickets?' . urldecode(http_build_query([
                        'kiosk_id' => $ticket->kiosk_id,
                        'counter_id' => $ticket->trip->start_from,
                        'pickup' => $ticket->trip->start_from
                    ])));
                    $androidReceiptPayload = [
                        'pnr' => $ticket->pnr_number,
                        'name' => $ticket->user->first_name ?? ($ticket->user->fullname ?? ''),
                        'date' => showDateTime($ticket->date_of_journey, 'M d, Y'),
                        'destination' => $ticket->drop?->name ?? '',
                        'updated_at' => formatDate($deposit->updated_at, true),
                        'expired_at' => formatDate($expiresAt, true),
                        'seats' => implode(',', $ticket->seats ?? []),
                        'departure_time' => date('h:i A', strtotime($ticket->trip->schedule->start_from)),
                        'bus_type' => $ticket->trip?->fleetType?->name ?? '',
                        'amount' => number_format((float) $deposit->amount, 2),
                        'discount_amount' => number_format((float) ($deposit->userDiscount?->amount ?? 0), 2),
                        'discount_description' => $deposit->userDiscount?->description ?? '',
                        'final_amount' => number_format((float) $deposit->final_amount, 2),
                        'method' => $deposit->gateway?->name ?? $deposit->methodName(),
                        'status' => strip_tags($deposit->statusString),
                        'passengers' => $manifest,
                    ];
                @endphp

                <div class="qr-card">
                    <img src="data:image/svg+xml;base64,{{ $qr }}" alt="Booking QR">
                </div>
                <div class="reference-label">Booking Reference (PNR)</div>
                <div class="reference-number">{{ $ticket->pnr_number }}</div>
                <div class="reference-sub">{{ $seatCount }} {{ $ticketWord }} &middot; 1 PNR</div>

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
            padding: 8px 0 24px;
        }

        .voucher-panel {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 8px rgba(15, 23, 42, .06);
            padding: 22px 20px;
            text-align: center;
        }

        .done-icon {
            align-items: center;
            background: #fff1c7;
            border-radius: 999px;
            color: #d97706;
            display: inline-flex;
            font-size: 30px;
            height: 52px;
            justify-content: center;
            margin-bottom: 10px;
            width: 52px;
        }

        .voucher-panel h3 {
            color: #111827;
            font-weight: 900;
            margin: 0 0 4px;
        }

        .voucher-panel p {
            color: #4b5563;
            margin-bottom: 14px;
        }

        .qr-card {
            border: 1px solid #dfe3e8;
            border-radius: 8px;
            display: inline-flex;
            padding: 12px;
        }

        .qr-card img {
            display: block;
            height: 138px;
            width: 138px;
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
            margin: 18px 0 14px;
            padding: 11px;
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
            padding: 10px 14px;
            text-transform: uppercase;
        }

        .ticket-row,
        .ticket-total {
            align-items: center;
            border-top: 1px solid #eef2f7;
            display: flex;
            gap: 12px;
            padding: 12px 14px;
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

            const androidReceiptPayload = @json($androidReceiptPayload);

            function printViaAndroidBridge() {
                if (!window.Android || typeof window.Android.printReceipt !== 'function') {
                    console.log('Android bridge not available');
                    return;
                }

                try {
                    console.log('Android bridge running...');
                    window.Android.printReceipt(JSON.stringify(androidReceiptPayload));
                } catch (error) {
                    console.error('Android silent print failed:', error);
                }
            }

            if (document.readyState === 'complete') {
                printViaAndroidBridge();
            } else {
                window.addEventListener('load', printViaAndroidBridge, { once: true });
            }

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
