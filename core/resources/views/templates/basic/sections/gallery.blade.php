@php
    $testContent = getContent('gallery.content', true);
    $testElements = getContent('gallery.element', false);
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
        <div class="row gy-5">
            {{-- <div class="col-lg-8 col-md-10">
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
                                            <img src="{{ frontendImage('gallery', @$item->data_values->image) }}"
                                                alt="gallery">
                                        </div>
                                        <h5 class="name">{{ __(@$item->data_values->person) }}</h5>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div> --}}
            @foreach ($testElements as $item)
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#imageModal"
                        data-bs-image="{{ frontendImage('gallery', @$item->data_values->image) }}"
                        data-title="{{ $item->data_values->title }}"
                        data-description="{{ $item->data_values->description }}">
                        <img src="{{ frontendImage('gallery', @$item->data_values->image) }}" alt="gallery">
                        <div class="caption text-center">{{ $item->data_values->title }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-0">
                <img id="modalImage" src="" alt="Full Image" class="w-100">
                <div class="image-info">
                    <h4 id="modalTitle"></h4>
                    <div id="modalDescription"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


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

            const imageModal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('modalTitle');
            const modalDescription = document.getElementById('modalDescription');

            imageModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const imageUrl = button.getAttribute('data-bs-image');
                modalImage.src = imageUrl;
                modalTitle.innerText = button.getAttribute('data-title');
                modalDescription.innerText = button.getAttribute('data-description');
            });
        });
    </script>
@endpush
