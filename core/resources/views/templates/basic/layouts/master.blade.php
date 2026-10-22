@extends($activeTemplate . 'layouts.app')
@section('app')
    @include($activeTemplate . 'partials.user_header')

    @if (!request()->routeIs([
        'home',
        'ticket',
        'search',
        'user.home',
        'user.ticket.history',
        'user.deposit.index',
        'user.notifications.index',
    ]))
        @include($activeTemplate . 'partials.breadcrumb')
    @endif

    @yield('content')

    @unless (request()->routeIs([
        'ticket',
        'ticket.seats',
        'user.deposit.index',
        'user.deposit.confirm',
        'user.deposit.done',
        'user.deposit.manual.confirm',
        'paynamics.response',
        'paynamics.cancel',
    ]))
        @include($activeTemplate . 'partials.footer')
    @endunless
    <a href="javascript::void()" class="scrollToTop active"><i class="las la-chevron-up"></i></a>
@endsection
