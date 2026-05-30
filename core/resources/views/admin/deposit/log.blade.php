@extends('admin.layouts.app')
@section('panel')
    @php
        $search = request('search');
        $date = request('date');
        $status = isset($status) ? $status : 'all';
        $method_code = request('method_code') ?: 'all';
    @endphp
    <div class="row justify-content-center">
        @if (request()->routeIs('admin.deposit.list') || request()->routeIs('admin.deposit.method'))
            <div class="col-12">
                @include('admin.deposit.widget')
            </div>
        @endif

        <div class="col-md-12 mb-3">
            <form action="#">
                <div class="d-flex flex-wrap gap-4">
                    <div style="width: 250px;">
                        <label for="">Date</label>
                        <input name="date" type="search" class="datepicker-here form-control bg--white pe-2 date-range"
                            placeholder="@lang('Start Date - End Date')" autocomplete="off" value="{{ request()->date }}">
                    </div>
                    <div style="width: 250px;">
                        <label for="">Payment Method</label>
                        @php
                            $gateways = App\Models\GatewayCurrency::get();
                        @endphp
                        <select name="method_code" class="select2" required>
                            <option value="all">@lang('All')</option>
                            @foreach ($gateways as $gateway)
                                <option value="{{ $gateway->method_code }}"
                                    {{ $gateway->method_code == $method_code ? 'selected' : '' }}>{{ $gateway->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="align-self-end">
                        <button class="btn btn--primary w-100 h-45"><i class="fas fa-filter"></i> Filter</button>
                    </div>
                    <div class="align-self-end">
                        <a class="btn btn--success w-100 h-45"
                            href="{{ url("/admin/deposit/export?status=$status&date=$date&search=$search&method_code=$method_code") }}"><i
                                class="fa-solid fa-file-export"></i> Export</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Gateway | Transaction')</th>
                                    <th>@lang('Initiated')</th>
                                    <th>@lang('PNR')</th>
                                    <th>Reference #</th>
                                    <th>@lang('User')</th>
                                    <th>Trip</th>
                                    <th>Seats</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Passenger type')</th>
                                    <th>@lang('Status')</th>
                                    @if ($status == 'approved')
                                        <th>@lang('Processed By')</th>
                                    @endif
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deposits as $deposit)
                                    @php
                                        $details = $deposit->detail ? json_encode($deposit->detail) : null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-bold">
                                                <a
                                                    href="{{ appendQuery('method', $deposit->method_code < 5000 ? @$deposit->gateway->alias : $deposit->method_code) }}">
                                                    @if ($deposit->method_code < 5000)
                                                        @if (@$deposit->gateway->name == 'Paynamics')
                                                            {{ getPaynamicsPChannel($deposit->pchannel, true) }}
                                                        @else
                                                            {{ __(@$deposit->gateway->name) }}
                                                        @endif
                                                    @else
                                                        @lang('Google Pay')
                                                    @endif
                                                </a>
                                            </span>
                                            <br>
                                            <small> {{ $deposit->trx }} </small>
                                        </td>

                                        <td>
                                            {{ showDateTime($deposit->created_at) }}<br>{{ diffForHumans($deposit->created_at) }}
                                        </td>
                                        <td>{{ $deposit->bookedTicket->pnr_number }}</td>
                                        <td>{{ implodeSeriesNo($deposit) }}</td>
                                        <td>
                                            @if ($deposit->user)
                                                <span class="fw-bold">{{ $deposit->user->fullname }}</span>
                                                <br>
                                                <span class="small">
                                                    <a
                                                        href="{{ appendQuery('search', @$deposit->user->username) }}"><span>@</span>{{ $deposit->user->username }}</a>
                                                </span>
                                            @else
                                                {{ $deposit->bookedTicket->kiosk->name }}
                                                <div>{{ $deposit->bookedTicket->kiosk->uid }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $deposit->bookedTicket?->trip?->route?->name }}</td>
                                        <td>{{ $deposit->bookedTicket->seats ? implode(',', $deposit->bookedTicket->seats) : '' }}
                                        </td>
                                        <td>
                                            {{ showAmount($deposit->final_amount) }}
                                        </td>
                                        <td>{{ getPassengerType($deposit) }}</td>
                                        <td>
                                            @php echo $deposit->statusBadge @endphp
                                        </td>
                                        @if ($status == 'approved')
                                            <td>
                                                {{ $deposit->processedBy ? $deposit->processedBy->name : '-' }}
                                            </td>
                                        @endif
                                        <td>
                                            <a href="{{ route('admin.deposit.details', $deposit->id) }}"
                                                class="btn btn-sm btn-outline--primary ms-1">
                                                <i class="la la-desktop"></i> @lang('Details')
                                            </a>
                                        </td>
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
                @if ($deposits->hasPages())
                    <div class="card-footer py-4">
                        @php echo paginateLinks($deposits) @endphp
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder='PNR / Username / TRX' />
@endpush
@push('style-lib')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/global/css/daterangepicker.css') }}">
@endpush

@push('script')
    <script src="{{ asset('assets/global/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/daterangepicker.min.js') }}"></script>
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

        })(jQuery)
    </script>
@endpush
