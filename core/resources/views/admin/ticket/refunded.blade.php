@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>Ticket No.</th>
                                    <th>PNR</th>
                                    <th>Passenger</th>
                                    <th>Trip</th>
                                    <th>Travel Date</th>
                                    <th>Fare / Refund</th>
                                    <th>Reason</th>
                                    <th>Processed / Authorized By</th>
                                    <th>Refunded At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($refunds as $refund)
                                    @php $ticket = $refund->bookedTicket; @endphp
                                    <tr>
                                        <td data-label="Ticket No.">
                                            <strong>{{ $refund->slip_series_number_id }}</strong>
                                            <div><span class="badge badge--danger">Refunded</span></div>
                                        </td>
                                        <td data-label="PNR">{{ $ticket->pnr_number }}</td>
                                        <td data-label="Passenger">
                                            {{ $ticket->deposit?->userDiscount?->passenger_name ?: ($ticket->user?->fullname ?: 'Guest') }}
                                            <div class="text-muted">Seat {{ formatSeatLabel($refund->slipSeriesNumber->seat) }}</div>
                                        </td>
                                        <td data-label="Trip">
                                            {{ $ticket->pickup->name }}
                                            <i class="las la-long-arrow-alt-right mx-1"></i>
                                            {{ $ticket->drop->name }}
                                            <div class="text-muted">{{ $ticket->trip->fleetType->name }}</div>
                                        </td>
                                        <td data-label="Travel Date">
                                            {{ showDateTime($ticket->date_of_journey, 'd M, Y') }}
                                            <div>{{ date('h:i A', strtotime($ticket->trip->schedule->start_from)) }}</div>
                                        </td>
                                        <td data-label="Fare / Refund">
                                            <div>Fare: {{ showAmount($refund->original_fare) }}</div>
                                            <strong class="text--danger">Refund: {{ showAmount($refund->refund_amount) }}</strong>
                                        </td>
                                        <td data-label="Reason">
                                            <strong>{{ $refund->reason }}</strong>
                                            <div class="text-muted">{{ $refund->remarks }}</div>
                                        </td>
                                        <td data-label="Processed / Authorized By">
                                            <div>{{ $refund->processedBy->name }}</div>
                                            <small class="text-muted">Authorized: {{ $refund->authorizedBy->name }}</small>
                                        </td>
                                        <td data-label="Refunded At">{{ showDateTime($refund->created_at) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center text-muted">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($refunds->hasPages())
                    <div class="card-footer py-4">{{ paginateLinks($refunds) }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <form action="{{ route('admin.vehicle.ticket.refunded') }}" method="GET" class="form-inline float-sm-right bg--white">
        <div class="input-group">
            <input type="text" name="search" class="form-control" value="{{ $search }}"
                placeholder="PNR, ticket no., passenger, or reason">
            <button class="btn btn--primary" type="submit"><i class="fa fa-search"></i></button>
        </div>
    </form>
@endpush
