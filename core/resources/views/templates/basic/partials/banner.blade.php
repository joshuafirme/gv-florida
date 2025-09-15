@php
    $contents = getContent('banner.content', true);
    $counters = App\Models\Counter::get();
@endphp
<!-- Banner Section Starts Here -->
<section class="banner-section"
    style="background: url({{ getImage('assets/images/frontend/banner/' . @$contents->data_values->background_image, '1500x88') }}) repeat-x bottom;">
    <div class="container">
        <div class="banner-wrapper">
            <div class="banner-content">
                <h1 class="title">{{ __(@$contents->data_values->heading) }}</h1>
                <a href="{{ __(@$contents->data_values->link) }}"
                    class="cmn--btn">{{ __(@$contents->data_values->link_title) }}</a>
            </div>
            <div class="ticket-form-wrapper">
                <div class="ticket-header nav-tabs nav border-0">
                    <h4 class="title">@lang('Choose Your Ticket')</h4>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="one-way">
                        <form action="{{ route('search') }}" class="ticket-form row g-3 justify-content-center m-0">
                            <div class="col-md-6">
                                <div class="form--group">
                                    <i class="las la-location-arrow"></i>
                                    <select class="form--control select2" name="pickup">
                                        <option value="">@lang('Pickup Point')</option>
                                        @foreach ($counters as $counter)
                                            <option value="{{ $counter->id }}"
                                                @if (request()->pickup == $counter->id) selected @endif>
                                                {{ __($counter->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form--group">
                                    <i class="las la-map-marker"></i>
                                    <select name="destination" class="form--control select2">
                                        <option value="">@lang('Dropping Point')</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form--group">
                                    <i class="las la-calendar-check"></i>
                                        <input type="text"  class="form--control date-range" name="date_of_journey"  placeholder="@lang('Departure Date')" autocomplete="off">
                                    </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form--group">
                                    <button class="w-100 btn btn--base">@lang('Find Tickets')</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="shape">
        <img src="{{ getImage('assets/images/frontend/banner/' . @$contents->data_values->animation_image, '200x69') }}"
            alt="bg">
    </div>
</section>


@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/global/css/daterangepicker.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/dropping-points.js') }}"></script>
@endpush


@push('script')
    <script>
        (function($) {
            "use strict"

            $('.select2').select2();
            const datePicker = $('.date-range').daterangepicker({
                autoUpdateInput: true,
                singleDatePicker: true,
                minDate:new Date()
            })

        })(jQuery)
    </script>
@endpush
