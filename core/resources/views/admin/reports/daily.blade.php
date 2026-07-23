@extends('admin.layouts.app')

@section('panel')
    <div class="daily-report">
        <header class="daily-report__header">
            <img src="{{ siteLogo() }}" alt="{{ gs('site_name') }}" class="daily-report__logo">
            <h2>Daily Collection Report</h2>
            <p>Generated: {{ now()->format('F j, Y h:i A') }}</p>
            <strong>{{ $date->format('l, F j, Y') }}</strong>
        </header>

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
                                @php($cashierSummary = $collection['summary'])
                                <tr>
                                    <td><strong>{{ $collection['cashier'] }}</strong></td>
                                    <td class="text-end">{{ $cashierSummary['tickets'] }}</td>
                                    <td class="text-end">{{ showAmount($cashierSummary['gross_sales']) }}</td>
                                    <td class="text-end {{ $cashierSummary['refunds'] > 0 ? 'daily-negative' : '' }}">
                                        {{ $cashierSummary['refunds'] > 0 ? '-' : '' }}{{ showAmount($cashierSummary['refunds']) }}
                                    </td>
                                    <td class="text-end {{ $cashierSummary['voids'] > 0 ? 'daily-negative' : '' }}">
                                        {{ $cashierSummary['voids'] > 0 ? '-' : '' }}{{ showAmount($cashierSummary['voids']) }}
                                    </td>
                                    <td class="text-end daily-total">
                                        {{ $cashierSummary['net_collection'] < 0 ? '-' : '' }}{{ showAmount(abs($cashierSummary['net_collection'])) }}
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

        <footer class="daily-report__footer">
            Net Revenue = Ticket Sales + Surcharges - Refunds - Voids. Rebooked and cancelled transactions are
            included in activity counts and have no cash impact.
        </footer>
    </div>
@endsection

@push('breadcrumb-plugins')
    <div class="daily-report-controls">
        <form action="{{ route('admin.report.daily') }}" method="GET" id="dailyReportDateForm">
            <i class="las la-calendar"></i>
            <input type="date" name="date" value="{{ $date->format('Y-m-d') }}"
                max="{{ now()->format('Y-m-d') }}" aria-label="Business date">
        </form>
        <button type="button" class="btn btn--primary" onclick="window.print()">
            <i class="las la-print"></i> Print Daily Report
        </button>
    </div>
@endpush

@push('style')
    <style>
        .daily-report-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .daily-report-controls form {
            position: relative;
        }

        .daily-report-controls form i {
            position: absolute;
            top: 50%;
            left: 12px;
            color: #d92378;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .daily-report-controls input {
            width: 165px;
            height: 38px;
            padding: 7px 10px 7px 35px;
            background: #fff;
            border: 1px solid #d9dce3;
            border-radius: 7px;
            font-size: 12px;
        }

        .daily-report {
            padding: 42px 28px 24px;
            background: #fff;
            border: 1px solid #e1e3e8;
            border-radius: 8px;
            color: #222936;
        }

        .daily-report__header {
            margin-bottom: 28px;
        }

        .daily-report__logo {
            display: block;
            width: auto;
            max-width: 240px;
            height: 38px;
            margin: 0 auto 42px;
            object-fit: contain;
        }

        .daily-report__header h2 {
            margin: 0 0 5px;
            color: #d92378;
            font-size: 21px;
            font-weight: 700;
        }

        .daily-report__header p,
        .daily-report__header strong {
            display: block;
            margin: 0 0 5px;
            color: #666e7c;
            font-size: 11px;
        }

        .daily-report__header strong {
            color: #303642;
            font-weight: 500;
        }

        .daily-report__section {
            min-width: 0;
            margin-top: 24px;
        }

        .daily-report__section h3 {
            margin: 0 0 8px;
            color: #303642;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .daily-report__columns {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(360px, .9fr);
            gap: 22px;
        }

        .daily-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .daily-table th {
            padding: 8px 10px;
            color: #fff;
            background: #d92378;
            border: 1px solid #e26ba1;
            font-weight: 600;
            white-space: nowrap;
        }

        .daily-table td {
            padding: 8px 10px;
            border: 1px solid #dfe2e7;
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

        .daily-report__footer {
            margin-top: 24px;
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
            .daily-report-controls {
                align-items: stretch;
                flex-direction: column;
                width: 100%;
            }

            .daily-report-controls input,
            .daily-report-controls .btn {
                width: 100%;
            }

            .daily-report {
                padding: 26px 14px 20px;
            }

            .daily-report__logo {
                margin-bottom: 28px;
            }
        }

        @media print {
            @page {
                size: landscape;
                margin: 9mm;
            }

            body {
                background: #fff !important;
            }

            .sidebar,
            .navbar-wrapper,
            .breadcrumb,
            .daily-report-controls {
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
                gap: 14px;
            }

            .daily-report__logo {
                height: 28px;
                margin-bottom: 25px;
            }

            .daily-report__section {
                margin-top: 16px;
            }

            .daily-table {
                font-size: 8px;
            }

            .daily-table th,
            .daily-table td {
                padding: 5px 6px;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';

            $('#dailyReportDateForm input[name="date"]').on('change', function() {
                $('#dailyReportDateForm').trigger('submit');
            });
        })(jQuery);
    </script>
@endpush
