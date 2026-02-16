@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('User')</th>
                                    <th>@lang('PNR Number')</th>
                                    <th>@lang('Journey Date')</th>
                                    <th>@lang('Trip')</th>
                                    <th>@lang('Fare')</th>
                                    <th>@lang('Booking Source')</th>
                                    <th>@lang('Payment Method')</th>
                                    <th>@lang('Processed By')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $item)
                                    <tr>
                                        <td data-label="@lang('User')">
                                            @if ($item->kiosk)
                                                {{ $item->kiosk->name }}
                                                <div>{{ $item->kiosk->uid }}</div>
                                            @elseif($item->user_id)
                                                <span class="font-weight-bold">{{ __(@$item->user->fullname) }}</span>
                                                <br>
                                                <span class="small"> <a
                                                        href="{{ route('admin.users.detail', $item->user_id) }}"><span>@</span>{{ __(@$item->user->username) }}</a>
                                                </span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('PNR Number')">
                                            <div><span class="text-muted">{{ __($item->pnr_number) }}</span></div>
                                            @if ($item->status == 1)
                                                <span
                                                    class="badge badge--success font-weight-normal text--samll">@lang('Booked')</span>
                                            @elseif($item->status == 2)
                                                <span
                                                    class="badge badge--warning font-weight-normal text--samll">@lang('Pending')</span>
                                            @else
                                                <span
                                                    class="badge badge--danger font-weight-normal text--samll">@lang('Rejected')</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Journey Date')">
                                            {{ __(showDateTime($item->date_of_journey, 'd M, Y')) }}
                                        </td>
                                        <td data-label="@lang('Trip')">
                                            <span class="font-weight-bold">{{ __($item->trip->fleetType->name) }}</span>
                                            <br>
                                            <span class="font-weight-bold"> {{ __($item->trip->startFrom->name) }} -
                                                {{ __($item->trip->endTo->name) }}</span>
                                        </td>
                                        <td data-label="@lang('Fare')">
                                            {{ __(showAmount($item->sub_total)) }}
                                            <div>Ticket Count: {{ $item->seats ? __(sizeof($item->seats)) : '' }}</div>
                                        </td>
                                        <td>{{ $item->kiosk_id ? $item->kiosk->name : 'Online' }}</td>
                                        <td>
                                            @if ($item->deposit && $item->deposit->pchannel)
                                                {{ readPaymentChannel($item->deposit->pchannel) }}
                                            @elseif($item->deposit)
                                                {{ $item->deposit->gatewayCurrency()->name }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->approved_by)
                                                {{ $item->approvedBy->name }}
                                            @elseif ($item->kiosk_id)
                                                {{ $item->kiosk->name }}
                                            @elseif ($item->deposit && $item->deposit->pchannel)
                                                Paynamics
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->status == Status::BOOKED_APPROVED)
                                                <a data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                    title="Reservation slip" target="_blank"
                                                    href="{{ route('admin.trip.reservationSlip', $item->id) }}"
                                                    class="btn btn-sm btn-outline--primary ms-1">
                                                    <i class="fa-solid fa-receipt"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
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
        </div>
    </div>
@endsection
@push('breadcrumb-plugins')
    <form
        action="{{ route('admin.vehicle.ticket.search', $scope ?? str_replace('admin.vehicle.ticket.', '', request()->route()->getName())) }}"
        method="GET" class="form-inline float-sm-right bg--white">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="@lang('Search PNR Number')"
                value="{{ $search ?? '' }}">
            <button class="btn btn--primary" type="submit"><i class="fa fa-search"></i></button>
        </div>
    </form>
@endpush
