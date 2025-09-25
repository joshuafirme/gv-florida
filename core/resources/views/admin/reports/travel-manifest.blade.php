@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-12 mb-3">
            <div class="bus-search-header">
                <form action="{{ route('admin.report.travelManifest') }}"
                    class="ticket-form ticket-form-two row g-3 justify-content-center">
                    <div class="col-md-4 col-lg-3">
                        <div class="form--group">
                            <label for="">Pickup Point</label>
                            <select name="pickup" class="form--control select2">
                                <option value="">@lang('Pickup Point')</option>
                                @foreach ($counters as $counter)
                                    <option value="{{ $counter->id }}" @if (request()->pickup == $counter->id) selected @endif>
                                        {{ __($counter->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form--group">
                            <label for="">Dropping Point</label>
                            <select name="destination" class="form--control select2">
                                <option value="">@lang('Dropping Point')</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form--group">
                            <label for="">Date from</label>
                            <input name="date" type="search"
                                class="datepicker-here form-control bg--white pe-2 date-range"
                                placeholder="@lang('Start Date - End Date')" autocomplete="off" value="{{ request()->date }}">
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form--group">
                            <button class="btn btn--primary input-group-text">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('PNR number')</th>
                                    <th>@lang('Trip')</th>
                                    <th>@lang('Bus type')</th>
                                    <th>@lang('Route')</th>
                                    <th>@lang('Passenger')</th>
                                    <th>@lang('Seat No.')</th>
                                    <th>@lang('Booking date')</th>
                                    <th>@lang('Payment channel')</th>
                                    <th>@lang('Status')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data as $item)
                                    <tr>
                                        <td>{{ __($item->pnr_number) }}</td>
                                        <td>{{ __($item->trip->title) }}</td>
                                        <td>{{ __($item->trip->fleetType->name) }}</td>
                                        <td>{{ __($item->pickup->name) }} -> {{ __($item->drop->name) }}</td>
                                        <td>{{ __(@$item->user->firstname) }} {{ __(@$item->user->lastname) }}</td>
                                        <td>{{ __(implode(',', $item->seats)) }}</td>
                                        <td>
                                            {{ __(showDateTime($item->date_of_journey, 'd M, Y')) }}</td>
                                        <td>
                                            @if (@$item->deposit->gateway->name == 'Paynamics')
                                                <div>{{ __(getPaynamicsPChannel(@$item->deposit->pchannel, true)) }}</div>
                                            @else
                                                {{ __(@$item->deposit->gateway->name) }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->status == 1)
                                                <span class="badge badge--success"> @lang('Booked')</span>
                                            @elseif($item->status == 2)
                                                <span class="badge badge--warning"> @lang('Pending')</span>
                                            @else
                                                <span class="badge badge--danger"> @lang('Rejected')</span>
                                            @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                @if ($data->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($data) }}
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>
@endsection

@push('script-lib')
    <script src="{{ asset('assets/global/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/daterangepicker.min.js') }}"></script>
@endpush

@push('style-lib')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/global/css/daterangepicker.css') }}">
@endpush

@push('script')
    <script src="{{ asset('assets/global/js/dropping-points.js') }}"></script>
    <script>
        (function($) {
            "use strict"

            const datePicker = $('.date-range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                },
                showDropdowns: true,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 15 Days': [moment().subtract(14, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(30, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month')
                        .endOf('month')
                    ],
                    'Last 6 Months': [moment().subtract(6, 'months').startOf('month'), moment().endOf('month')],
                    'This Year': [moment().startOf('year'), moment().endOf('year')],
                },
                maxDate: moment()
            });
            const changeDatePickerText = (event, startDate, endDate) => {
                $(event.target).val(startDate.format('MMMM DD, YYYY') + ' - ' + endDate.format('MMMM DD, YYYY'));
            }


            $('.date-range').on('apply.daterangepicker', (event, picker) => changeDatePickerText(event, picker
                .startDate, picker.endDate));


            if ($('.date-range').val()) {
                let dateRange = $('.date-range').val().split(' - ');
                $('.date-range').data('daterangepicker').setStartDate(new Date(dateRange[0]));
                $('.date-range').data('daterangepicker').setEndDate(new Date(dateRange[1]));
            }

            setTimeout(() => {
                const queryString = window.location.search;
                const urlParams = new URLSearchParams(queryString);

                const destination = urlParams.get('destination');

                $('select[name=destination]').val(destination)
                $('select[name=destination]').trigger('change');
            }, 1500);

        })(jQuery)
    </script>
@endpush
