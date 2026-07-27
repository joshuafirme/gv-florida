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
    </style>
</head>
<body>
    <div class="brand">{{ strtoupper(gs('site_name') ?: 'GV FLORIDA TRANSPORT, INC.') }}</div>
    <h1>Daily Collection Report</h1>
    <p class="meta">Generated: {{ now()->format('F j, Y h:i A') }}</p>
    <p class="meta"><strong>{{ $date->format('l, F j, Y') }}</strong></p>

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
                @php($cashierSummary = $collection['summary'])
                <tr>
                    <td><strong>{{ $collection['cashier'] }}</strong></td>
                    <td class="right">{{ $cashierSummary['tickets'] }}</td>
                    <td class="right">{{ showAmount($cashierSummary['gross_sales']) }}</td>
                    <td class="right {{ $cashierSummary['refunds'] > 0 ? 'negative' : '' }}">
                        {{ $cashierSummary['refunds'] > 0 ? '-' : '' }}{{ showAmount($cashierSummary['refunds']) }}
                    </td>
                    <td class="right {{ $cashierSummary['voids'] > 0 ? 'negative' : '' }}">
                        {{ $cashierSummary['voids'] > 0 ? '-' : '' }}{{ showAmount($cashierSummary['voids']) }}
                    </td>
                    <td class="right total">
                        {{ $cashierSummary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($cashierSummary['net_collection'])) }}
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

    <div class="note">
        Net Revenue = Ticket Sales + Surcharges - Refunds - Voids. Rebooked and cancelled transactions are included in activity counts and have no cash impact.
    </div>
</body>
</html>
