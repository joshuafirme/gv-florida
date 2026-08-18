@extends('admin.layouts.app')

@section('panel')
    <div class="all-ticket-filter">
        <form action="{{ route('admin.vehicle.ticket.list') }}" method="GET">
            <div class="all-ticket-filter__search">
                <i class="las la-search"></i>
                <input type="search" name="search" value="{{ $search }}"
                    placeholder="Search PNR, reference, passenger, seat, trip, or transaction">
            </div>

            <div class="all-ticket-filter__field">
                <label for="ticketTravelDate">Travel Date</label>
                <input id="ticketTravelDate" type="date" name="travel_date" value="{{ $travelDate }}">
            </div>

            <div class="all-ticket-filter__field">
                <label for="ticketRoute">Route</label>
                <select id="ticketRoute" name="route_id">
                    <option value="">All Routes</option>
                    @foreach ($routes as $route)
                        @php
                            $routeLabel = $route->name
                                ?: trim(($route->startFrom?->name ?: '') . ' - ' . ($route->endTo?->name ?: ''), ' -');
                        @endphp
                        <option value="{{ $route->id }}" @selected($routeId === $route->id)>
                            {{ $routeLabel ?: 'Route ' . $route->id }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="all-ticket-filter__field">
                <label for="ticketStatus">Status</label>
                <select id="ticketStatus" name="status">
                    <option value="">All Statuses</option>
                    @foreach (['booked', 'rebooked', 'refunded', 'cancelled', 'voided', 'pending', 'expired', 'rejected'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </div>

            <button class="btn btn--primary all-ticket-filter__submit" type="submit">
                <i class="las la-filter"></i> Filter
            </button>
            <a class="all-ticket-filter__clear" href="{{ route('admin.vehicle.ticket.list') }}">Clear</a>
        </form>
    </div>

    <div class="card all-ticket-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table--light style--two all-ticket-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>PNR</th>
                            <th>Reference No.</th>
                            <th>Journey</th>
                            <th>Trip</th>
                            <th>Seat No.</th>
                            <th>Fare</th>
                            <th>Passenger</th>
                            <th>Booking Source</th>
                            <th>Payment Method</th>
                            <th>Processed By</th>
                            <th>Authorized By</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            @php($statusClass = strtolower($ticket['status']))
                            <tr>
                                <td data-label="User">
                                    <strong>{{ $ticket['source'] }}</strong>
                                    @if ($ticket['device_id'])
                                        <small>{{ $ticket['device_id'] }}</small>
                                    @endif
                                </td>
                                <td data-label="PNR">
                                    <strong class="all-ticket-pnr">{{ $ticket['pnr'] }}</strong>
                                    @if ($ticket['transaction'])
                                        <small>{{ $ticket['transaction'] }}</small>
                                    @endif
                                </td>
                                <td data-label="Reference No."><strong>{{ $ticket['reference'] }}</strong></td>
                                <td data-label="Journey">
                                    <strong>{{ $ticket['journey_date'] }}</strong>
                                    <small>{{ $ticket['departure_time'] }}</small>
                                </td>
                                <td data-label="Trip">
                                    <strong>{{ $ticket['trip_class'] }}</strong>
                                    <small>{{ $ticket['route'] }}</small>
                                </td>
                                <td data-label="Seat No."><strong>{{ $ticket['seat'] }}</strong></td>
                                <td data-label="Fare"><strong>{{ showAmount($ticket['fare']) }}</strong></td>
                                <td data-label="Passenger">
                                    <strong>{{ $ticket['passenger_name'] }}</strong>
                                    <small>{{ $ticket['passenger_type'] }}</small>
                                    @if ($ticket['passenger_id'])
                                        <small>ID: {{ $ticket['passenger_id'] }}</small>
                                    @endif
                                </td>
                                <td data-label="Booking Source">{{ $ticket['source'] }}</td>
                                <td data-label="Payment Method">{{ $ticket['payment_method'] }}</td>
                                <td data-label="Processed By">{{ $ticket['processed_by'] }}</td>
                                <td data-label="Authorized By">{{ $ticket['authorized_by'] ?: '-' }}</td>
                                <td data-label="Status">
                                    <span class="all-ticket-status all-ticket-status--{{ $statusClass }}">
                                        {{ $ticket['status'] }}
                                    </span>
                                    @if ($ticket['reason'])
                                        <small title="{{ $ticket['reason'] }}">{{ $ticket['reason'] }}</small>
                                    @endif
                                </td>
                                <td data-label="Action">
                                    @if ($ticket['actions'])
                                        <div class="all-ticket-actions">
                                            @foreach ($ticket['actions'] as $action)
                                                <a href="{{ $action['url'] }}"
                                                    class="btn btn-sm {{ $action['class'] }}"
                                                    title="{{ $action['label'] }}"
                                                    data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                    @if (($action['target'] ?? null) === '_blank') target="_blank" rel="noopener" @endif>
                                                    <i class="{{ $action['icon'] }}"></i>
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center text-muted py-5">
                                    No tickets matched the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($tickets->hasPages())
            <div class="card-footer py-4">
                {{ paginateLinks($tickets) }}
            </div>
        @endif
    </div>
@endsection

@push('style')
    <style>
        .all-ticket-filter {
            margin-bottom: 16px;
        }

        .all-ticket-filter form {
            align-items: end;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(280px, 1.7fr) minmax(145px, .65fr) minmax(190px, .9fr) minmax(145px, .65fr) auto auto;
        }

        .all-ticket-filter__search {
            position: relative;
        }

        .all-ticket-filter__search i {
            color: #8a919e;
            font-size: 17px;
            left: 12px;
            pointer-events: none;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }

        .all-ticket-filter input,
        .all-ticket-filter select {
            background: #fff;
            border: 1px solid #d9dce2;
            border-radius: 6px;
            color: #353a45;
            height: 42px;
            width: 100%;
        }

        .all-ticket-filter input {
            padding: 0 12px;
        }

        .all-ticket-filter__search input {
            padding-left: 38px;
        }

        .all-ticket-filter select {
            padding: 0 32px 0 10px;
        }

        .all-ticket-filter__field label {
            color: #686f7c;
            display: block;
            font-size: 11px;
            font-weight: 600;
            margin: 0 0 4px;
        }

        .all-ticket-filter__submit {
            align-items: center;
            display: inline-flex;
            gap: 5px;
            height: 42px;
            justify-content: center;
        }

        .all-ticket-filter__clear {
            align-items: center;
            color: var(--primary-color, #df2a82);
            display: inline-flex;
            height: 42px;
            padding: 0 4px;
        }

        .all-ticket-card {
            border-radius: 7px;
            overflow: hidden;
        }

        .all-ticket-table {
            min-width: 1540px;
        }

        .all-ticket-table th,
        .all-ticket-table td {
            font-size: 11px;
            padding: 11px 10px;
            vertical-align: top;
        }

        .all-ticket-table td small {
            color: #7c8390;
            display: block;
            font-size: 9px;
            line-height: 1.3;
            margin-top: 2px;
        }

        .all-ticket-actions {
            align-items: center;
            display: flex;
            flex-wrap: nowrap;
            gap: 4px;
        }

        .all-ticket-actions .btn {
            align-items: center;
            display: inline-flex;
            flex: 0 0 30px;
            height: 30px;
            justify-content: center;
            margin: 0;
            padding: 0;
            width: 30px;
        }

        .all-ticket-pnr {
            color: var(--primary-color, #df2a82);
        }

        .all-ticket-status {
            border: 1px solid;
            border-radius: 12px;
            display: inline-flex;
            font-size: 9px;
            line-height: 1;
            padding: 5px 8px;
            white-space: nowrap;
        }

        .all-ticket-status--booked { background: #eaf8f0; border-color: #b9e4ca; color: #087947; }
        .all-ticket-status--rebooked { background: #eaf5fc; border-color: #bddff2; color: #12689e; }
        .all-ticket-status--refunded,
        .all-ticket-status--cancelled,
        .all-ticket-status--voided,
        .all-ticket-status--rejected { background: #fff0ef; border-color: #f0c2bf; color: #b42318; }
        .all-ticket-status--pending { background: #fff7e7; border-color: #efd59e; color: #996000; }
        .all-ticket-status--expired,
        .all-ticket-status--unknown { background: #f0f1f3; border-color: #d4d6da; color: #646a75; }

        @media (max-width: 1199px) {
            .all-ticket-filter form {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }

        @media (max-width: 767px) {
            .all-ticket-filter form {
                grid-template-columns: 1fr;
            }

            .all-ticket-filter__clear {
                justify-content: center;
            }
        }
    </style>
@endpush
