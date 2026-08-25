<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shift End Report - {{ $date->format('Y-m-d') }}</title>
    <style>
        @page {
            size: Letter landscape;
            margin: 10mm 14mm 12mm 18mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            color: #222936;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 7px;
            line-height: 1.15;
        }

        @include('admin.partials.report-document-header-styles', ['pdfHeader' => true])

        .section-title {
            margin: 7px 0 3px;
            color: #303642;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th {
            padding: 3px 3px;
            color: #fff;
            background: #d92378;
            border: 0.45px solid #d45a91;
            font-size: 6.2px;
            font-weight: 700;
            line-height: 1.1;
            text-align: left;
        }

        td {
            padding: 3px 3px;
            border: 0.45px solid #cfd4dc;
            line-height: 1.12;
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

        .summary {
            font-size: 7px;
        }

        .summary th,
        .summary td {
            padding: 3px 4px;
        }

        .right {
            text-align: right;
        }

        .sub {
            display: block;
            margin-top: 1px;
            color: #707887;
            font-size: 5.7px;
            line-height: 1.1;
        }

        .reference {
            color: #d92378;
            font-weight: 700;
            font-size: 10px;
        }

        .payment-details .amount {
            display: block;
        }

        .amount {
            font-weight: 700;
            white-space: nowrap;
        }

        .negative {
            color: #b42318;
        }

        .status {
            display: inline-block;
            padding: 1px 2px;
            border: 0.4px solid #b9c1cc;
            border-radius: 2px;
            font-size: 5.5px;
            white-space: nowrap;
        }

        .status-sold { color: #087a4b; background: #e9f8f0; }
        .status-rebooked { color: #1264a3; background: #e8f4fd; }
        .status-cancelled { color: #8a5700; background: #fff4df; }
        .status-voided { color: #7423a5; background: #f4eafa; }
        .status-refunded { color: #b42318; background: #fff0ef; }

        .note {
            margin-top: 6px;
            color: #737c8b;
            font-size: 5.8px;
        }

        .empty {
            padding: 16px;
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
    @include('admin.partials.report-document-header', [
        'reportTitle' => 'Shift End Report',
        'reportDate' => $date,
        'reportDateLabel' => 'Shift date',
        'reportSubject' => 'Cashier: ' . $admin->name,
    ])

    <div class="section-title">Summary</div>
    <table class="summary">
        <colgroup>
            <col style="width: 23%">
            <col style="width: 7%">
            <col style="width: 12%">
            <col style="width: 10%">
            <col style="width: 10%">
            <col style="width: 10%">
            <col style="width: 10%">
            <col style="width: 18%">
        </colgroup>
        <thead>
            <tr>
                <th>Cashier</th>
                <th class="right">Tickets</th>
                <th class="right">Gross Sales</th>
                <th class="right">Discounts</th>
                <th class="right">Surcharges</th>
                <th class="right">Refunds</th>
                <th class="right">Voids</th>
                <th class="right">Net Collection</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $admin->name }}</td>
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
                <td class="right amount">
                    {{ $summary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($summary['net_collection'])) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Detail - Transactions</div>
    <table>
        <colgroup>
            <col width="7.5%" style="width: 7.5%">
            <col width="4.5%" style="width: 4.5%">
            <col width="7%" style="width: 7%">
            <col width="9%" style="width: 9%">
            <col width="7%" style="width: 7%">
            <col width="11%" style="width: 11%">
            <col width="4%" style="width: 4%">
            <col width="7%" style="width: 7%">
            <col width="9%" style="width: 9%">
            <col width="5.5%" style="width: 5.5%">
            <col width="28.5%" style="width: 28.5%">
        </colgroup>
        <thead>
            <tr>
                <th style="width: 7.5%">Transaction Date &amp; Time</th>
                <th style="width: 4.5%">Source</th>
                <th style="width: 7%">Reference No.</th>
                <th style="width: 9%">Passenger</th>
                <th style="width: 7%">Departure</th>
                <th style="width: 11%">Trip</th>
                <th style="width: 4%">Seat</th>
                <th style="width: 7%">Drop-Off</th>
                <th class="right" style="width: 9%">Payment Details</th>
                <th style="width: 5.5%">Status</th>
                <th style="width: 28.5%">Reason</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $transaction)
                @php
                    $amount = (float) $transaction->amount;
                    $statusClass = strtolower($transaction->status);
                @endphp
                <tr>
                    <td>
                        <strong>{{ $transaction->processed_at->format('M j, Y') }}</strong>
                        <span class="sub">{{ $transaction->processed_at->format('h:i A') }}</span>
                    </td>
                    <td>{{ $transaction->source ?: '-' }}</td>
                    <td class="reference">{{ $transaction->reference_no ?: '-' }}</td>
                    <td>
                        <strong>{{ $transaction->passenger_name ?: 'Guest' }}</strong>
                        <span class="sub">
                            {{ $transaction->passenger_type ?: 'Regular' }}
                            @if ($transaction->passenger_id)
                                &middot; ID {{ $transaction->passenger_id }}
                            @endif
                        </span>
                    </td>
                    <td>
                        <strong>{{ $transaction->journey_date?->format('M j, Y') ?: '-' }}</strong>
                        <span class="sub">
                            {{ $transaction->departure_time ? date('h:i A', strtotime($transaction->departure_time)) : '-' }}
                        </span>
                    </td>
                    <td>
                        <strong>{{ $transaction->trip_class ?: '-' }}</strong>
                        <span class="sub">{{ $transaction->trip_route ?: '-' }}</span>
                    </td>
                    <td><strong>{{ formatSeatLabel($transaction->seat_no) ?: '-' }}</strong></td>
                    <td>
                        <strong>{{ $transaction->km_post ? 'KM ' . $transaction->km_post : ($transaction->drop_off ?: '-') }}</strong>
                        @if ($transaction->km_post && $transaction->drop_off)
                            <span class="sub">{{ $transaction->drop_off }}</span>
                        @endif
                    </td>
                    <td class="right payment-details">
                        <strong class="amount {{ $amount < 0 ? 'negative' : '' }}">
                            {{ $amount < 0 ? '-' : '' }}{{ showAmount(abs($amount)) }}
                        </strong>
                        <span class="sub">{{ $transaction->payment_method ?: '-' }}</span>
                    </td>
                    <td><span class="status status-{{ $statusClass }}">{{ $transaction->status }}</span></td>
                    <td>@include('admin.partials.transaction-reason', ['transaction' => $transaction])</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="empty">No cashier transactions were recorded for this date.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8">Total - {{ $transactions->count() }} transactions</td>
                <td class="right">
                    {{ $summary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($summary['net_collection'])) }}
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="note">
        Net Collection = Fares + Surcharges - Refunds - Voids. Cancelled and same-fare rebooked transactions have no cash impact.
    </div>
</body>
</html>
