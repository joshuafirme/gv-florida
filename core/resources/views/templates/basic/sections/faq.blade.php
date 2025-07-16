@php
    $faqContent = getContent('faq.content', true);
    $faqElements = getContent('faq.element', false, null, true);
@endphp

<section class="faq-section padding-top padding-bottom">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="section-header text-center">
                    <h2 class="title">{{ __(@$faqContent->data_values->heading) }}</h2>
                    <p>{{ __(@$faqContent->data_values->sub_heading) }}</p>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="faq-wrapper">
                    @foreach ($faqElements as $item)
                        @if ($loop->odd)
                            <div class="faq-item">
                                <div class="faq-title">
                                    <span class="icon"></span>
                                    <h5 class="title">{{ __(@$item->data_values->question) }}</h5>
                                </div>
                                <div class="faq-content">
                                    <p>@php
                                        echo @$item->data_values->answer;
                                    @endphp</p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="col-lg-6">
                <div class="faq-wrapper">
                    @foreach ($faqElements as $item)
                        @if ($loop->even)
                            <div class="faq-item">
                                <div class="faq-title">
                                    <span class="icon"></span>
                                    <h5 class="title">{{ __(@$item->data_values->question) }}</h5>
                                </div>
                                <div class="faq-content">
                                    <p>@php
                                        echo @$item->data_values->answer;
                                    @endphp</p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
