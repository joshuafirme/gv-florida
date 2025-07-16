@extends($activeTemplate.'layouts.master')
@section('content')
<div class="container padding-top padding-bottom">
    <div class="row  justify-content-center">
        <div class="col-md-8">
            <div class="card cmn--card">
                <div class="card-body p-3 p-sm-4">
                    <div class="deposit-preview">
                        <div class="deposit-thumb">
                            <img src="{{ $data->gatewayCurrency()->methodImage() }}" alt="@lang('Image')">
                        </div>
                        <div class="deposit-content">
                            <ul>
                                <li>
                                    @lang('Amount:') <span class="text--success">{{ showAmount($data->amount) }}</span>
                                </li>
                                <li>
                                    @lang('Charge:') <span class="text--danger">{{ showAmount($data->charge) }}</span>
                                </li>
                                <li>
                                    @lang('Payable:') <span class="text--warning">{{ showAmount($data->amount + $data->charge) }}</span>
                                </li>
                                <li>
                                    @lang('Conversion Rate:') <span class="text--info">1
                                        {{ __(gs('cur_text')) }}
                                        @lang('=') {{ showAmount($data->rate,currencyFormat:false) }}
                                        {{ __($data->baseCurrency()) }}</span>
                                </li>
                                <li>
                                    @lang('In') {{ $data->baseCurrency() }}: <span class="text--primary">{{ showAmount($data->final_amount,currencyFormat:false) }}</span>
                                </li>
                            </ul>
                            @if (1000 > $data->method_code)
                            <a href="{{ route('user.deposit.confirm') }}" class="btn btn--base btn--md w-100 radius-5 mt-3 ">@lang('pay now')</a>
                            @else
                            <a href="{{ route('user.deposit.manual.confirm') }}" class="btn btn--base btn--md w-100 radius-5 mt-3 ">@lang('Pay
                                Now')</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
