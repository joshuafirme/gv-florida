@extends($activeTemplate . 'layouts.master')
@section('content')
    <!-- booking history Starts Here -->
    <section class="dashboard-section padding-top padding-bottom">
        <div class="container">
            <div class="dashboard-wrapper">
                <div class="booking-table-wrapper">
                    <table class="booking-table">
                        <thead>
                            <tr>
                                <th>@lang('PNR Number')</th>
                                <th>@lang('AC / Non-Ac')</th>
                                <th>@lang('Starting Point')</th>
                                <th>@lang('Dropping Point')</th>
                                <th>@lang('Journey Date')</th>
                                <th>@lang('Pickup Time')</th>
                                <th>@lang('Booked Seats')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Fare')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookedTickets as $item)
                                <tr>
                                    <td class="ticket-no" data-label="@lang('PNR Number')">{{ __($item->pnr_number) }}</td>
                                    <td data-label="@lang('AC / Non-Ac')">{{ $item->trip->fleetType->has_ac ? 'AC' : 'Non-Ac' }}</td>
                                    <td class="pickup" data-label="@lang('Starting Point')">{{ __($item->pickup->name) }}</td>
                                    <td class="drop" data-label="@lang('Dropping Point')">{{ __($item->drop->name) }}</td>
                                    <td class="date" data-label="@lang('Journey Date')">{{ __(showDateTime($item->date_of_journey, 'd M, Y')) }}</td>
                                    <td class="time" data-label="@lang('Pickup Time')">{{ __(showDateTime($item->trip->schedule->start_from, 'H:i a')) }}</td>
                                    <td class="seats" data-label="@lang('Booked Seats')">{{ __(implode(',', $item->seats)) }}</td>
                                    <td data-label="@lang('Status')">
                                        @if ($item->status == 1)
                                            <span class="badge badge--success"> @lang('Booked')</span>
                                        @elseif($item->status == 2)
                                            <span class="badge badge--warning"> @lang('Pending')</span>
                                        @else
                                            <span class="badge badge--danger"> @lang('Rejected')</span>
                                        @endif
                                    </td>
                                    <td class="fare" data-label="@lang('Fare')">{{ __(showAmount($item->sub_total)) }}</td>
                                    <td class="action" data-label="@lang('Action')">
                                        <div class="action-button-wrapper">
                                            @if ($item->date_of_journey >= \Carbon\Carbon::today()->format('Y-m-d') && $item->status == 1)
                                                <a href="{{ route('user.ticket.print', $item->id) }}" target="_blank" class="print"><i class="las la-print"></i></a>
                                            @else
                                                <a href="javascript::void(0)" class="checkinfo" data-info="{{ $item }}" data-bs-toggle="modal" data-bs-target="#infoModal"><i class="las la-info-circle"></i></a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center" colspan="100%">{{ $emptyMessage }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($bookedTickets->hasPages())
                    <div class="custom-pagination">
                        {{ paginateLinks($bookedTickets) }}
                    </div>
                @endif
            </div>
        </div>
    </section>
    <!-- booking history end Here -->

    <div class="modal fade" id="infoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Ticket Booking History')</h5>
                    <button type="button" class="w-auto btn--close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
                </div>
                <div class="modal-body p-4">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--danger w-auto btn--sm px-3" data-bs-dismiss="modal">
                        @lang('Close')
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('style')
    <style>
        .modal-body p:not(:last-child) {
            border-bottom: 1px dashed #ebebeb;
            padding: 5px 0;
        }
    </style>
@endpush

@push('script')
    <script>
        "use strict"

        $('.checkinfo').on('click', function() {
            var info = $(this).data('info');
            var modal = $('#infoModal');
            var html = '';
            html += `
                    <p class="d-flex flex-wrap justify-content-between pt-0"><strong>@lang('Journey Date')</strong>  <span>${info.date_of_journey}</span></p>
                    <p class="d-flex flex-wrap justify-content-between"><strong>@lang('PNR Number')</strong>  <span>${info.pnr_number}</span></p>
                    <p class="d-flex flex-wrap justify-content-between"><strong>@lang('Route')</strong>  <span>${info.trip.start_from.name} @lang('to') ${info.trip.end_to.name}</span></p>
                    <p class="d-flex flex-wrap justify-content-between"><strong>@lang('Fare')</strong>  <span>${parseInt(info.sub_total).toFixed(2)} {{ __(gs('cur_text')) }}</span></p>
                    <p class="d-flex flex-wrap justify-content-between"><strong>@lang('Status')</strong>  <span>${info.status == 1 ? '<span class="badge badge--success">@lang('Successful')</span>' : info.status == 2 ? '<span class="badge badge--warning">@lang('Pending')</span>' : '<span class="badge badge--danger">@lang('Rejected')</span>'}</span></p>
                `;
            modal.find('.modal-body').html(html);
        })
    </script>
@endpush
