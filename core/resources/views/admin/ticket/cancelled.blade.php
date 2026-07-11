@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-12">
            <div class="mb-3">
                <h4 class="mb-1">Cancelled Ticket</h4>
                <span class="text-muted">{{ $cancellations->total() }} {{ \Illuminate\Support\Str::plural('record', $cancellations->total()) }}</span>
            </div>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>Source</th>
                                    <th>PNR</th>
                                    <th>Ref. No.</th>
                                    <th>Seat</th>
                                    <th>Journey Date</th>
                                    <th>Trip</th>
                                    <th>Fare</th>
                                    <th>Passenger</th>
                                    <th>Payment</th>
                                    <th>Processed By</th>
                                    <th>Rebooked</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($cancellations as $cancellation)
                                    @php
                                        $ticket = $cancellation->bookedTicket;
                                        $payment = '-';
                                        if ($ticket?->deposit && $ticket->deposit->pchannel) {
                                            $payment = readPaymentChannel($ticket->deposit->pchannel);
                                        } elseif ($ticket?->deposit) {
                                            $payment = $ticket->deposit->gatewayCurrency()->name;
                                        }
                                    @endphp
                                    <tr>
                                        <td data-label="Source">
                                            {{ $ticket->kiosk_id ? ($ticket->kiosk?->name ?: 'Kiosk') : 'Online' }}
                                            <div class="text-muted">{{ $payment }}</div>
                                        </td>
                                        <td data-label="PNR">
                                            <span class="text--primary">{{ $ticket->pnr_number }}</span>
                                            <div><span class="badge badge--danger">Cancelled</span></div>
                                        </td>
                                        <td data-label="Ref. No.">{{ $cancellation->slip_series_number_id }}</td>
                                        <td data-label="Seat"><strong>{{ $cancellation->slipSeriesNumber->seat }}</strong></td>
                                        <td data-label="Journey Date">
                                            {{ showDateTime($ticket->date_of_journey, 'M d, Y') }}
                                            <div>{{ date('h:i A', strtotime($ticket->trip->schedule->start_from)) }}</div>
                                        </td>
                                        <td data-label="Trip">
                                            <strong>{{ $ticket->trip->fleetType->name }}</strong>
                                            <div class="text-muted">{{ $ticket->pickup->name }} via {{ $ticket->drop->name }}</div>
                                        </td>
                                        <td data-label="Fare"><strong>{{ showAmount($cancellation->original_fare) }}</strong></td>
                                        <td data-label="Passenger">
                                            {{ $ticket->deposit?->userDiscount?->passenger_name ?: ($ticket->user?->fullname ?: 'Guest') }}
                                            <div class="text-muted">{{ getPassengerType($ticket->deposit) }}</div>
                                        </td>
                                        <td data-label="Payment">{{ $payment }}</td>
                                        <td data-label="Processed By">
                                            <div>{{ $cancellation->processedBy->name }}</div>
                                            <small class="text-muted">Cancelled by {{ $cancellation->authorizedBy->name }}</small>
                                        </td>
                                        <td data-label="Rebooked">
                                            @if ($ticket->is_rebooked)
                                                <span class="badge badge--info">Yes</span>
                                            @else
                                                <span class="badge badge--dark">No</span>
                                            @endif
                                        </td>
                                        <td data-label="Action">
                                            <a href="{{ route('admin.vehicle.ticket.cancel.acknowledgment', $cancellation->id) }}"
                                                target="_blank" class="btn btn-sm btn-outline--primary" title="Cancellation acknowledgment">
                                                <i class="las la-eye"></i>
                                            </a>
                                        </td>
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
                @if ($cancellations->hasPages())
                    <div class="card-footer py-4">{{ paginateLinks($cancellations) }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <form action="{{ route('admin.vehicle.ticket.cancelled') }}" method="GET" class="form-inline float-sm-right bg--white">
        <div class="input-group">
            <input type="text" name="search" class="form-control" value="{{ $search }}"
                placeholder="Search PNR, passenger, or ref. no.">
            <button class="btn btn--primary" type="submit"><i class="fa fa-search"></i></button>
        </div>
    </form>
@endpush
