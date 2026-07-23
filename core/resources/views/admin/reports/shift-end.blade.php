@extends('admin.layouts.app')

@section('panel')
    <div class="shift-report-card">
        <header class="shift-report-header">
            <div class="shift-report-brand">{{ strtoupper(gs('site_name') ?: 'GV FLORIDA TRANSPORT, INC.') }}</div>
            <h2>Shift End Report</h2>
            <p>Generated: {{ now()->format('F j, Y h:i A') }}</p>
            <strong>{{ $admin->name }} &middot; {{ $date->format('l, F j, Y') }}</strong>
        </header>

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
                    <thead>
                        <tr>
                            <th>Transaction Date &amp; Time</th>
                            <th>Source</th>
                            <th>PNR</th>
                            <th>Reference No.</th>
                            <th>Passenger</th>
                            <th>Journey</th>
                            <th>Trip</th>
                            <th>Seat No.</th>
                            <th>Drop-Off</th>
                            <th>Payment Method</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th>Reason</th>
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
                                <td><strong class="shift-pnr">{{ $transaction->pnr ?: '-' }}</strong></td>
                                <td class="shift-reference">{{ $transaction->reference_no ?: '-' }}</td>
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
                                <td><strong>{{ $transaction->seat_no ?: '-' }}</strong></td>
                                <td>
                                    <strong>{{ $transaction->km_post ? 'KM ' . $transaction->km_post : ($transaction->drop_off ?: '-') }}</strong>
                                    @if ($transaction->km_post && $transaction->drop_off)
                                        <small>{{ $transaction->drop_off }}</small>
                                    @endif
                                </td>
                                <td>{{ $transaction->payment_method ?: '-' }}</td>
                                <td class="text-end amount-cell {{ $amount < 0 ? 'shift-negative' : '' }}">
                                    {{ $amount < 0 ? '-' : '' }}{{ showAmount(abs($amount)) }}
                                </td>
                                <td><span class="shift-status shift-status--{{ $statusClass }}">{{ $transaction->status }}</span></td>
                                <td>{{ $transaction->reason ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="shift-empty">No cashier transactions were recorded for this date.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="10"><strong>Total &middot; {{ $transactions->count() }} transactions</strong></td>
                            <td class="text-end"><strong>{{ $summary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($summary['net_collection'])) }}</strong></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <footer class="shift-report-footer">
            Net Collection = Sold transactions - Refunds - Voids. Cancelled and same-fare rebooked transactions have no cash impact.
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
        <button type="button" class="btn btn--primary" onclick="window.print()">
            <i class="las la-print"></i> Print Shift Report
        </button>
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
            padding: 42px 28px 24px;
            background: #fff;
            border: 1px solid #e1e3e8;
            border-radius: 8px;
            color: #222936;
        }

        .shift-report-header {
            margin-bottom: 25px;
        }

        .shift-report-brand {
            margin-bottom: 34px;
            color: #10131a;
            font-size: 15px;
            font-weight: 700;
            text-align: center;
        }

        .shift-report-header h2 {
            margin: 0 0 5px;
            color: #d92378;
            font-size: 20px;
            font-weight: 700;
        }

        .shift-report-header p,
        .shift-report-header strong {
            display: block;
            margin: 0 0 6px;
            color: #5f6674;
            font-size: 11px;
        }

        .shift-report-header strong {
            color: #303642;
            font-weight: 500;
        }

        .shift-report-section {
            margin-top: 22px;
        }

        .shift-report-section h3 {
            margin: 0 0 8px;
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
            padding: 7px 8px;
            color: #fff;
            background: #d92378;
            border: 1px solid #e26ba1;
            font-weight: 600;
            white-space: nowrap;
        }

        .shift-summary-table td,
        .shift-detail-table td {
            padding: 7px 8px;
            border: 1px solid #dfe2e7;
            vertical-align: top;
        }

        .shift-detail-table {
            min-width: 1500px;
        }

        .shift-detail-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .shift-detail-table td small {
            display: block;
            margin-top: 3px;
            color: #78808e;
            font-size: 9px;
            line-height: 1.35;
        }

        .shift-detail-table tfoot td {
            background: #e7e9ed;
        }

        .shift-pnr {
            color: #d92378;
        }

        .shift-reference {
            font-family: monospace;
        }

        .amount-cell {
            font-weight: 600;
            white-space: nowrap;
        }

        .shift-negative {
            color: #c62828;
        }

        .shift-status {
            display: inline-flex;
            padding: 3px 6px;
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
            margin-top: 18px;
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
                padding: 26px 14px 20px;
            }
        }

        @media print {
            @page {
                size: landscape;
                margin: 8mm;
            }

            body {
                background: #fff !important;
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

            .shift-detail-scroll {
                overflow: visible !important;
            }

            .shift-detail-table {
                min-width: 0;
                font-size: 7px;
            }

            .shift-summary-table {
                font-size: 8px;
            }

            .shift-summary-table th,
            .shift-summary-table td,
            .shift-detail-table th,
            .shift-detail-table td {
                padding: 4px;
            }

            .shift-detail-table td small,
            .shift-status {
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
