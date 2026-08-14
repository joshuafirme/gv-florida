<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Report - {{ $date->format('Y-m-d') }}</title>
    <style>
        @page {
            size: legal landscape;
            margin: 8mm 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #222936;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 8px;
            line-height: 1.2;
        }

        .brand {
            margin: 0 0 10px;
            color: #11151d;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
        }

        h1 {
            margin: 0 0 2px;
            color: #d92378;
            font-size: 17px;
        }

        .meta {
            margin: 0 0 2px;
            color: #586170;
            font-size: 8px;
        }

        .section-title {
            margin: 10px 0 4px;
            color: #303642;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th {
            padding: 4px 5px;
            color: #fff;
            background: #d92378;
            border: 0.5px solid #d45a91;
            font-size: 7px;
            font-weight: 700;
            text-align: left;
        }

        td {
            padding: 4px 5px;
            border: 0.5px solid #cfd4dc;
            vertical-align: top;
            word-break: break-word;
        }

        tbody tr:nth-child(even) {
            background: #f6f7f9;
        }

        tfoot td {
            background: #e8eaee;
            font-weight: 700;
        }

        .right {
            text-align: right;
        }

        .total {
            font-weight: 700;
            white-space: nowrap;
        }

        .negative {
            color: #b42318;
        }

        .subtle {
            color: #737c8b;
            font-size: 6px;
        }

        .transactions-table {
            font-size: 6.2px;
        }

        .transactions-table th,
        .transactions-table td {
            padding: 2px 3px;
            line-height: 1.1;
        }

        .note {
            margin-top: 10px;
            color: #737c8b;
            font-size: 6.5px;
        }

        .empty {
            padding: 18px;
            color: #737c8b;
            text-align: center;
        }

        .transaction-reason span {
            display: block;
        }

        .transaction-reason span + span {
            margin-top: 1px;
        }
    </style>
</head>
<body>
    <div class="brand">{{ strtoupper(gs('site_name') ?: 'GV FLORIDA TRANSPORT, INC.') }}</div>
    <h1>Daily Collection Report</h1>
    <p class="meta">Generated: {{ now()->format('F j, Y h:i A') }}</p>
    <p class="meta"><strong>{{ $date->format('l, F j, Y') }}</strong></p>
    @if ($active_filter_labels)
        <p class="meta">
            <strong>Filters:</strong>
            {{ collect($active_filter_labels)->map(fn ($value, $label) => $label . ': ' . $value)->implode(' | ') }}
        </p>
    @endif

    <div class="section-title">Summary</div>
    <table>
        <thead>
            <tr>
                <th class="right">Tickets Sold</th>
                <th class="right">Gross Sales</th>
                <th class="right">Discounts</th>
                <th class="right">Surcharges</th>
                <th class="right">Refunds</th>
                <th class="right">Voids</th>
                <th class="right">Net Revenue</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="right">{{ $summary['tickets'] }}</td>
                <td class="right">{{ showAmount($summary['gross_sales']) }}</td>
                <td class="right">{{ showAmount($summary['discounts']) }}</td>
                <td class="right">{{ showAmount($summary['surcharges']) }}</td>
                <td class="right {{ $summary['refunds'] > 0 ? 'negative' : '' }}">
                    {{ $summary['refunds'] > 0 ? '-' : '' }}{{ showAmount($summary['refunds']) }}
                </td>
                <td class="right {{ $summary['voids'] > 0 ? 'negative' : '' }}">
                    {{ $summary['voids'] > 0 ? '-' : '' }}{{ showAmount($summary['voids']) }}
                </td>
                <td class="right total">
                    {{ $summary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($summary['net_collection'])) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Transaction Activity</div>
    <table>
        <thead>
            <tr>
                <th class="right">Sold</th>
                <th class="right">Rebooked</th>
                <th class="right">Cancelled</th>
                <th class="right">Voided</th>
                <th class="right">Refunded</th>
                <th class="right">Total Events</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="right">{{ $activity['sold'] }}</td>
                <td class="right">{{ $activity['rebooked'] }}</td>
                <td class="right">{{ $activity['cancelled'] }}</td>
                <td class="right">{{ $activity['voided'] }}</td>
                <td class="right">{{ $activity['refunded'] }}</td>
                <td class="right total">{{ $summary['transaction_count'] }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Collection by Cashier</div>
    <table>
        <colgroup>
            <col style="width: 31%">
            <col style="width: 9%">
            <col style="width: 15%">
            <col style="width: 15%">
            <col style="width: 15%">
            <col style="width: 15%">
        </colgroup>
        <thead>
            <tr>
                <th>Cashier</th>
                <th class="right">Tickets</th>
                <th class="right">Gross</th>
                <th class="right">Refunds</th>
                <th class="right">Voids</th>
                <th class="right">Net</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cashier_collections as $collection)
                <tr>
                    <td><strong>{{ $collection['cashier'] }}</strong></td>
                    <td class="right">{{ $collection['summary']['tickets'] }}</td>
                    <td class="right">{{ showAmount($collection['summary']['gross_sales']) }}</td>
                    <td class="right {{ $collection['summary']['refunds'] > 0 ? 'negative' : '' }}">
                        {{ $collection['summary']['refunds'] > 0 ? '-' : '' }}{{ showAmount($collection['summary']['refunds']) }}
                    </td>
                    <td class="right {{ $collection['summary']['voids'] > 0 ? 'negative' : '' }}">
                        {{ $collection['summary']['voids'] > 0 ? '-' : '' }}{{ showAmount($collection['summary']['voids']) }}
                    </td>
                    <td class="right total">
                        {{ $collection['summary']['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($collection['summary']['net_collection'])) }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No cashier transactions were recorded for this date.</td></tr>
            @endforelse
        </tbody>
        @if ($cashier_collections->isNotEmpty())
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="right">{{ $summary['tickets'] }}</td>
                    <td class="right">{{ showAmount($summary['gross_sales']) }}</td>
                    <td class="right {{ $summary['refunds'] > 0 ? 'negative' : '' }}">
                        {{ $summary['refunds'] > 0 ? '-' : '' }}{{ showAmount($summary['refunds']) }}
                    </td>
                    <td class="right {{ $summary['voids'] > 0 ? 'negative' : '' }}">
                        {{ $summary['voids'] > 0 ? '-' : '' }}{{ showAmount($summary['voids']) }}
                    </td>
                    <td class="right">{{ $summary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($summary['net_collection'])) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="section-title">Sales by Channel</div>
    <table>
        <colgroup>
            <col style="width: 60%">
            <col style="width: 15%">
            <col style="width: 25%">
        </colgroup>
        <thead>
            <tr>
                <th>Channel</th>
                <th class="right">Tickets</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($channel_collections as $collection)
                <tr>
                    <td><strong>{{ $collection['channel'] }}</strong></td>
                    <td class="right">{{ $collection['tickets'] }}</td>
                    <td class="right total">{{ showAmount($collection['amount']) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty">No ticket sales were recorded for this date.</td></tr>
            @endforelse
        </tbody>
        @if ($channel_collections->isNotEmpty())
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="right">{{ $channel_collections->sum('tickets') }}</td>
                    <td class="right">{{ showAmount($channel_collections->sum('amount')) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="section-title">Detail - Transactions</div>
    <table class="transactions-table">
        <colgroup>
            <col style="width: 7%"><col style="width: 4%"><col style="width: 5%"><col style="width: 6%">
            <col style="width: 7%"><col style="width: 8%"><col style="width: 6%"><col style="width: 9%">
            <col style="width: 4%"><col style="width: 6%"><col style="width: 6%"><col style="width: 6%">
            <col style="width: 5%"><col style="width: 21%">
        </colgroup>
        <thead>
            <tr>
                <th>Transaction Date &amp; Time</th><th>Source</th><th>PNR</th><th>Reference No.</th>
                <th>Processed By</th><th>Passenger</th><th>Journey</th><th>Trip</th><th>Seat No.</th>
                <th>Drop-Off</th><th>Payment Method</th><th class="right">Amount</th><th>Status</th><th>Reason</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $transaction)
                @php
                    $seat = preg_replace('/^\d+-/', '', (string) $transaction->seat_no);
                    $departureTime = $transaction->departure_time
                        ? \Carbon\Carbon::parse($transaction->departure_time)->format('h:i A')
                        : null;
                @endphp
                <tr>
                    <td>{{ $transaction->processed_at?->format('M j, Y') }}<br><span class="subtle">{{ $transaction->processed_at?->format('h:i A') }}</span></td>
                    <td>{{ $transaction->source ?: '-' }}</td><td>{{ $transaction->pnr ?: '-' }}</td><td>{{ $transaction->reference_no ?: '-' }}</td>
                    <td>{{ $transaction->processed_by_label }}</td>
                    <td><strong>{{ $transaction->passenger_name ?: 'Guest' }}</strong><br><span class="subtle">{{ $transaction->passenger_type ?: 'Regular' }}{{ $transaction->passenger_id ? ' - ID ' . $transaction->passenger_id : '' }}</span></td>
                    <td>{{ $transaction->journey_date?->format('M j, Y') ?: '-' }}<br><span class="subtle">{{ $departureTime ?: '-' }}</span></td>
                    <td><strong>{{ $transaction->trip_class ?: '-' }}</strong><br><span class="subtle">{{ $transaction->trip_route ?: '-' }}</span></td>
                    <td>{{ $seat ?: '-' }}</td><td>{{ $transaction->km_post ? 'KM ' . $transaction->km_post : '-' }}<br><span class="subtle">{{ $transaction->drop_off ?: '' }}</span></td>
                    <td>{{ $transaction->payment_method ?: '-' }}</td>
                    <td class="right total {{ $transaction->amount < 0 ? 'negative' : '' }}">{{ $transaction->amount < 0 ? '-' : '' }}{{ showAmount(abs($transaction->amount)) }}</td>
                    <td>{{ $transaction->status }}</td>
                    <td>@include('admin.partials.transaction-reason', ['transaction' => $transaction])</td>
                </tr>
            @empty
                <tr><td colspan="14" class="empty">No transactions were recorded for this date.</td></tr>
            @endforelse
        </tbody>
        @if ($transactions->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="11">Total - {{ $transactions->count() }} transactions</td>
                    <td class="right {{ $summary['net_collection'] < 0 ? 'negative' : '' }}">{{ $summary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($summary['net_collection'])) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="note">
        Net Revenue = Ticket Sales + Surcharges - Refunds - Voids. Rebooked and cancelled transactions are included in activity counts and have no cash impact.
    </div>
</body>
</html>
