@extends($activeTemplate . 'layouts.authenticate')
@section('content')
    <div class="verification-code-wrapper">
        <div class="verification-area">
            <form action="{{ route('user.password.verify.code') }}" method="POST" class="submit-form">
                @csrf
                <p class="verification-text">@lang('A 6 digit verification code sent to your email address') : {{ showEmailAddress($email) }}</p>
                <input type="hidden" name="email" value="{{ $email }}">
                @include($activeTemplate . 'partials.verification_code')
                <div class="form-group">
                    <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                </div>
                <div class="form-group">
                    @lang('Please check including your Junk/Spam Folder. if not found, you can')
                    <a href="{{ route('user.password.request') }}">@lang('Try to send again')</a>
                </div>
            </form>
        </div>
    </div>
@endsection
