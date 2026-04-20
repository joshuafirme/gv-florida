@extends('admin.layouts.app')
@php
    use Carbon\Carbon;
@endphp
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
                                    <th>@lang('Is Rebooked')</th>
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
                                            <div>{{ date('h:i A', strtotime($item->trip->schedule->start_from)) }}</div>
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
                                            @if ($item->seats && is_array($item->seats))
                                                <div>{{ implode(', ', $item->seats) }}</div>
                                            @endif
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
                                            @if ($item->is_rebooked)
                                                <span class="badge badge--info">@lang('Yes')</span>
                                            @else
                                                <span class="badge badge--secondary">@lang('No')</span>
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

                                                @if (Carbon::parse($item->date_of_journey)->greaterThan(now()) && !$item->is_rebooked)
                                                    <button data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                        title="Change Schedule" target="_blank"
                                                        class="btn btn-sm btn-outline--primary ms-1 update-booking-date-btn"
                                                        data-id="{{ $item->id }}"
                                                        data-date-of-journey="{{ $item->date_of_journey }}">
                                                        <i class="fa-solid fa-calendar-day"></i>
                                                    </button>
                                                @endif
                                                @if (Carbon::parse($item->date_of_journey)->isFuture())
                                                    <button type="button" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom" title="Cancel Booking"
                                                        class="btn btn-sm btn-outline--danger confirmationBtn"
                                                        data-question="@lang('Are you sure you want to cancel this booking?')"
                                                        data-action="{{ route('admin.trip.ticket.cancel.booking', $item->id) }}"><i
                                                            class="fa-solid fa-circle-xmark"></i>
                                                    </button>
                                                @endif
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

    <div id="updateBookingDateModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Booking Date</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="updateBookingDateForm" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="date_of_journey">New Date of Journey</label>
                            <input type="date" class="form-control" name="date_of_journey"
                                required>
                        </div>
                        <div class="form-group mt-2">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" name="username"
                                required>
                        </div>
                        <div class="form-group mt-2">
                            <label for="passcode">Passcode</label>
                            <input type="password" class="form-control" name="passcode"
                                required>
                        </div>

                        <button type="submit" class="btn btn--primary">Update Date</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <x-confirmation-modal />
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

@push('script')
    <script>
        'use strict';

        $(document).on("click", ".update-booking-date-btn", function(e) {
            e.preventDefault();

            let id = $(this).data('id');
            let dateOfJourney = $(this).data('date-of-journey') || '';

            // Build URL properly (pass base URL from Blade)
            let actionUrl = "{{ url('admin/manage/update-booking-date') }}";
            actionUrl = `${actionUrl}/${id}`;

            $("#updateBookingDateModal").modal('show');
            $("#updateBookingDateForm").attr('action', actionUrl);
            $("#updateBookingDateForm input[name='date_of_journey']").val(dateOfJourney);
        });
    </script>
@endpush
