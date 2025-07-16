@php
    $testContent = getContent('testimonials.content', true);
    $testElements = getContent('testimonials.element', false);
@endphp

<!-- Section Starts Here -->
<section class="padding-bottom padding-top testimonial-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="section-header text-center">
                    <h2 class="title">{{ __(@$testContent->data_values->heading) }}</h2>
                    <p>{{ __(@$testContent->data_values->sub_heading) }}</p>
                </div>
            </div>
        </div>
        <div class="row justify-content-center gy-5">
            <div class="col-lg-8 col-md-10">
                <div class="testimonial-wrapper">
                    <div class="testimonial-slider">
                        @foreach ($testElements as $item)
                            <div class="single-slider">
                                <div class="testimonial-item">
                                    <div class="content">
                                        <p>{{ __(@$item->data_values->description) }}</p>
                                    </div>
                                    <div class="thumb-wrapper">
                                        <div class="thumb">
                                            <img src="{{ frontendImage('testimonials', @$item->data_values->image) }}"
                                                alt="testimonials">
                                        </div>
                                        <h5 class="name">{{ __(@$item->data_values->person) }}</h5>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


@if (!app()->offsetExists('slick_script'))
    @push('script-lib')
        @push('script-lib')
            <script src="{{ asset($activeTemplateTrue . 'js/slick.min.js') }}"></script>
        @endpush
    @endpush
    @php app()->offsetSet('slick_script',true) @endphp
@endif

@if (!app()->offsetExists('slick_css'))
    @push('style-lib')
        <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/slick.css') }}">
    @endpush
    @php app()->offsetSet('slick_css',true) @endphp
@endif


@push('script')
    <script>
        $(function() {
            'use strict'

            $(".testimonial-slider").slick({
                fade: false,
                slidesToShow: 1,
                slidesToScroll: 1,
                infinite: true,
                autoplay: false,
                pauseOnHover: true,
                centerMode: true,
                dots: true,
                arrows: false,
                nextArrow: '<i class="las la-arrow-right arrow-right"></i>',
                prevArrow: '<i class="las la-arrow-left arrow-left"></i> ',
            });
        });
    </script>
@endpush
