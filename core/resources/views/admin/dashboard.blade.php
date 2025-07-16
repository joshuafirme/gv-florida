@extends('admin.layouts.app')

@section('panel')
    <div class="row gy-4">
        <div class="col-xxl-3 col-sm-6">
            <x-widget style="6" link="{{ route('admin.users.all') }}" icon="las la-users" title="Total Users" value="{{ $widget['total_users'] }}" bg="primary" />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget style="6" link="{{ route('admin.users.active') }}" icon="las la-user-check" title="Active Users" value="{{ $widget['verified_users'] }}" bg="success" />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget style="6" link="{{ route('admin.users.email.unverified') }}" icon="lar la-envelope" title="Email Unverified Users" value="{{ $widget['email_unverified_users'] }}" bg="danger" />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget style="6" link="{{ route('admin.users.mobile.unverified') }}" icon="las la-comment-slash" title="Mobile Unverified Users" value="{{ $widget['mobile_unverified_users'] }}" bg="warning" />
        </div>
    </div>

    <div class="row gy-4 mt-1">
        <div class="col-xxl-3 col-sm-6">
            <x-widget style="6" link="{{ route('admin.deposit.successful') }}" title="Successful Payment" icon="fas fa-wallet" value="{{ showAmount($deposit['total_deposit_amount']) }}" bg="success" outline="true" />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget style="6" link="{{ route('admin.deposit.pending') }}" title="Pending Payment" icon="fas fa-spinner" value="{{ showAmount($deposit['total_deposit_pending']) }}" bg="warning" outline="true" />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget style="6" link="{{ route('admin.deposit.rejected') }}" title="Rejected Payment" icon="fas fa-exclamation-triangle" value="{{ showAmount($deposit['total_deposit_rejected']) }}" bg="danger" outline="true" />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget style="6" link="{{ route('admin.deposit.list') }}" title="Total Charge" icon="fa fa-dollar-sign" value="{{ showAmount($deposit['total_deposit_charge']) }}" bg="primary" outline="true" />
        </div>
    </div>

    <div class="row gy-4 mt-1">
        <div class="col-xxl-3 col-sm-6">
            <x-widget style="2" overlay_icon="0" cover_cursor="1" link="{{ route('admin.counter.index') }}" title="Total Counter" icon="fas fa-map-marker-alt" value="{{ $widget['total_counter'] }}" color="success" />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget style="2" overlay_icon="0" cover_cursor="1" link="{{ route('admin.fleet.vehicles.index') }}" title="Total AC Vehicles" icon="fas fa-wind" value="{{ $widget['vehicle_with_ac'] }}" color="info" />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget style="2" overlay_icon="0" cover_cursor="1" link="{{ route('admin.fleet.vehicles.index') }}" title="Total Non AC Vehicles" icon="fas fa-times-circle" value="{{ $widget['vehicle_without_ac'] }}" color="warning" />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget style="2" overlay_icon="0" cover_cursor="1" link="{{ route('admin.fleet.vehicles.index') }}" title="Total Vehicles" icon="fas fa-bus" value="{{ $widget['total_vehicle'] }}" color="primary" />
        </div>
    </div>

    <div class="row gy-4 mt-1">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">@lang('Latest Booking History')</h5>

                    @if ($soldTickets->count())
                        <div class="table-responsive--sm table-responsive">
                            <table class="table table--light style--two">
                                <thead>
                                    <tr>
                                        <th>@lang('User')</th>
                                        <th>@lang('PNR Number')</th>
                                        <th>@lang('Ticket Count')</th>
                                        <th>@lang('Amount')</th>
                                        <th>@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($soldTickets as $item)
                                        <tr>
                                            <td data-label="@lang('User')">
                                                <span class="font-weight-bold">{{ __($item->user->fullname) }}</span>
                                                <br>
                                                <span class="small">
                                                    <a href="{{ route('admin.users.detail', $item->user_id) }}"><span>@</span>{{ $item->user->username }}</a>
                                                </span>
                                            </td>
                                            <td data-label="@lang('PNR Number')">
                                                <strong>{{ __($item->pnr_number) }}</strong>
                                            </td>
                                            <td data-label="@lang('Ticket Count')">
                                                <strong>{{ __(sizeof($item->seats)) }}</strong>
                                            </td>
                                            <td data-label="@lang('Amount')">
                                                {{ showAmount($item->sub_total) }}
                                            </td>
                                            <td data-label="@lang('Action')">
                                                <a href="{{ route('admin.vehicle.ticket.booked') }}" class="icon-btn ml-1 " data-toggle="tooltip" title="" data-original-title="@lang('Detail')">
                                                    <i class="la la-desktop"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table><!-- table end -->
                        </div>
                    @else
                        <div class="empty-list text-center h-100">
                            <img src="{{ getImage('assets/images/empty_list.png') }}" alt="empty">
                            <h5 class="text-muted">@lang('No booking records available')</h5>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between">
                        <h5 class="card-title">@lang('Payment History')</h5>

                        <div id="paymentDatePicker" class="border p-1 cursor-pointer rounded">
                            <i class="la la-calendar"></i>&nbsp;
                            <span></span> <i class="la la-caret-down"></i>
                        </div>
                    </div>
                    <div id="paymentChartArea"> </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gy-4 mt-1">
        <div class="col-xl-4 col-lg-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <h5 class="card-title">@lang('Login By Browser') (@lang('Last 30 days'))</h5>
                    <canvas id="userBrowserChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">@lang('Login By OS') (@lang('Last 30 days'))</h5>
                    <canvas id="userOsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">@lang('Login By Country') (@lang('Last 30 days'))</h5>
                    <canvas id="userCountryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-lib')
    <script src="{{ asset('assets/admin/js/vendor/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/vendor/chart.js.2.8.0.js') }}"></script>
    <script src="{{ asset('assets/global/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/charts.js') }}"></script>
@endpush

@push('style-lib')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/global/css/daterangepicker.css') }}">
@endpush

@push('script')
    <script>
        "use strict";

        const start = moment().subtract(14, 'days');
        const end = moment();

        const dateRangeOptions = {
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 15 Days': [moment().subtract(14, 'days'), moment()],
                'Last 30 Days': [moment().subtract(30, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'months').startOf('month'), moment().endOf('month')],
                'This Year': [moment().startOf('year'), moment().endOf('year')],
            },
            maxDate: moment()
        }

        const changeDatePickerText = (element, startDate, endDate) => {
            $(element).html(startDate.format('MMMM D, YYYY') + ' - ' + endDate.format('MMMM D, YYYY'));
        }

        let paymentLineChart = lineChart(
            document.querySelector("#paymentChartArea"),
            [{
                name: 'Payment',
                data: []
            }]
        );

        const paymentChart = (startDate, endDate) => {
            const data = {
                start_date: startDate.format('YYYY-MM-DD'),
                end_date: endDate.format('YYYY-MM-DD')
            }

            const url = @json(route('admin.payment.chart'));

            $.get(url, data,
                function(data, status) {
                    if (status == 'success') {
                        paymentLineChart.updateSeries(data.data);
                        paymentLineChart.updateOptions({
                            xaxis: {
                                categories: data.created_on,
                            }
                        });
                    }
                }
            );
        }

        $('#paymentDatePicker').daterangepicker(dateRangeOptions, (start, end) => changeDatePickerText('#paymentDatePicker span', start, end));
        changeDatePickerText('#paymentDatePicker span', start, end);
        paymentChart(start, end);

        $('#paymentDatePicker').on('apply.daterangepicker', (event, picker) => paymentChart(picker.startDate, picker.endDate));

        piChart(
            document.getElementById('userBrowserChart'),
            @json(@$chart['user_browser_counter']->keys()),
            @json(@$chart['user_browser_counter']->flatten())
        );

        piChart(
            document.getElementById('userOsChart'),
            @json(@$chart['user_os_counter']->keys()),
            @json(@$chart['user_os_counter']->flatten())
        );

        piChart(
            document.getElementById('userCountryChart'),
            @json(@$chart['user_country_counter']->keys()),
            @json(@$chart['user_country_counter']->flatten())
        );
    </script>
@endpush
@push('style')
    <style>
        .apexcharts-menu {
            min-width: 120px !important;
        }

        .empty-list {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .empty-list img {
            width: 150px;
            margin-bottom: 15px;
        }

        #paymentChartArea {
            height: 413px;
        }
    </style>
@endpush
