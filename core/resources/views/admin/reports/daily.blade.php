@extends('admin.layouts.app')

@section('panel')
    @php
        $pdfParameters = array_filter(
            array_merge(['date' => $date->format('Y-m-d')], $filters),
            fn ($value) => $value !== null && $value !== ''
        );
    @endphp

    <div class="daily-report-filterbar">
        <form action="{{ route('admin.report.daily') }}" method="GET" class="daily-report-filterbar__form">
            <div class="daily-report-filterbar__field">
                <label for="dailyReportDate">Date</label>
                <input type="date" id="dailyReportDate" name="date" value="{{ $date->format('Y-m-d') }}"
                    max="{{ now()->format('Y-m-d') }}">
            </div>

            <div class="daily-report-filterbar__field">
                <label for="dailyReportTransactionType">Transaction Type</label>
                <select id="dailyReportTransactionType" name="transaction_type">
                    <option value="">All Transaction Types</option>
                    @foreach ($filter_options['transaction_types'] as $transactionType)
                        <option value="{{ $transactionType }}" @selected($filters['transaction_type'] === $transactionType)>
                            {{ $transactionType }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="daily-report-filterbar__field">
                <label for="dailyReportSource">Source</label>
                <select id="dailyReportSource" name="source">
                    <option value="">All Sources</option>
                    @foreach ($filter_options['sources'] as $source)
                        <option value="{{ $source }}" @selected($filters['source'] === $source)>{{ $source }}</option>
                    @endforeach
                </select>
            </div>

            <div class="daily-report-filterbar__field">
                <label for="dailyReportProcessedBy">Processed By</label>
                <select id="dailyReportProcessedBy" name="processed_by">
                    <option value="">All Processors</option>
                    @foreach ($filter_options['processed_by'] as $processor)
                        <option value="{{ $processor['value'] }}" @selected($filters['processed_by'] === $processor['value'])>
                            {{ $processor['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="daily-report-filterbar__field">
                <label for="dailyReportPaymentMethod">Payment Method</label>
                <select id="dailyReportPaymentMethod" name="payment_method">
                    <option value="">All Payment Methods</option>
                    @foreach ($filter_options['payment_methods'] as $paymentMethod)
                        <option value="{{ $paymentMethod }}" @selected($filters['payment_method'] === $paymentMethod)>
                            {{ $paymentMethod }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="daily-report-filterbar__actions">
                <button type="submit" class="btn btn--primary">
                    <i class="las la-filter"></i> Apply
                </button>
                <a href="{{ route('admin.report.daily') }}" class="btn btn-light" title="Clear filters">
                    <i class="las la-times"></i> Clear
                </a>
            </div>
        </form>

        <a class="btn btn--primary daily-report-filterbar__print" target="_blank"
            href="{{ route('admin.report.daily.pdf', $pdfParameters) }}">
            <i class="las la-print"></i> Print Daily Report
        </a>
    </div>

    <div class="daily-report">
        @include('admin.partials.report-document-header', [
            'reportTitle' => 'Daily Collection Report',
            'reportDate' => $date,
            'reportDateLabel' => 'Business date',
            'reportFilters' => $active_filter_labels,
        ])

        <section class="daily-report__section">
            <h3>Summary</h3>
            <div class="table-responsive">
                <table class="daily-table daily-table--summary">
                    <thead>
                        <tr>
                            <th class="text-end">Tickets Sold</th>
                            <th class="text-end">Gross Sales</th>
                            <th class="text-end">Discounts</th>
                            <th class="text-end">Surcharges</th>
                            <th class="text-end">Refunds</th>
                            <th class="text-end">Voids</th>
                            <th class="text-end">Net Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-end">{{ $summary['tickets'] }}</td>
                            <td class="text-end">{{ showAmount($summary['gross_sales']) }}</td>
                            <td class="text-end">{{ showAmount($summary['discounts']) }}</td>
                            <td class="text-end">{{ showAmount($summary['surcharges']) }}</td>
                            <td class="text-end daily-negative">
                                {{ $summary['refunds'] > 0 ? '-' : '' }}{{ showAmount($summary['refunds']) }}
                            </td>
                            <td class="text-end daily-negative">
                                {{ $summary['voids'] > 0 ? '-' : '' }}{{ showAmount($summary['voids']) }}
                            </td>
                            <td class="text-end daily-total">
                                {{ $summary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($summary['net_collection'])) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="daily-report__section">
            <h3>Transaction Activity</h3>
            <div class="table-responsive">
                <table class="daily-table daily-table--activity">
                    <thead>
                        <tr>
                            <th class="text-end">Sold</th>
                            <th class="text-end">Rebooked</th>
                            <th class="text-end">Cancelled</th>
                            <th class="text-end">Voided</th>
                            <th class="text-end">Refunded</th>
                            <th class="text-end">Total Events</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-end">{{ $activity['sold'] }}</td>
                            <td class="text-end">{{ $activity['rebooked'] }}</td>
                            <td class="text-end">{{ $activity['cancelled'] }}</td>
                            <td class="text-end">{{ $activity['voided'] }}</td>
                            <td class="text-end">{{ $activity['refunded'] }}</td>
                            <td class="text-end daily-total">{{ $summary['transaction_count'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="daily-report__columns">
            <section class="daily-report__section">
                <h3>Collection by Cashier</h3>
                <div class="table-responsive">
                    <table class="daily-table">
                        <thead>
                            <tr>
                                <th>Cashier</th>
                                <th class="text-end">Tickets</th>
                                <th class="text-end">Gross</th>
                                <th class="text-end">Refunds</th>
                                <th class="text-end">Voids</th>
                                <th class="text-end">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cashier_collections as $collection)
                                <tr>
                                    <td><strong>{{ $collection['cashier'] }}</strong></td>
                                    <td class="text-end">{{ $collection['summary']['tickets'] }}</td>
                                    <td class="text-end">{{ showAmount($collection['summary']['gross_sales']) }}</td>
                                    <td class="text-end {{ $collection['summary']['refunds'] > 0 ? 'daily-negative' : '' }}">
                                        {{ $collection['summary']['refunds'] > 0 ? '-' : '' }}{{ showAmount($collection['summary']['refunds']) }}
                                    </td>
                                    <td class="text-end {{ $collection['summary']['voids'] > 0 ? 'daily-negative' : '' }}">
                                        {{ $collection['summary']['voids'] > 0 ? '-' : '' }}{{ showAmount($collection['summary']['voids']) }}
                                    </td>
                                    <td class="text-end daily-total">
                                        {{ $collection['summary']['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($collection['summary']['net_collection'])) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="daily-empty">No cashier transactions were recorded for this date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($cashier_collections->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td><strong>Total</strong></td>
                                    <td class="text-end"><strong>{{ $summary['tickets'] }}</strong></td>
                                    <td class="text-end"><strong>{{ showAmount($summary['gross_sales']) }}</strong></td>
                                    <td class="text-end daily-negative">
                                        <strong>{{ $summary['refunds'] > 0 ? '-' : '' }}{{ showAmount($summary['refunds']) }}</strong>
                                    </td>
                                    <td class="text-end daily-negative">
                                        <strong>{{ $summary['voids'] > 0 ? '-' : '' }}{{ showAmount($summary['voids']) }}</strong>
                                    </td>
                                    <td class="text-end daily-total">
                                        {{ $summary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($summary['net_collection'])) }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </section>

            <section class="daily-report__section">
                <h3>Sales by Channel</h3>
                <div class="table-responsive">
                    <table class="daily-table">
                        <thead>
                            <tr>
                                <th>Channel</th>
                                <th class="text-end">Tickets</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($channel_collections as $collection)
                                <tr>
                                    <td><strong>{{ $collection['channel'] }}</strong></td>
                                    <td class="text-end">{{ $collection['tickets'] }}</td>
                                    <td class="text-end daily-total">{{ showAmount($collection['amount']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="daily-empty">No ticket sales were recorded for this date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($channel_collections->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td><strong>Total</strong></td>
                                    <td class="text-end"><strong>{{ $channel_collections->sum('tickets') }}</strong></td>
                                    <td class="text-end daily-total">
                                        {{ showAmount($channel_collections->sum('amount')) }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </section>
        </div>

        <section class="daily-report__section daily-report__transactions">
            <h3>Detail - Transactions</h3>
            <div class="table-responsive">
                <table class="daily-table">
                    <colgroup>
                        <col style="width: 7%"><col style="width: 4%"><col style="width: 5%"><col style="width: 6%">
                        <col style="width: 7%"><col style="width: 8%"><col style="width: 6%"><col style="width: 9%">
                        <col style="width: 4%"><col style="width: 6%"><col style="width: 6%"><col style="width: 6%">
                        <col style="width: 6%"><col style="width: 5%"><col style="width: 15%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Transaction Date &amp; Time</th>
                            <th>Source</th>
                            <th>PNR</th>
                            <th>Reference No.</th>
                            <th>Processed By</th>
                            <th>Passenger</th>
                            <th>Departure</th>
                            <th>Trip</th>
                            <th>Seat No.</th>
                            <th>Drop-Off</th>
                            <th>Payment Method</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Discount / Refund</th>
                            <th>Status</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            @php
                                $seat = preg_replace('/^\d+-/', '', (string) $transaction->seat_no);
                                $departureTime = $transaction->departure_time
                                    ? \Carbon\Carbon::parse($transaction->departure_time)->format('h:i A')
                                    : null;
                                $adjustmentAmount = in_array($transaction->status, ['Discount Override', 'Refunded'], true)
                                    ? abs((float) $transaction->amount)
                                    : 0;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $transaction->processed_at->format('M j, Y') }}</strong>
                                    <small>{{ $transaction->processed_at->format('h:i A') }}</small>
                                </td>
                                <td>{{ $transaction->source ?: '-' }}</td>
                                <td><span class="daily-data--pnr">{{ $transaction->pnr ?: '-' }}</span></td>
                                <td><strong class="daily-data--reference">{{ $transaction->reference_no ?: '-' }}</strong></td>
                                <td>{{ $transaction->processed_by_label }}</td>
                                <td>
                                    <strong>{{ $transaction->passenger_name ?: 'Guest' }}</strong><br>
                                    <small>{{ $transaction->passenger_type ?: 'Regular' }}{{ $transaction->passenger_id ? ' - ID ' . $transaction->passenger_id : '' }}</small>
                                </td>
                                <td>
                                    <strong class="daily-data--travel-date">{{ $transaction->journey_date?->format('M j, Y') ?: '-' }}</strong>
                                    <span class="daily-data--departure-time">{{ $departureTime ?: '-' }}</span>
                                </td>
                                <td><strong>{{ $transaction->trip_class ?: '-' }}</strong><br><small>{{ $transaction->trip_route ?: '-' }}</small></td>
                                <td>{{ $seat ?: '-' }}</td>
                                <td><strong class="daily-data--km-post">{{ $transaction->km_post ? 'KM ' . $transaction->km_post : '-' }}</strong><br><small>{{ $transaction->drop_off ?: '' }}</small></td>
                                <td>{{ $transaction->payment_method ?: '-' }}</td>
                                <td class="text-end daily-total {{ $transaction->amount < 0 ? 'daily-negative' : '' }}">
                                    <strong class="daily-data--amount">{{ $transaction->amount < 0 ? '-' : '' }}{{ showAmount(abs($transaction->amount)) }}</strong>
                                </td>
                                <td class="text-end daily-total {{ $adjustmentAmount > 0 ? 'daily-negative' : '' }}">
                                    {{ $adjustmentAmount > 0 ? '-' . showAmount($adjustmentAmount) : '-' }}
                                </td>
                                <td><span class="daily-status daily-status--{{ \Illuminate\Support\Str::slug($transaction->status) }}">{{ $transaction->status }}</span></td>
                                <td>@include('admin.partials.transaction-reason', ['transaction' => $transaction])</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="daily-empty">No transactions were recorded for this date.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($transactions->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="11"><strong>Total - {{ $transactions->count() }} transactions</strong></td>
                                <td class="text-end daily-total {{ $summary['net_collection'] < 0 ? 'daily-negative' : '' }}">
                                    {{ $summary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($summary['net_collection'])) }}
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </section>

        <footer class="daily-report__footer">
            Net Revenue = Ticket Sales + Surcharges - Refunds - Voids. Rebooked and cancelled transactions are
            included in activity counts and have no cash impact.
        </footer>
    </div>
@endsection

@push('style')
    <style>
        .daily-report-filterbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
        }

        .daily-report-filterbar__form {
            display: flex;
            align-items: flex-end;
            flex: 1 1 auto;
            flex-wrap: wrap;
            gap: 10px;
            min-width: 0;
        }

        .daily-report-filterbar__field {
            flex: 1 1 150px;
            min-width: 135px;
        }

        .daily-report-filterbar__field label {
            display: block;
            margin-bottom: 5px;
            color: #505766;
            font-size: 11px;
            font-weight: 600;
        }

        .daily-report-filterbar__field input,
        .daily-report-filterbar__field select {
            width: 100%;
            height: 38px;
            padding: 7px 10px;
            background: #fff;
            border: 1px solid #d9dce3;
            border-radius: 7px;
            color: #303642;
            font-size: 12px;
        }

        .daily-report-filterbar__actions {
            display: flex;
            flex: 0 0 auto;
            gap: 8px;
        }

        .daily-report-filterbar__actions .btn,
        .daily-report-filterbar__print {
            align-items: center;
            display: inline-flex;
            gap: 5px;
            height: 38px;
            justify-content: center;
            white-space: nowrap;
        }

        .daily-report {
            padding: 18px 20px;
            background: #fff;
            border: 1px solid #e1e3e8;
            border-radius: 8px;
            color: #222936;
        }

        @include('admin.partials.report-document-header-styles')

        .daily-report__section {
            min-width: 0;
            margin-top: 14px;
        }

        .daily-report__section h3 {
            margin: 0 0 5px;
            color: #303642;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .daily-report__columns {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(360px, .9fr);
            gap: 14px;
        }

        .daily-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            table-layout: fixed;
        }

        .daily-table th {
            padding: 5px 6px;
            color: #fff;
            background: #d92378;
            border: 1px solid #e26ba1;
            font-weight: 600;
            white-space: nowrap;
        }

        .daily-table td {
            padding: 5px 6px;
            border: 1px solid #dfe2e7;
            line-height: 1.2;
            overflow-wrap: anywhere;
            vertical-align: middle;
        }

        .daily-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .daily-table tfoot td {
            background: #e7e9ed;
        }

        .daily-total {
            font-weight: 700;
            white-space: nowrap;
        }

        .daily-negative {
            color: #c62828;
        }

        .daily-empty {
            padding: 24px !important;
            color: #7a818e;
            text-align: center;
        }

        .daily-report__transactions .daily-table {
            min-width: 1650px;
            font-size: 9px;
        }

        .daily-report__transactions .daily-table th,
        .daily-report__transactions .daily-table td {
            padding: 5px;
        }

        .daily-report__transactions small {
            color: #7a818e;
            font-size: 8px;
        }

        .daily-data--pnr {
            color: #7a818e;
            font-size: 8px;
            font-weight: 500;
        }

        .daily-data--reference,
        .daily-data--travel-date,
        .daily-data--departure-time,
        .daily-data--km-post,
        .daily-data--amount {
            color: #17202d;
            font-weight: 800;
        }

        .daily-data--reference,
        .daily-data--amount {
            font-size: 10.5px;
            white-space: nowrap;
        }

        .daily-data--travel-date,
        .daily-data--departure-time {
            display: block;
            font-size: 10px;
            line-height: 1.15;
            white-space: nowrap;
        }

        .daily-data--departure-time {
            margin-top: 2px;
        }

        .daily-data--km-post {
            font-size: 10px;
            white-space: nowrap;
        }

        .daily-negative .daily-data--amount {
            color: inherit;
        }
        .transaction-reason span {
            display: block;
        }

        .transaction-reason span + span {
            margin-top: 2px;
        }
        .daily-status {
            display: inline-block;
            padding: 2px 5px;
            border: 1px solid #d9dce3;
            border-radius: 999px;
            font-size: 8px;
            line-height: 1.1;
            white-space: nowrap;
        }

        .daily-status--sold { color: #167944; background: #ecfdf3; border-color: #b6ead0; }
        .daily-status--rebooked { color: #1c6b9e; background: #eef8ff; border-color: #b9dff7; }
        .daily-status--cancelled,
        .daily-status--refunded { color: #b42318; background: #fff3f2; border-color: #ffc8c2; }
        .daily-status--voided { color: #7f3bb1; background: #f7f0ff; border-color: #dec5fa; }
        .daily-status--discount-override { color: #9b5c00; background: #fff8e6; border-color: #efd69b; }
        .daily-status--validated { color: #167944; background: #ecfdf3; border-color: #b6ead0; }

        .daily-report__footer {
            margin-top: 14px;
            color: #8a919e;
            font-size: 9px;
        }

        @media (max-width: 991px) {
            .daily-report__columns {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        @media (max-width: 767px) {
            .daily-report-filterbar {
                align-items: stretch;
                flex-direction: column;
                width: 100%;
            }

            .daily-report-filterbar__form,
            .daily-report-filterbar__actions,
            .daily-report-filterbar__actions .btn,
            .daily-report-filterbar__print {
                width: 100%;
            }

            .daily-report-filterbar__field {
                flex-basis: calc(50% - 5px);
            }

            .daily-report {
                padding: 16px 14px;
            }
        }

        @media print {
            @page {
                size: legal landscape;
                margin: 6mm;
            }

            body {
                background: #fff !important;
            }

            .sidebar,
            .navbar-wrapper,
            .breadcrumb,
            .daily-report-filterbar {
                display: none !important;
            }

            .page-wrapper,
            .body-wrapper,
            .bodywrapper__inner,
            .container-fluid {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .daily-report {
                padding: 0;
                border: 0;
            }

            .daily-report__columns {
                grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr);
                gap: 8px;
            }

            .daily-report__section {
                margin-top: 7px;
            }

            .daily-report__section h3 {
                margin-bottom: 3px;
                font-size: 8px;
            }

            .daily-table {
                font-size: 7px;
            }

            .daily-table th,
            .daily-table td {
                padding: 2px 3px;
                line-height: 1.1;
            }

            .daily-report__transactions .daily-table {
                min-width: 0;
                font-size: 6px;
            }

            .daily-report__transactions .daily-table th,
            .daily-report__transactions .daily-table td {
                padding: 2px;
            }

            .daily-report__transactions small,
            .daily-status {
                font-size: 5px;
            }

            .daily-data--pnr {
                font-size: 5px;
            }

            .daily-data--reference,
            .daily-data--amount,
            .daily-data--travel-date,
            .daily-data--departure-time,
            .daily-data--km-post {
                font-size: 7px;
            }

            .daily-report__footer {
                margin-top: 6px;
                font-size: 6px;
            }
        }
    </style>
@endpush
