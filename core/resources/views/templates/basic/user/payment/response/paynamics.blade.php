@if (auth()->check())
    @extends($activeTemplate . 'layouts.frontend')
@else
    @extends($activeTemplate . 'layouts.master')
@endif

<style>
    .success-card {
        max-width: 600px;
        margin: 60px auto;
        padding: 30px;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        text-align: center;
    }

    .success-icon {
        font-size: 4rem;
        color: #28a745;
    }

    .transaction-details {
        text-align: left;
        margin-top: 20px;
    }

    .transaction-details dt {
        font-weight: 600;
    }
</style>

@section('content')
    <div class="container padding-top padding-bottom">
        <div class="success-card">
            @php
                $color_class = 'warning';
                if ($transaction->response_code == 'GR001') {
                    $color_class = 'success';
                }
            @endphp
            <i class="fas fa-check fa-2xl text-{{ $color_class }}"></i>
            <h3 class="mt-3 text-{{ $color_class }}">{{ $transaction->response_message }}</h3>
            {{-- <p class="text-muted">Your payment has been processed successfully.</p> --}}
            <div class="alert alert-{{ $color_class }} mt-3" role="alert">
                {{ $transaction->response_advise }}
            </div>

            <dl class="row transaction-details">
                <dt class="col-sm-4">Request ID:</dt>
                <dd class="col-sm-8">{{ $transaction->request_id }}</dd>

                <dt class="col-sm-4">Response ID:</dt>
                <dd class="col-sm-8">{{ $transaction->response_id }}</dd>

                <dt class="col-sm-4">Payment Channel:</dt>
                <dd class="col-sm-8">{{ $transaction->pchannel }}</dd>

                <dt class="col-sm-4">Timestamp:</dt>
                <dd class="col-sm-8">{{ $transaction->timestamp }}</dd>
            </dl>

            <a href="{{ url('/') }}" class="btn btn--base mt-3">Return to Home</a>
        </div>

    </div>
@endsection
