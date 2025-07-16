@extends($activeTemplate . 'layouts.authenticate')

@section('content')
    <form method="POST" action="{{ route('user.password.email') }}" class="verify-gcaptcha">
        @csrf

        <div class="row gy-3">
            <div class="col-lg-12 form-group">
                <label class="form-label">@lang('Email or Username')</label>
                <input type="text" class="form--control" name="value" value="{{ old('value') }}" required autofocus="off">
            </div>
            <div class="col-lg-12">
                <x-captcha />
            </div>

            <div class="form-group">
                <button class="account-button" type="submit">@lang('Send Password Code')</button>
            </div>

        </div>
    </form>
@endsection
@push('script')
    <script>
        (function($) {
            "use strict";

            myVal();
            $('select[name=type]').on('change', function() {
                myVal();
            });

            function myVal() {
                $('.my_value').text($('select[name=type] :selected').text());
            }
        })(jQuery)
    </script>
@endpush
