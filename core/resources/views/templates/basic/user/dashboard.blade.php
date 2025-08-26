@extends($activeTemplate . 'layouts.master')
@section('content')
    <!-- booking history Starts Here -->
    <section class="dashboard-section padding-top padding-bottom">
        <div class="container">
            <div class="dashboard-wrapper">
                <div class="row pb-60 gy-4 justify-content-center">
                    <div class="col-lg-4 col-md-6 col-sm-10">
                        <div class="dashboard-widget">
                            <div class="dashboard-widget__content">
                                <p>@lang('Total Booked Ticket')</p>
                                <h3 class="title">{{ __($widget['booked']) }}</h3>
                            </div>
                            <div class="dashboard-widget__icon">
                                <i class="las la-ticket-alt"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-10">
                        <div class="dashboard-widget">
                            <div class="dashboard-widget__content">
                                <p>@lang('Total Rejected Ticket')</p>
                                <h3 class="title">{{ __($widget['rejected']) }}</h3>
                            </div>
                            <div class="dashboard-widget__icon">
                                <i class="las la-ticket-alt"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-10">
                        <div class="dashboard-widget">
                            <div class="dashboard-widget__content">
                                <p>@lang('Total Pending Ticket')</p>
                                <h3 class="title">{{ __($widget['pending']) }}</h3>
                            </div>
                            <div class="dashboard-widget__icon">
                                <i class="las la-ticket-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
                @include('templates.basic.user.partials.book_logs')
                @if ($bookedTickets->hasPages())
                    <div class="custom-pagination">
                        {{ paginateLinks($bookedTickets) }}
                    </div>
                @endif
            </div>
        </div>
    </section>
    <!-- booking history end Here -->
    
    @include('templates.basic.modals.book_info')
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
    @include('templates.basic.scripts.booking_history')
@endpush
