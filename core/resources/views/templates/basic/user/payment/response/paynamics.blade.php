@section('content')
    @php
        $kiosk_id = request()->kiosk_id;
    @endphp
    @if ($kiosk_id)
        @include('templates.basic.partials.kiosk_nav')
    @endif

    @extends($activeTemplate . $layout)
    <div class="container padding-bottom">

        @if ($ticket->kiosk_id)

            @include('templates.basic.user.partials.ticket-wrapper')

            @push('style-lib')
                <link rel="stylesheet" href="{{ asset('assets/global/css/reservartion-slip.css') }}">
            @endpush

            <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>

            @include('templates.basic.user.partials.scripts.qz-print-slip')
        @else
            <div class="success-card">
                @php
                    $color_class = 'warning';
                    if ($transaction->response_code == 'GR001') {
                        $color_class = 'success';
                    }
                @endphp
                {{-- <i class="fas fa-check fa-2xl text-{{ $color_class }}"></i> --}}
                <h3 class="mt-3 mb-2 text-{{ $color_class }}">{{ $transaction->response_message }}</h3>
                {{-- <p class="text-muted">Your payment has been processed successfully.</p> --}}
                {{-- <div class="alert alert-{{ $color_class }} mt-3" role="alert">
                {{ $transaction->response_advise }}
            </div> --}}

                <dl class="row transaction-details">
                    <dt class="col-sm-4">Request ID:</dt>
                    <dd class="col-sm-8">{{ $transaction->request_id }}</dd>
                    @if (isset($transaction->direct_otc_info))
                        @foreach ($transaction->direct_otc_info as $direct_otc_info)
                            <dt class="col-sm-4">Reference number:</dt>
                            <dd class="col-sm-8">{{ $transaction->pay_reference }}</dd>
                            <dt class="col-sm-4">Payment Channel:</dt>
                            <dd class="col-sm-8">{!! nl2br($direct_otc_info->pay_instructions) !!}</dd>
                        @endforeach
                    @else
                        <dt class="col-sm-4">Payment Channel:</dt>
                        <dd class="col-sm-8">{{ $transaction->pchannel }}</dd>
                    @endif

                    <dt class="col-sm-4">Timestamp:</dt>
                    <dd class="col-sm-8">{{ $transaction->timestamp }}</dd>
                </dl>

                <a href="{{ url('/user/dashboard') }}" class="btn btn--base mt-3">Return to Dashboard</a>
            </div>
        @endif

    </div>
@endsection
