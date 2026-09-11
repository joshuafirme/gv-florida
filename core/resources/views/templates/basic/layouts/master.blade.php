@extends($activeTemplate . 'layouts.app')
@section('app')
    @include($activeTemplate . 'partials.user_header')

    @if (!request()->routeIs([
        'home',
        'ticket',
        'search',
        'user.deposit.index',
    ]))
        @include($activeTemplate . 'partials.breadcrumb')
    @endif

    @yield('content')

    @include($activeTemplate . 'partials.footer')
    <a href="javascript::void()" class="scrollToTop active"><i class="las la-chevron-up"></i></a>
@endsection
