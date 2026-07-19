@extends('admin.layouts.app')

@section('panel')
    <div class="audit-report">
        <header class="audit-report__header">
            <p>Generated: {{ now()->format('F j, Y h:i A') }}</p>
            <strong>{{ number_format($transactions->total()) }} {{ $transactions->total() === 1 ? 'event' : 'events' }} &middot; payment and booking activity with staff attribution</strong>
        </header>

        <section class="audit-filter" aria-label="Audit trail filters">
            <form action="{{ route('admin.report.audit.trail') }}" method="GET">
                <div class="audit-filter__search">
                    <i class="las la-search" aria-hidden="true"></i>
                    <input type="search" name="search" value="{{ $search }}"
                        placeholder="Search PNR, reference, passenger, staff, seat, or details"
                        aria-label="Search audit trail">
                </div>

                <select name="event" class="form-control" aria-label="Filter by event">
                    <option value="">All Events</option>
                    @foreach ($events as $event)
                        <option value="{{ $event }}" @selected(request('event') === $event)>{{ $event }}</option>
                    @endforeach
                </select>

                <select name="admin_id" class="form-control" aria-label="Filter by staff">
                    <option value="">All Staff</option>
                    @foreach ($admins as $admin)
                        <option value="{{ $admin->id }}" @selected((string) request('admin_id') === (string) $admin->id)>
                            {{ $admin->name }} ({{ '@' . $admin->username }})
                        </option>
                    @endforeach
                </select>

                <select name="source" class="form-control" aria-label="Filter by source">
                    <option value="">All Sources</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source }}" @selected(request('source') === $source)>{{ $source }}</option>
                    @endforeach
                </select>

                <div class="audit-filter__date">
                    <label for="auditDateFrom">From</label>
                    <input id="auditDateFrom" type="date" name="date_from" class="form-control"
                        value="{{ request('date_from') }}" max="{{ now()->format('Y-m-d') }}">
                </div>

                <div class="audit-filter__date">
                    <label for="auditDateTo">To</label>
                    <input id="auditDateTo" type="date" name="date_to" class="form-control"
                        value="{{ request('date_to') }}" max="{{ now()->format('Y-m-d') }}">
                </div>

                <button class="btn btn--primary audit-filter__apply" type="submit">
                    <i class="las la-filter" aria-hidden="true"></i> Filter
                </button>
                <a href="{{ route('admin.report.audit.trail') }}" class="btn btn--light audit-filter__clear">Clear</a>
            </form>
        </section>

        <section class="audit-events">
            <div class="audit-events__heading">
                <h3>Events</h3>
                <span>Newest activity first</span>
            </div>

            <div class="table-responsive">
                <table class="table table--light style--two audit-table">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Event</th>
                            <th>PNR</th>
                            <th>Ref. No.</th>
                            <th>Passenger</th>
                            <th class="text-end">Amount</th>
                            <th>Performed By</th>
                            <th>Details</th>
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
                                if (in_array($transaction->status, ['Rebooked', 'Cancelled'], true) && abs($amount) < 0.01) {
                                    $amount = max((float) $transaction->base_fare - (float) $transaction->discount_amount, 0);
                                }
                            @endphp
                            <tr>
                                <td data-label="Date & Time">
                                    <strong>{{ $transaction->processed_at?->format('M j, Y') ?: '-' }}</strong>
                                    <small>{{ $transaction->processed_at?->format('h:i A') ?: '-' }}</small>
                                </td>
                                <td data-label="Event">
                                    <span class="audit-status audit-status--{{ $statusClass }}">{{ $transaction->status }}</span>
                                </td>
                                <td data-label="PNR"><strong class="audit-pnr">{{ $transaction->pnr ?: '-' }}</strong></td>
                                <td data-label="Ref. No."><span class="audit-reference">{{ $transaction->reference_no ?: '-' }}</span></td>
                                <td data-label="Passenger">
                                    <strong>{{ $transaction->passenger_name ?: 'Guest' }}</strong>
                                    <small>
                                        {{ $transaction->passenger_type ?: 'Regular' }}
                                        @if ($transaction->passenger_id)
                                            &middot; ID {{ $transaction->passenger_id }}
                                        @endif
                                    </small>
                                </td>
                                <td data-label="Amount" class="text-end audit-amount {{ $amount < 0 ? 'audit-amount--negative' : '' }}">
                                    {{ $amount < 0 ? '-' : '' }}{{ showAmount(abs($amount)) }}
                                </td>
                                <td data-label="Performed By">
                                    <strong>{{ $transaction->admin?->name ?: 'Unknown staff' }}</strong>
                                    @if ($transaction->admin?->username)
                                        <small>{{ '@' . $transaction->admin->username }}</small>
                                    @endif
                                </td>
                                <td data-label="Details" class="audit-details">
                                    <strong>{{ $transaction->source ?: '-' }} &middot; {{ $transaction->payment_method ?: '-' }}</strong>
                                    @if ($transaction->trip_class || $transaction->trip_route)
                                        <small>
                                            {{ $transaction->trip_class ?: '-' }}
                                            @if ($transaction->trip_route)
                                                &middot; {{ $transaction->trip_route }}
                                            @endif
                                        </small>
                                    @endif
                                    <small>
                                        @if ($transaction->journey_date)
                                            {{ $transaction->journey_date->format('M j, Y') }}
                                            @if ($transaction->departure_time)
                                                {{ date('h:i A', strtotime($transaction->departure_time)) }}
                                            @endif
                                            &middot;
                                        @endif
                                        Seat {{ $transaction->seat_no ?: '-' }}
                                    </small>
                                    @if ($transaction->reason)
                                        <small class="audit-details__reason">{{ $transaction->reason }}</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="las la-clipboard-list audit-empty-icon" aria-hidden="true"></i>
                                    <span>No audit events match the selected filters.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transactions->hasPages())
                <div class="audit-events__pagination">
                    {{ paginateLinks($transactions) }}
                </div>
            @endif
        </section>
    </div>
@endsection

@push('style')
    <style>
        .audit-report {
            color: #252b37;
        }

        .audit-report__header {
            margin-bottom: 20px;
        }

        .audit-report__header h2 {
            color: #d92378;
            font-size: 28px;
            font-weight: 800;
            margin: 0 0 4px;
        }

        .audit-report__header p,
        .audit-report__header strong {
            color: #687080;
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin: 0 0 4px;
        }

        .audit-report__header strong {
            color: #384152;
        }

        .audit-filter {
            background: #fff;
            border: 1px solid #e1e4e9;
            border-radius: 8px;
            margin-bottom: 22px;
            padding: 14px;
        }

        .audit-filter form {
            align-items: end;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(260px, 2fr) repeat(3, minmax(135px, 1fr)) repeat(2, minmax(135px, .8fr)) auto auto;
        }

        .audit-filter__search {
            position: relative;
        }

        .audit-filter__search i {
            color: #8a93a3;
            font-size: 18px;
            left: 12px;
            pointer-events: none;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }

        .audit-filter__search input {
            border: 1px solid #d8dce3;
            border-radius: 7px;
            height: 42px;
            padding: 8px 12px 8px 38px;
            width: 100%;
        }

        .audit-filter .form-control {
            border-color: #d8dce3;
            border-radius: 7px;
            height: 42px;
            min-width: 0;
        }

        .audit-filter__date label {
            color: #697181;
            display: block;
            font-size: 11px;
            font-weight: 700;
            margin: 0 0 4px;
            text-transform: uppercase;
        }

        .audit-filter__apply,
        .audit-filter__clear {
            align-items: center;
            display: inline-flex;
            height: 42px;
            justify-content: center;
            white-space: nowrap;
        }

        .audit-events {
            background: #fff;
            border: 1px solid #e1e4e9;
            border-radius: 8px;
            overflow: hidden;
        }

        .audit-events__heading {
            align-items: center;
            display: flex;
            justify-content: space-between;
            padding: 15px 17px;
        }

        .audit-events__heading h3 {
            font-size: 14px;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
        }

        .audit-events__heading span {
            color: #7b8493;
            font-size: 12px;
        }

        .audit-table {
            margin-bottom: 0;
            min-width: 1180px;
        }

        .audit-table th,
        .audit-table td {
            padding: 11px 12px;
            vertical-align: top;
        }

        .audit-table td {
            color: #424a58;
            font-size: 12px;
        }

        .audit-table td > strong,
        .audit-table td > small {
            display: block;
        }

        .audit-table td > small {
            color: #7b8493;
            margin-top: 3px;
        }

        .audit-pnr {
            color: #d92378;
        }

        .audit-reference {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .audit-status {
            border: 1px solid transparent;
            border-radius: 999px;
            display: inline-flex;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            padding: 5px 8px;
        }

        .audit-status--sold {
            background: #ecfdf3;
            border-color: #bbf7d0;
            color: #16794a;
        }

        .audit-status--rebooked {
            background: #eff8ff;
            border-color: #b9e0f7;
            color: #116b91;
        }

        .audit-status--cancelled,
        .audit-status--refunded {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #b45309;
        }

        .audit-status--voided {
            background: #faf5ff;
            border-color: #e9d5ff;
            color: #7e22ce;
        }

        .audit-status--neutral {
            background: #f3f4f6;
            border-color: #d1d5db;
            color: #4b5563;
        }

        .audit-amount {
            color: #1f2937 !important;
            font-weight: 800;
            white-space: nowrap;
        }

        .audit-amount--negative {
            color: #c2413b !important;
        }

        .audit-details {
            min-width: 230px;
        }

        .audit-details__reason {
            color: #9a5a14 !important;
        }

        .audit-empty-icon,
        .audit-table .text-muted span {
            display: block;
        }

        .audit-empty-icon {
            font-size: 30px;
            margin-bottom: 6px;
        }

        .audit-events__pagination {
            border-top: 1px solid #e5e7eb;
            padding: 14px 16px;
        }

        @media (max-width: 1399px) {
            .audit-filter form {
                grid-template-columns: minmax(240px, 2fr) repeat(3, minmax(130px, 1fr));
            }
        }

        @media (max-width: 767px) {
            .audit-report__header h2 {
                font-size: 23px;
            }

            .audit-filter form {
                grid-template-columns: 1fr;
            }

            .audit-events__heading {
                align-items: flex-start;
                flex-direction: column;
                gap: 4px;
            }
        }
    </style>
@endpush
