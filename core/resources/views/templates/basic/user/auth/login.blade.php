@extends($activeTemplate . 'layouts.authenticate')
@section('content')
    @include($activeTemplate . 'partials.social_login')

    <form method="POST" class="account-form row" action="{{ route('user.login') }}" onsubmit="return submitUserForm();">
        @csrf
        <div class="col-lg-12 form-group form--group">
            <label for="username">@lang('Username')</label>
            <input id="username" name="username" type="text" class="form--control" placeholder="@lang('Enter Your username')" required>
        </div>
        <div class="col-lg-12 form-group form--group">
            <label for="password">@lang('Password')</label>
            <input id="password" type="password" name="password" class="form--control" placeholder="@lang('Enter Your Password')" required>
        </div>
        <div class="col-lg-12">
            <x-captcha />
        </div>

        <div class="col-lg-12 d-flex justify-content-between">
            <div class="form-group form--group custom--checkbox">
                <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember">@lang('Remember Me')</label>
            </div>
            <div class="">
                <a href="{{ route('user.password.request') }}">@lang('Forgot Password?')</a>
            </div>
        </div>
        <div class="col-md-12 form-group form--group">
            <button class="account-button w-100" type="submit">@lang('Sign In')</button>
        </div>
        <div class="col-md-12">
            <div class="account-page-link">
                <p>@lang('Don\'t have any Account?') <a href="{{ route('user.register') }}">@lang('Sign Up')</a></p>
            </div>
        </div>
    </form>
@endsection

@push('script')
    <script>
        "use strict";

        function submitUserForm() {
            var response = grecaptcha.getResponse();
            if (response.length == 0) {
                document.getElementById('g-recaptcha-error').innerHTML =
                    '<span class="text-danger">@lang('Captcha field is required.')</span>';
                return false;
            }
            return true;
        }
    </script>
@endpush
