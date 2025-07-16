@extends($activeTemplate . 'layouts.app')
@section('app')
    @stack('fbComment')

    @include($activeTemplate . 'partials.header')
    @if (!request()->routeIs('home') && !request()->routeIs('ticket') && !request()->routeIs('search'))
        @include($activeTemplate . 'partials.breadcrumb')
    @endif

    @yield('content')

    @include($activeTemplate . 'partials.footer')

    @php
        $cookie = App\Models\Frontend::where('data_keys', 'cookie.data')->first();
    @endphp
    @if ($cookie->data_values->status == 1 && !\Cookie::get('gdpr_cookie'))
        <div id="cookiePolicy" class="cookies-card bg--default radius--10px text-center">
            <div class="cookies-card__icon">
                <i class="las la-cookie-bite"></i>
            </div>
            <p class="mt-4 cookies-card__content">
                {{ __($cookie->data_values->short_desc) }}
                <a href="{{ route('cookie.policy') }}">@lang('learn more')</a>
            </p>
            <div class="cookies-card__btn mt-4">
                <a href="javascript:void(0)" class="btn policy btn--base w-100">@lang('Allow')</a>
            </div>
        </div>
    @endif

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
