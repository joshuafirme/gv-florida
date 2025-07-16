@extends($activeTemplate . 'layouts.authenticate')
@section('content')
    <div class="mb-4">
        <p>@lang('Your account is verified successfully. Now you can change your password. Please enter a strong password and don\'t share it with anyone.')</p>
    </div>
    <form method="POST" action="{{ route('user.password.update') }}">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="row gy-3">
            <div class="form-group col-xl-12">
                <label class="form-label">@lang('Password')</label>
                <input type="password" class="form-control form--control @if (gs('secure_password')) secure-password @endif" name="password" required>
            </div>
            <div class="form-group col-xl-12">
                <label class="form-label">@lang('Confirm Password')</label>
                <input type="password" class="form-control form--control" name="password_confirmation" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn--base w-100"> @lang('Submit')</button>
            </div>
        </div>
    </form>
@endsection
@if (gs('secure_password'))
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif
