@php
    $hwContent = getContent('how_it_works.content', true);
    $hwClements = getContent('how_it_works.element', false,null, true);
@endphp
<!-- Working Process Section Starts Here -->
<section class="working-process padding-top padding-bottom">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-10">
                <div class="section-header text-center">
                    <h2 class="title">{{ __(@$hwContent->data_values->heading) }}</h2>
                    <p>{{ __(@$hwContent->data_values->sub_heading) }}</p>
                </div>
            </div>
        </div>
        <div class="row g-4 gy-md-5 justify-content-center">
            @foreach ($hwClements as $item)
                <div class="col-lg-4 col-md-6 col-sm-10">
                    <div class="working-process-item">
                        <div class="thumb-wrapper">
                            <span>{{ ++$loop->index > 9 ? $loop->index : '0'. $loop->index }}</span>
                            <div class="thumb">
                            @php
                                echo @$item->data_values->icon
                            @endphp
                        </div>
                        </div>
                        <div class="content">
                            <h4 class="title">{{ __(@$item->data_values->heading) }}</h4>
                            <p>{{ __(@$item->data_values->sub_heading) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
<!-- Working Process Section Ends Here -->
