@extends('admin.layouts.app')

@section('panel')
    @php
        $metricUi = [
            'sold' => ['title' => 'Booked / Sold', 'icon' => 'la-ticket-alt', 'class' => 'sold'],
            'rebooked' => ['title' => 'Rebooked', 'icon' => 'la-exchange-alt', 'class' => 'rebooked'],
            'refunded' => ['title' => 'Refunded', 'icon' => 'la-undo-alt', 'class' => 'refunded'],
            'voided' => ['title' => 'Voided', 'icon' => 'la-ban', 'class' => 'voided'],
            'cancelled' => ['title' => 'Cancelled', 'icon' => 'la-times-circle', 'class' => 'cancelled'],
        ];
    @endphp

    <div class="cashier-summary">
        <div>
            <span>Today's Net Collection</span>
            <strong class="{{ $cashierWidget['net_collection'] < 0 ? 'is-negative' : '' }}">
                {{ $cashierWidget['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($cashierWidget['net_collection'])) }}
            </strong>
        </div>
        <div>
            <span>Transactions Processed</span>
            <strong>{{ $cashierWidget['transaction_count'] }}</strong>
        </div>
        <dl>
            <div><dt>Gross Sales</dt><dd>{{ showAmount($cashierWidget['gross_sales']) }}</dd></div>
            <div><dt>Discounts</dt><dd>{{ showAmount($cashierWidget['discounts']) }}</dd></div>
            <div><dt>Surcharges</dt><dd>{{ showAmount($cashierWidget['surcharges']) }}</dd></div>
            <div><dt>Refunds</dt><dd>-{{ showAmount($cashierWidget['refunds']) }}</dd></div>
            <div><dt>Voids</dt><dd>-{{ showAmount($cashierWidget['voids']) }}</dd></div>
        </dl>
    </div>

    <div class="cashier-metrics">
        @foreach ($metricUi as $key => $ui)
            @php $metric = $statusMetrics[$key]; @endphp
            <article class="cashier-metric cashier-metric--{{ $ui['class'] }}">
                <div class="cashier-metric__icon"><i class="las {{ $ui['icon'] }}"></i></div>
                <div>
                    <span>{{ $ui['title'] }}</span>
                    <strong>{{ $metric['count'] }}</strong>
                    <small>
                        {{ in_array($key, ['refunded', 'voided']) ? '-' : '' }}{{ showAmount($metric['amount']) }}
                    </small>
                </div>
            </article>
        @endforeach
    </div>

    <div class="card cashier-history">
        <div class="card-body">
            <div class="cashier-history__heading">
                <div>
                    <h5>Latest Ticket Transactions</h5>
                    <p>Each ticket is shown under its latest status.</p>
                </div>
                <a href="{{ route('admin.report.shift.end') }}">View Shift Report</a>
            </div>

            <div class="table-responsive">
                <table class="table table--light style--two align-middle">
                    <thead>
                        <tr>
                            <th>Processed</th>
                            <th>PNR / Reference</th>
                            <th>Passenger</th>
                            <th>Trip / Seat</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestTransactions as $transaction)
                            @php $amount = (float) $transaction->amount; @endphp
                            <tr>
                                <td>
                                    <strong>{{ $transaction->processed_at->format('h:i A') }}</strong>
                                    <small class="d-block text-muted">{{ $transaction->processed_at->format('M j, Y') }}</small>
                                </td>
                                <td>
                                    <strong>{{ $transaction->pnr ?: '-' }}</strong>
                                    <small class="d-block text-muted">Ref. {{ $transaction->reference_no ?: '-' }}</small>
                                </td>
                                <td>
                                    <strong>{{ $transaction->passenger_name ?: 'Guest' }}</strong>
                                    <small class="d-block text-muted">{{ $transaction->passenger_type ?: 'Regular' }}</small>
                                </td>
                                <td>
                                    <strong>{{ $transaction->trip_route ?: '-' }}</strong>
                                    <small class="d-block text-muted">Seat {{ formatSeatLabel($transaction->seat_no) ?: '-' }}</small>
                                </td>
                                <td class="text-end {{ $amount < 0 ? 'text--danger' : '' }}">
                                    <strong>{{ $amount < 0 ? '-' : '' }}{{ showAmount(abs($amount)) }}</strong>
                                </td>
                                <td>
                                    <span class="cashier-status cashier-status--{{ strtolower($transaction->status) }}">
                                        {{ $transaction->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">No transactions processed today.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .cashier-summary {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e5ea;
            border-radius: 8px;
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(200px, 1fr) minmax(160px, .65fr) minmax(420px, 2fr);
            padding: 20px;
        }

        .cashier-summary > div > span,
        .cashier-metric span {
            color: #6b7280;
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .cashier-summary > div > strong {
            color: #172033;
            display: block;
            font-size: 24px;
            margin-top: 4px;
        }

        .cashier-summary strong.is-negative {
            color: #c83838;
        }

        .cashier-summary dl {
            display: grid;
            gap: 8px 18px;
            grid-template-columns: repeat(5, minmax(90px, 1fr));
            margin: 0;
        }

        .cashier-summary dl div {
            min-width: 0;
        }

        .cashier-summary dt {
            color: #7b8490;
            font-size: 11px;
            font-weight: 600;
        }

        .cashier-summary dd {
            color: #172033;
            font-size: 14px;
            font-weight: 800;
            margin: 2px 0 0;
        }

        .cashier-metrics {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(5, minmax(150px, 1fr));
            margin-top: 16px;
        }

        .cashier-metric {
            align-items: center;
            background: #fff;
            border: 1px solid #e2e5ea;
            border-radius: 8px;
            display: flex;
            gap: 12px;
            min-height: 104px;
            padding: 16px;
        }

        .cashier-metric__icon {
            align-items: center;
            background: #edf8f2;
            border-radius: 8px;
            color: #17834f;
            display: flex;
            flex: 0 0 40px;
            font-size: 20px;
            height: 40px;
            justify-content: center;
        }

        .cashier-metric strong {
            color: #172033;
            display: inline-block;
            font-size: 25px;
            line-height: 1;
            margin-top: 7px;
        }

        .cashier-metric small {
            color: #596273;
            display: block;
            font-weight: 700;
            margin-top: 5px;
        }

        .cashier-metric--rebooked .cashier-metric__icon { background: #eaf5ff; color: #1478b8; }
        .cashier-metric--refunded .cashier-metric__icon,
        .cashier-metric--voided .cashier-metric__icon { background: #fff0f0; color: #c83838; }
        .cashier-metric--cancelled .cashier-metric__icon { background: #f1f2f4; color: #6b7280; }

        .cashier-history {
            border-radius: 8px;
            margin-top: 16px;
        }

        .cashier-history__heading {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .cashier-history__heading h5,
        .cashier-history__heading p {
            margin: 0;
        }

        .cashier-history__heading p {
            color: #7b8490;
            font-size: 12px;
            margin-top: 3px;
        }

        .cashier-history__heading a {
            color: #d92378;
            font-size: 13px;
            font-weight: 700;
        }

        .cashier-status {
            background: #eef1f4;
            border-radius: 999px;
            color: #596273;
            display: inline-flex;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 9px;
        }

        .cashier-status--sold { background: #e8f8ef; color: #16804d; }
        .cashier-status--rebooked { background: #e8f4fc; color: #1478b8; }
        .cashier-status--refunded,
        .cashier-status--voided { background: #fff0f0; color: #c83838; }

        @media (max-width: 1199px) {
            .cashier-summary { grid-template-columns: repeat(2, 1fr); }
            .cashier-summary dl { grid-column: 1 / -1; }
            .cashier-metrics { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 767px) {
            .cashier-summary,
            .cashier-metrics { grid-template-columns: 1fr; }
            .cashier-summary dl { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
@endpush
