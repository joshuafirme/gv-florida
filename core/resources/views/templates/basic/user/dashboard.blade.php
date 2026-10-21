@extends($activeTemplate . 'layouts.master')
@section('content')
    <section class="dashboard-section ticket-account-section padding-top padding-bottom">
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

    @include('templates.basic.modals.book_info')
@endsection

@push('script')
    @include('templates.basic.scripts.booking_history')
@endpush
