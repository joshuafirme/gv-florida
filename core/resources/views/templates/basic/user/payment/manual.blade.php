@section('content')
    @php
        $kiosk_id = request()->kiosk_id;
    @endphp
    @if ($kiosk_id)
        @php
            $layout = 'layouts.kiosk';
        @endphp
        @include('templates.basic.partials.kiosk_nav')
    @endif
    @extends($activeTemplate . $layout)
@section('content')
    <div class="container padding-top padding-bottom">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card custom--card">
                    <div class="card-header card-header-bg">
                        <h5 class="card-title">{{ __($pageTitle) }}</h5>
                    </div>
                    <div class="card-body  ">
                        <form action="{{ route('user.deposit.manual.update') }}" method="POST" class="disableSubmission"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-primary">
                                        <p class="mb-0"><i class="las la-info-circle"></i> @lang('You are about to pay')
                                            <b>{{ showAmount($data['final_amount']) }}</b>
                                            @lang('Please proceed with the exact amount to complete your transaction.')
                                        </p>
                                    </div>

                                    <div class="mb-3">@php echo  $data->gateway->description @endphp</div>
                                </div>

                                <div class="col-12">
                                    <x-viser-form identifier="id" identifierValue="{{ $gateway->form_id }}" />
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn--base w-100">@lang('Proceed')</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
