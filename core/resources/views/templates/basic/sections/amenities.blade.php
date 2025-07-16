@php
    $amenitiesContent = getContent('amenities.content', true);
    $facilities = getContent('amenities.element', false, null, true);
@endphp
<!-- Our Ameninies Section Starts Here -->
<section class="amenities-section padding-bottom">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-10">
                <div class="section-header text-center">
                    <h2 class="title">{{ __(@$amenitiesContent->data_values->heading) }}</h2>
                    <p>{{ __(@$amenitiesContent->data_values->sub_heading) }}</p>
                </div>
            </div>
        </div>
        <div class="amenities-wrapper">
            <div class="amenities-slider">
                @foreach ($facilities as $item)
                    <div class="single-slider">
                        <div class="amenities-item">
                            <div class="thumb">
                                @php
                                    echo $item->data_values->icon;
                                @endphp
                            </div>
                            <h6 class="title">{{ __($item->data_values->title) }}</h6>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
<!-- Our Ameninies Section Ends Here -->

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

            $(".amenities-slider").slick({
                fade: false,
                slidesToShow: 6,
                slidesToScroll: 1,
                infinite: true,
                autoplay: true,
                pauseOnHover: true,
                centerMode: false,
                dots: false,
                arrows: false,
                // asNavFor: '.testimonial-img-slider',
                nextArrow: '<i class="las la-arrow-right arrow-right"></i>',
                prevArrow: '<i class="las la-arrow-left arrow-left"></i> ',
                responsive: [{
                        breakpoint: 1199,
                        settings: {
                            slidesToShow: 5,
                            slidesToScroll: 1,
                        },
                    },
                    {
                        breakpoint: 991,
                        settings: {
                            slidesToShow: 4,
                            slidesToScroll: 1,
                        },
                    },
                    {
                        breakpoint: 767,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 1,
                        },
                    },
                    {
                        breakpoint: 500,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1,
                        },
                    },
                ],
            });
        });
    </script>
@endpush
