@php
    $aboutContent = getContent('about.content', true);
@endphp

<section class="about-section padding-top padding-bottom">
    <div class="container">
        <div class="row mb-4 mb-md-5 gy-4">
            <div class="col-lg-7 col-xl-6">
                <div class="about-content">
                    <div class="section-header">
                        <h2 class="title">{{ __(@$aboutContent->data_values->heading) }}</h2>
                    </div>
                    <p>
                        @php
                            echo @$aboutContent->data_values->short_description;
                        @endphp
                    </p>
                </div>
            </div>
            <div class="col-lg-5 col-xl-6">
                <div class="about-thumb">
                    <img src="{{ frontendImage('about',@$aboutContent->data_values->image) }}" alt="{{ __(@$aboutContent->data_values->heading) }}">
                </div>
            </div>
        </div>
        <div class="about-details">
            <div class="item">
                <h4 class="title">{{ __(@$aboutContent->data_values->title) }}</h4>
                <p>
                    @php
                        echo @$aboutContent->data_values->description
                    @endphp
                </p>
            </div>
        </div>
    </div>
</section>
