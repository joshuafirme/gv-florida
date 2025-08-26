@extends($activeTemplate . 'layouts.master')
@section('content')
    <!-- booking history Starts Here -->
    <section class="dashboard-section padding-top padding-bottom">
        <div class="container">
            <div class="dashboard-wrapper">
                
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
