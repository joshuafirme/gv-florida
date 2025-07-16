@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="container padding-top padding-bottom">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-md-6">
                <div class="profile__content__edit p-0">
                    <div class="p-4">
                        <form action="" method="post" class="register">
                            @csrf
                            <div class="row gy-3">
                                <div class="form-group col-12">
                                    <label class="form-label">@lang('Current Password')</label>
                                    <input type="password" class="form-control form--control" name="current_password" required autocomplete="current-password">
                                </div>
                                <div class="form-group col-12">
                                    <label class="form-label">@lang('Password')</label>
                                    <input type="password" class="form-control form--control @if (gs('secure_password')) secure-password @endif" name="password" required autocomplete="current-password">
                                </div>
                                <div class="form-group col-12">
                                    <label class="form-label">@lang('Confirm Password')</label>
                                    <input type="password" class="form-control form--control" name="password_confirmation" required autocomplete="current-password">
                                </div>
                                <div class="form-group col-12">
                                    <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@if (gs('secure_password'))
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif
