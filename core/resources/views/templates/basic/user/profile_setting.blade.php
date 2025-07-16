@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="padding-top padding-bottom">
        <div class="container">
            <div class="profile__edit__wrapper">
                <div class="profile__edit__form">
                    <form class="register prevent-double-click" action="" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="row justify-content-center">
                            <div class="col-xl-10">
                                <div class="profile__content__edit p-0">
                                    <div class="row gy-3 p-4">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">@lang('First Name')</label>
                                                <input type="text" class="form-control form--control radius-0" name="firstname" placeholder="@lang('First Name')" value="{{ $user->firstname }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">@lang('Last Name')</label>
                                                <input type="text" class="form-control form--control radius-0" id="lastname" name="lastname" placeholder="@lang('Last Name')" value="{{ $user->lastname }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">@lang('E-mail Address')</label>
                                                <input class="form-control form--control radius-0" value="{{ $user->email }}" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">@lang('Mobile Number')</label>
                                                <input class="form-control form--control radius-0" value="{{ $user->mobile }}" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">@lang('Address')</label>
                                                <input type="text" class="form-control form--control radius-0" name="address" placeholder="@lang('Address')" value="{{ @$user->address }}" required="">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">@lang('State')</label>
                                                <input type="text" class="form-control form--control radius-0" name="state" placeholder="@lang('state')" value="{{ @$user->state }}" required="">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">@lang('Zip Code')</label>
                                                <input type="text" class="form-control form--control radius-0" name="zip" placeholder="@lang('Zip Code')" value="{{ @$user->zip }}" required="">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">@lang('City')</label>
                                                <input type="text" class="form-control form--control radius-0" name="city" placeholder="@lang('City')" value="{{ @$user->city }}" required="">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">@lang('Zip Code')</label>
                                                <input type="text" class="form-control form--control radius-0" name="zip" placeholder="@lang('Zip Code')" value="{{ @$user->zip }}" required="">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">@lang('Country')</label>
                                                <input class="form-control form--control radius-0" value="{{ @$user->country_name }}" disabled>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <button type="submit" class="btn btn--base btn--block h-auto">@lang('Update Profile')</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
