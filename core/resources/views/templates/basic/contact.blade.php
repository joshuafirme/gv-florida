@extends($activeTemplate.'layouts.frontend')
@section('content')

@php
    $content = getContent('contact.content',true);
@endphp

<section class="contact-section padding-top padding-bottom overflow-hidden">
    <div class="container">
        <div class="text-center">
            <h3 class="title mb-2">{{ __(@$content->data_values->title) }}</h3>
            <p class="mb-5">{{ __(@$content->data_values->short_details) }}</p>
        </div>
        <div class="row pb-80 gy-4 justify-content-center">
            <div class="col-sm-6 col-lg-4">
                <div class="info-item">
                    <div class="icon">
                        <i class="flaticon-location"></i>
                    </div>
                    <div class="content">
                        <h5 class="title">@lang('Our Address')</h5>
                        @lang('Address') : {{ __(@$content->data_values->address )}}
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="info-item active">
                    <div class="icon">
                        <i class="flaticon-call"></i>
                    </div>
                    <div class="content">
                        <h5 class="title">@lang('Call Us')</h5>
                        <a href="tel:{{ @$content->data_values->contact_number }}">{{ __(@$content->data_values->contact_number) }}</a>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="info-item">
                    <div class="icon">
                        <i class="flaticon-envelope"></i>
                    </div>
                    <div class="content">
                        <h5 class="title">@lang('Email Us')</h5>
                        <a href="mailto:{{ @$content->data_values->email }}">{{ __(@$content->data_values->email) }}</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row gy-5">
            <div class="col-lg-6">
                <div class="contact-form-wrapper">
                    <h4 class="title mb-4">{{ @$content->data_values->form_title }}</h4>
                    <form class="contact-form row gy-3" method="post">
                        @csrf
                        <div class=" col-xl-6 col-lg-12 col-md-6">
                            <div class="form--group">
                                <label for="name">@lang('Name') <span>*</span></label>
                                <input id="name" name="name" type="text" class="form--control" placeholder="@lang('Name')" value="{{ auth()->user() ? auth()->user()->fullname : old('name') }}" @if(auth()->user()) readonly @endif required>
                            </div>
                        </div>
                        <div class=" col-xl-6 col-lg-12 col-md-6">
                            <div class="form--group">
                                <label for="email">@lang('Email') <span>*</span></label>
                                <input id="email" name="email" type="email" class="form--control" placeholder="@lang('Email')" value="{{ auth()->user() ? auth()->user()->email : old('email') }}" @if(auth()->user()) readonly @endif required>
                            </div>
                        </div>
                        <div class=" col-xl-12">
                            <div class="form--group">
                                <label for="subject">@lang('Subject') <span>*</span></label>
                                <input id="subject" name="subject" type="text" class="form--control" placeholder="@lang('Subject')" value="{{ old('subject') }}" required>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form--group">
                                <label for="msg">@lang('Your Message') <span>*</span></label>
                                <textarea id="msg" name="message" class="form--control" placeholder="@lang('Message')">{{old('message')}}</textarea>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form--group">
                                <button class="contact-button" type="submit">@lang('Send Us Message')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="map-wrapper">
                    <iframe class="map" style="border:0;" src="https://maps.google.com/maps?q={{ @$content->data_values->latitude }},{{ @$content->data_values->longitude }}&hl=es;z=14&amp;output=embed"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>



@endsection
