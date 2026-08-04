<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $pageTitle }}</title>
    <style>
        @page { margin: 6mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #222936;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 7px;
        }
        .brand {
            margin-bottom: 5px;
            color: #10131a;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
        }
        h1 {
            margin: 0 0 1px;
            color: #df2a82;
            font-size: 14px;
        }
        .meta {
            margin: 0 0 1px;
            color: #5f6674;
            font-size: 8px;
        }
        .cashier { color: #303642; }
        section { margin-top: 7px; }
        h2 {
            margin: 0 0 3px;
            color: #303642;
            font-size: 8px;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th {
            padding: 2px 3px;
            color: #fff;
            background: #df2a82;
            border: 1px solid #e26ba1;
            font-size: 6.3px;
            font-weight: 600;
            line-height: 1.05;
            text-align: left;
        }
        td {
            padding: 2px 3px;
            border: 1px solid #dfe2e7;
            font-size: 6.3px;
            line-height: 1.08;
            overflow-wrap: break-word;
            vertical-align: top;
        }
        tbody tr:nth-child(even) { background: #f8f9fa; }
        tbody tr { page-break-inside: avoid; }
        td small {
            display: block;
            margin-top: 1px;
            color: #78808e;
            font-size: 5.4px;
            line-height: 1.05;
        }
        tfoot td { background: #e7e9ed; }
        .text-right { text-align: right; }
        .pnr { color: #df2a82; }
        .reference { font-family: "DejaVu Sans Mono", monospace; }
        .amount { font-weight: 700; white-space: nowrap; }
        .negative { color: #c62828; }
        .status {
            display: inline-block;
            padding: 1px 2px;
            border: 1px solid #c9ced6;
            border-radius: 2px;
            white-space: nowrap;
        }
        .status-sold { color: #087a4b; background: #e9f8f0; border-color: #bce5cf; }
        .status-rebooked { color: #1264a3; background: #e8f4fd; border-color: #bfdef4; }
        .status-cancelled { color: #a15c00; background: #fff4df; border-color: #f0d59d; }
        .status-voided { color: #812bb4; background: #f4eafa; border-color: #ddc1ec; }
        .status-refunded { color: #b42318; background: #fff0ef; border-color: #f4c7c3; }
        .empty { padding: 20px; color: #7a818e; text-align: center; }
        footer { margin-top: 6px; color: #8a919e; font-size: 6px; }
    </style>
</head>
<body>
    <header>
        <div class="brand">{{ strtoupper(gs('site_name') ?: 'GV FLORIDA TRANSPORT, INC.') }}</div>
        <h1>Shift End Report</h1>
        <p class="meta">Generated: {{ now()->format('F j, Y h:i A') }}</p>
        <p class="meta cashier">{{ $admin->name }} &middot; {{ $date->format('l, F j, Y') }}</p>
    </header>

    <section>
        <h2>Summary</h2>
        <table>
            <thead>
                <tr>
                    <th>Cashier</th>
                    <th class="text-right">Tickets</th>
                    <th class="text-right">Gross Sales</th>
                    <th class="text-right">Discounts</th>
                    <th class="text-right">Surcharges</th>
                    <th class="text-right">Refunds</th>
                    <th class="text-right">Voids</th>
                    <th class="text-right">Net Collection</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $admin->name }}</td>
                    <td class="text-right">{{ $summary['tickets'] }}</td>
                    <td class="text-right">{{ showAmount($summary['gross_sales']) }}</td>
                    <td class="text-right">{{ showAmount($summary['discounts']) }}</td>
                    <td class="text-right">{{ showAmount($summary['surcharges']) }}</td>
                    <td class="text-right {{ $summary['refunds'] > 0 ? 'negative' : '' }}">{{ $summary['refunds'] > 0 ? '-' : '' }}{{ showAmount($summary['refunds']) }}</td>
                    <td class="text-right {{ $summary['voids'] > 0 ? 'negative' : '' }}">{{ $summary['voids'] > 0 ? '-' : '' }}{{ showAmount($summary['voids']) }}</td>
                    <td class="text-right amount">{{ $summary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($summary['net_collection'])) }}</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Detail &middot; Transactions</h2>
        <table>
            <colgroup>
                <col style="width: 9%"><col style="width: 5%"><col style="width: 7%">
                <col style="width: 7%"><col style="width: 9%"><col style="width: 8%">
                <col style="width: 11%"><col style="width: 5%"><col style="width: 7%">
                <col style="width: 7%"><col style="width: 7%"><col style="width: 6%">
                <col style="width: 12%">
            </colgroup>
            <thead>
                <tr>
                    <th>Transaction Date &amp; Time</th><th>Source</th><th>PNR</th>
                    <th>Reference No.</th><th>Passenger</th><th>Journey</th><th>Trip</th>
                    <th>Seat No.</th><th>Drop-Off</th><th>Payment Method</th>
                    <th class="text-right">Amount</th><th>Status</th><th>Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $transaction)
                    @php
                        $statusClass = strtolower($transaction->status ?: 'neutral');
                        $amount = (float) $transaction->amount;
                    @endphp
                    <tr>
                        <td><strong>{{ $transaction->processed_at->format('M j, Y') }}</strong><small>{{ $transaction->processed_at->format('h:i A') }}</small></td>
                        <td>{{ $transaction->source ?: '-' }}</td>
                        <td><strong class="pnr">{{ $transaction->pnr ?: '-' }}</strong></td>
                        <td class="reference">{{ $transaction->reference_no ?: '-' }}</td>
                        <td><strong>{{ $transaction->passenger_name ?: 'Guest' }}</strong><small>{{ $transaction->passenger_type ?: 'Regular' }}@if ($transaction->passenger_id) &middot; ID {{ $transaction->passenger_id }}@endif</small></td>
                        <td><strong>{{ $transaction->journey_date?->format('M j, Y') ?: '-' }}</strong><small>{{ $transaction->departure_time ? date('h:i A', strtotime($transaction->departure_time)) : '-' }}</small></td>
                        <td><strong>{{ $transaction->trip_class ?: '-' }}</strong><small>{{ $transaction->trip_route ?: '-' }}</small></td>
                        <td><strong>{{ formatSeatLabel($transaction->seat_no) ?: '-' }}</strong></td>
                        <td><strong>{{ $transaction->km_post ? 'KM ' . $transaction->km_post : ($transaction->drop_off ?: '-') }}</strong>@if ($transaction->km_post && $transaction->drop_off)<small>{{ $transaction->drop_off }}</small>@endif</td>
                        <td>{{ $transaction->payment_method ?: '-' }}</td>
                        <td class="text-right amount {{ $amount < 0 ? 'negative' : '' }}">{{ $amount < 0 ? '-' : '' }}{{ showAmount(abs($amount)) }}</td>
                        <td><span class="status status-{{ $statusClass }}">{{ $transaction->status }}</span></td>
                        <td>{{ $transaction->reason ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="13" class="empty">No cashier transactions were recorded for this date.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="10"><strong>Total &middot; {{ $transactions->count() }} transactions</strong></td>
                    <td class="text-right amount">{{ $summary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($summary['net_collection'])) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </section>

    <footer>Net Collection = Fares + Surcharges - Refunds - Voids. Cancelled and same-fare rebooked transactions have no cash impact.</footer>
</body>
</html>
