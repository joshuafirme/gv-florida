@extends($activeTemplate . $layout)

@section('content')
    @if ($layout === 'layouts.kiosk')
        @include('templates.basic.partials.kiosk_nav')
    @endif

    @php
        $state = $callbackState ?? 'failed';
        $stateMeta = match ($state) {
            'pending' => [
                'icon' => 'la-clock',
                'title' => 'Payment Pending',
                'copy' => 'Your booking is reserved while Paynamics confirms the payment.',
            ],
            'cancelled' => [
                'icon' => 'la-times',
                'title' => 'Payment Cancelled',
                'copy' => 'No payment was confirmed. You may select another payment option to continue.',
            ],
            default => [
                'icon' => 'la-exclamation',
                'title' => 'Payment Not Confirmed',
                'copy' => 'The payment could not be confirmed. Please review the response below before trying again.',
            ],
        };
        $retryUrl = $ticket
            ? route('user.deposit.index', array_filter(['kiosk_id' => $ticket->kiosk_id]))
            : route('ticket');
        $tripsUrl = $ticket
            ? route('ticket', array_filter([
                'kiosk_id' => $ticket->kiosk_id,
                'counter_id' => $ticket->trip?->start_from,
                'pickup' => $ticket->pickup_point ?: $ticket->trip?->start_from,
                'destination' => $ticket->dropping_point,
                'date_of_journey' => \Carbon\Carbon::parse($ticket->date_of_journey)->format('m/d/Y'),
            ]))
            : route('ticket');
    @endphp

    <div class="paynamics-result-flow">
        <div class="container">
            @if ($ticket)
                @include('templates.basic.partials.booking_stepper', ['currentStep' => 'payment'])
            @endif

            <section class="paynamics-result paynamics-result--{{ $state }}">
                <div class="paynamics-result__icon">
                    <i class="las {{ $stateMeta['icon'] }}"></i>
                </div>
                <h3>{{ $stateMeta['title'] }}</h3>
                <p>{{ $stateMeta['copy'] }}</p>

                <div class="paynamics-result__notice">
                    <strong>{{ $callbackDetails['message'] }}</strong>
                    @if (!empty($callbackDetails['advice']))
                        <span>{{ $callbackDetails['advice'] }}</span>
                    @endif
                </div>

                @if ($ticket)
                    <div class="paynamics-result__booking">
                        <div>
                            <span>Booking Reference (PNR)</span>
                            <strong>{{ $ticket->pnr_number }}</strong>
                        </div>
                        <div>
                            <span>Seat(s)</span>
                            <strong>{{ formatSeatLabel($ticket->seats ?? []) }}</strong>
                        </div>
                        <div>
                            <span>Amount</span>
                            <strong>{{ showAmount($deposit->final_amount) }}</strong>
                        </div>
                    </div>
                @endif

                <div class="paynamics-result__details">
                    <div>
                        <span>Payment Channel</span>
                        <strong>{{ $callbackDetails['payment_channel'] ?: 'Paynamics' }}</strong>
                    </div>
                    <div>
                        <span>Provider Reference</span>
                        <strong>{{ $callbackDetails['pay_reference'] ?: 'Not generated' }}</strong>
                    </div>
                    <div>
                        <span>Request ID</span>
                        <strong>{{ $callbackDetails['request_id'] ?: 'Unavailable' }}</strong>
                    </div>
                    <div>
                        <span>Response Code</span>
                        <strong>{{ $callbackDetails['response_code'] ?: 'Not provided' }}</strong>
                    </div>
                    <div>
                        <span>Response Time</span>
                        <strong>{{ $callbackDetails['timestamp'] }}</strong>
                    </div>
                </div>

                @if (!empty($callbackDetails['instructions']))
                    <div class="paynamics-result__instructions">
                        <strong>Payment Instructions</strong>
                        <span>{!! nl2br(e($callbackDetails['instructions'])) !!}</span>
                    </div>
                @endif

                <div class="paynamics-result__actions">
                    @if ($ticket && $state === 'pending')
                        <a class="paynamics-result__primary" href="{{ route('user.deposit.done') }}">
                            <i class="las la-file-invoice"></i>
                            View Booking Voucher
                        </a>
                    @elseif ($ticket)
                        <a class="paynamics-result__primary" href="{{ $retryUrl }}">
                            <i class="las la-credit-card"></i>
                            Choose Payment Method
                        </a>
                    @endif
                    <a class="paynamics-result__secondary" href="{{ $tripsUrl }}">
                        <i class="las la-arrow-left"></i>
                        Back to Trips
                    </a>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .paynamics-result-flow {
            background: #f3f5f7;
            min-height: 100vh;
            padding: 8px 0 28px;
        }

        .paynamics-result {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 8px rgba(15, 23, 42, .06);
            margin: 0 auto;
            max-width: 760px;
            padding: 24px;
            text-align: center;
        }

        .paynamics-result__icon {
            align-items: center;
            background: #fff1f2;
            border-radius: 999px;
            color: #be123c;
            display: inline-flex;
            font-size: 28px;
            height: 52px;
            justify-content: center;
            margin-bottom: 10px;
            width: 52px;
        }

        .paynamics-result--pending .paynamics-result__icon {
            background: #fffbeb;
            color: #b45309;
        }

        .paynamics-result h3 {
            color: #111827;
            font-weight: 900;
            margin: 0 0 4px;
        }

        .paynamics-result > p {
            color: #64748b;
            margin: 0 auto 16px;
        }

        .paynamics-result__notice {
            background: #fff1f2;
            border-left: 3px solid #fb7185;
            color: #9f1239;
            display: grid;
            gap: 3px;
            padding: 11px 13px;
            text-align: left;
        }

        .paynamics-result--pending .paynamics-result__notice {
            background: #fffbeb;
            border-left-color: #f59e0b;
            color: #92400e;
        }

        .paynamics-result__booking {
            background: #f8fafc;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 14px;
            text-align: left;
        }

        .paynamics-result__booking > div,
        .paynamics-result__details > div {
            min-width: 0;
            padding: 11px 13px;
        }

        .paynamics-result__booking span,
        .paynamics-result__booking strong,
        .paynamics-result__details span,
        .paynamics-result__details strong,
        .paynamics-result__instructions span,
        .paynamics-result__instructions strong {
            display: block;
        }

        .paynamics-result__booking span,
        .paynamics-result__details span {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .paynamics-result__booking strong,
        .paynamics-result__details strong {
            color: #1f2937;
            overflow-wrap: anywhere;
        }

        .paynamics-result__details {
            border: 1px solid #e2e8f0;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 14px;
            text-align: left;
        }

        .paynamics-result__details > div {
            border-bottom: 1px solid #e2e8f0;
        }

        .paynamics-result__details > div:nth-child(odd) {
            border-right: 1px solid #e2e8f0;
        }

        .paynamics-result__instructions {
            color: #475569;
            padding: 14px 2px 0;
            text-align: left;
        }

        .paynamics-result__instructions strong {
            color: #1f2937;
            margin-bottom: 4px;
        }

        .paynamics-result__actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .paynamics-result__primary,
        .paynamics-result__secondary {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            font-weight: 800;
            gap: 7px;
            justify-content: center;
            min-height: 44px;
            padding: 0 18px;
        }

        .paynamics-result__primary {
            background: var(--booking-primary);
            color: var(--booking-on-primary);
        }

        .paynamics-result__primary:hover,
        .paynamics-result__primary:focus {
            background: var(--booking-primary-hover);
            color: var(--booking-on-primary);
        }

        .paynamics-result__secondary {
            background: #eef2f7;
            color: #334155;
        }

        @media (max-width: 575px) {
            .paynamics-result {
                padding: 20px 14px;
            }

            .paynamics-result__booking,
            .paynamics-result__details {
                grid-template-columns: 1fr;
            }

            .paynamics-result__details > div:nth-child(odd) {
                border-right: 0;
            }

            .paynamics-result__actions {
                flex-direction: column;
            }
        }
    </style>
@endpush
