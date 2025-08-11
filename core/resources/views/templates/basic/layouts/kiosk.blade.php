@extends($activeTemplate . 'layouts.app')
@section('app')

    @yield('content')

    <a href="javascript::void()" class="scrollToTop active"><i class="las la-chevron-up"></i></a>
@endsection

@push('script')
    <script>
        "use strict";

        $('.policy').on('click', function() {
            $.get('{{ route('cookie.accept') }}', function(response) {
                $('.cookies-card').addClass('d-none');
            });
        });

        setTimeout(function() {
            $('.cookies-card').removeClass('hide')
        }, 2000);
    </script>
@endpush
