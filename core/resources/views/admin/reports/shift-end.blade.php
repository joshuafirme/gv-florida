@extends('admin.layouts.app')

@section('panel')
    <div class="shift-report-card">
        @include('admin.partials.report-document-header', [
            'reportTitle' => 'Shift End Report',
            'reportDate' => $date,
            'reportDateLabel' => 'Shift date',
            'reportSubject' => 'Cashier: ' . $admin->name,
        ])

        <section class="shift-report-section">
            <h3>Summary</h3>
            <div class="table-responsive">
                <table class="shift-summary-table">
                    <thead>
                        <tr>
                            <th>Cashier</th>
                            <th class="text-end">Tickets</th>
                            <th class="text-end">Gross Sales</th>
                            <th class="text-end">Discounts</th>
                            <th class="text-end">Surcharges</th>
                            <th class="text-end">Refunds</th>
                            <th class="text-end">Voids</th>
                            <th class="text-end">Net Collection</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $admin->name }}</td>
                            <td class="text-end">{{ $summary['tickets'] }}</td>
                            <td class="text-end">{{ showAmount($summary['gross_sales']) }}</td>
                            <td class="text-end">{{ showAmount($summary['discounts']) }}</td>
                            <td class="text-end">{{ showAmount($summary['surcharges']) }}</td>
                            <td class="text-end {{ $summary['refunds'] > 0 ? 'shift-negative' : '' }}">
                                {{ $summary['refunds'] > 0 ? '-' : '' }}{{ showAmount($summary['refunds']) }}
                            </td>
                            <td class="text-end {{ $summary['voids'] > 0 ? 'shift-negative' : '' }}">
                                {{ $summary['voids'] > 0 ? '-' : '' }}{{ showAmount($summary['voids']) }}
                            </td>
                            <td class="text-end"><strong>{{ $summary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($summary['net_collection'])) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="shift-report-section">
            <h3>Detail &middot; Transactions</h3>
            <div class="table-responsive shift-detail-scroll">
                <table class="shift-detail-table">
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
                            <th style="width: 4%">Seat No.</th>
                            <th style="width: 7%">Drop-Off</th>
                            <th class="text-end" style="width: 9%">Payment Details</th>
                            <th style="width: 5.5%">Status</th>
                            <th style="width: 28.5%">Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            @php
                                $statusClass = match ($transaction->status) {
                                    'Sold' => 'sold',
                                    'Rebooked' => 'rebooked',
                                    'Cancelled' => 'cancelled',
                                    'Voided' => 'voided',
                                    'Refunded' => 'refunded',
                                    default => 'neutral',
                                };
                                $amount = (float) $transaction->amount;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $transaction->processed_at->format('M j, Y') }}</strong>
                                    <small>{{ $transaction->processed_at->format('h:i A') }}</small>
                                </td>
                                <td>{{ $transaction->source ?: '-' }}</td>
                                <td><strong class="shift-reference">{{ $transaction->reference_no ?: '-' }}</strong></td>
                                <td>
                                    <strong>{{ $transaction->passenger_name ?: 'Guest' }}</strong>
                                    <small>
                                        {{ $transaction->passenger_type ?: 'Regular' }}
                                        @if ($transaction->passenger_id)
                                            &middot; ID {{ $transaction->passenger_id }}
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    <strong>{{ $transaction->journey_date?->format('M j, Y') ?: '-' }}</strong>
                                    <small>{{ $transaction->departure_time ? date('h:i A', strtotime($transaction->departure_time)) : '-' }}</small>
                                </td>
                                <td>
                                    <strong>{{ $transaction->trip_class ?: '-' }}</strong>
                                    <small>{{ $transaction->trip_route ?: '-' }}</small>
                                </td>
                                <td><strong>{{ formatSeatLabel($transaction->seat_no) ?: '-' }}</strong></td>
                                <td>
                                    <strong>{{ $transaction->km_post ? 'KM ' . $transaction->km_post : ($transaction->drop_off ?: '-') }}</strong>
                                    @if ($transaction->km_post && $transaction->drop_off)
                                        <small>{{ $transaction->drop_off }}</small>
                                    @endif
                                </td>
                                <td class="text-end payment-details-cell">
                                    <strong class="amount-cell {{ $amount < 0 ? 'shift-negative' : '' }}">
                                        {{ $amount < 0 ? '-' : '' }}{{ showAmount(abs($amount)) }}
                                    </strong>
                                    <small>{{ $transaction->payment_method ?: '-' }}</small>
                                </td>
                                <td><span class="shift-status shift-status--{{ $statusClass }}">{{ $transaction->status }}</span></td>
                                <td>@include('admin.partials.transaction-reason', ['transaction' => $transaction])</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="shift-empty">No cashier transactions were recorded for this date.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="8"><strong>Total &middot; {{ $transactions->count() }} transactions</strong></td>
                            <td class="text-end"><strong>{{ $summary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($summary['net_collection'])) }}</strong></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <footer class="shift-report-footer">
            Net Collection = Fares + Surcharges - Refunds - Voids. Cancelled and same-fare rebooked transactions have no cash impact.
        </footer>
    </div>
@endsection

@push('breadcrumb-plugins')
    <div class="shift-report-controls">
        <form action="{{ route('admin.report.shift.end') }}" method="GET" id="shiftDateForm">
            <i class="las la-calendar"></i>
            <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                aria-label="Shift date">
        </form>
        <a class="btn btn--primary" target="_blank"
            href="{{ route('admin.report.shift.end.pdf', ['date' => $date->format('Y-m-d')]) }}">
            <i class="las la-print"></i> Print Shift Report
        </a>
    </div>
@endpush

@push('style')
    <style>
        .shift-report-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .shift-report-controls form {
            position: relative;
        }

        .shift-report-controls form i {
            position: absolute;
            top: 50%;
            left: 12px;
            color: #d92378;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .shift-report-controls input {
            width: 160px;
            height: 38px;
            padding: 7px 10px 7px 35px;
            background: #fff;
            border: 1px solid #d9dce3;
            border-radius: 7px;
            font-size: 12px;
        }

        .shift-report-card {
            padding: 18px 20px;
            background: #fff;
            border: 1px solid #e1e3e8;
            border-radius: 8px;
            color: #222936;
        }

        @include('admin.partials.report-document-header-styles')

        .shift-report-section {
            margin-top: 14px;
        }

        .shift-report-section h3 {
            margin: 0 0 5px;
            color: #303642;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .shift-summary-table,
        .shift-detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .shift-summary-table th,
        .shift-detail-table th {
            padding: 4px 5px;
            color: #fff;
            background: #d92378;
            border: 1px solid #e26ba1;
            font-weight: 600;
            white-space: nowrap;
        }

        .shift-summary-table td,
        .shift-detail-table td {
            padding: 4px 5px;
            border: 1px solid #dfe2e7;
            line-height: 1.2;
            vertical-align: top;
        }

        .shift-detail-table {
            min-width: 1500px;
            table-layout: fixed;
        }

        .shift-detail-table th,
        .shift-detail-table td {
            overflow-wrap: anywhere;
        }

        .shift-detail-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .shift-detail-table td small {
            display: block;
            margin-top: 1px;
            color: #78808e;
            font-size: 9px;
            line-height: 1.15;
        }

        .transaction-reason span {
            display: block;
        }

        .transaction-reason span + span {
            margin-top: 2px;
        }

        .shift-detail-table tfoot td {
            background: #e7e9ed;
        }

        .shift-reference {
            color: #d92378;
            font-size: 12px;
            font-family: monospace;
        }

        .amount-cell {
            display: block;
            font-weight: 600;
            white-space: nowrap;
        }

        .shift-negative {
            color: #c62828;
        }

        .shift-status {
            display: inline-flex;
            padding: 2px 4px;
            border: 1px solid;
            border-radius: 4px;
            font-size: 9px;
            white-space: nowrap;
        }

        .shift-status--sold { color: #087a4b; background: #e9f8f0; border-color: #bce5cf; }
        .shift-status--rebooked { color: #1264a3; background: #e8f4fd; border-color: #bfdef4; }
        .shift-status--cancelled { color: #a15c00; background: #fff4df; border-color: #f0d59d; }
        .shift-status--voided { color: #812bb4; background: #f4eafa; border-color: #ddc1ec; }
        .shift-status--refunded { color: #b42318; background: #fff0ef; border-color: #f4c7c3; }

        .shift-empty {
            padding: 35px !important;
            color: #7a818e;
            text-align: center;
        }

        .shift-report-footer {
            margin-top: 12px;
            color: #8a919e;
            font-size: 9px;
        }

        @media (max-width: 767px) {
            .shift-report-controls {
                align-items: stretch;
                flex-direction: column;
                width: 100%;
            }

            .shift-report-controls input,
            .shift-report-controls .btn {
                width: 100%;
            }

            .shift-report-card {
                padding: 16px 14px;
            }
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm 14mm 12mm 18mm;
            }

            body {
                background: #fff !important;
                box-sizing: border-box;
                padding: 0 !important;
            }

            .sidebar,
            .navbar-wrapper,
            .breadcrumb,
            .shift-report-controls {
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

            .shift-report-card {
                padding: 0;
                border: 0;
            }

            .shift-report-section {
                margin-top: 7px;
            }

            .shift-report-section h3 {
                margin-bottom: 3px;
                font-size: 8px;
            }

            .shift-detail-scroll {
                overflow: visible !important;
            }

            .shift-detail-table {
                min-width: 0;
                font-size: 6.5px;
            }

            .shift-summary-table {
                font-size: 7px;
            }

            .shift-summary-table th,
            .shift-summary-table td,
            .shift-detail-table th,
            .shift-detail-table td {
                padding: 2px 3px;
                line-height: 1.1;
            }

            .shift-detail-table th {
                white-space: normal;
            }

            .shift-detail-table td small,
            .shift-status {
                font-size: 5.5px;
            }

            .shift-status {
                padding: 1px 2px;
            }

            .shift-report-footer {
                margin-top: 6px;
                font-size: 6px;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';
            $('input[name="date"]').on('change', function() {
                $('#shiftDateForm').trigger('submit');
            });
        })(jQuery);
    </script>
@endpush
