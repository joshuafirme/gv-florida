@php
    $blogContent = getContent('blog.content', true);
    $blogElements = getContent('blog.element', false, 3);
@endphp

<!-- Blog Section Starts Here -->
<section class="blog-section padding-top padding-bottom">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-10">
                <div class="section-header text-center">
                    <h2 class="title">{{ __(@$blogContent->data_values->heading) }}</h2>
                    <p>{{ __(@$blogContent->data_values->sub_heading) }}</p>
                </div>
            </div>
        </div>
        <div class="row justify-content-center g-4">
            @foreach($blogElements as $item)
            <div class="col-lg-4 col-md-6 col-sm-10">
                <div class="post-item">
                    <div class="post-thumb">
                        <img src="{{ frontendImage('blog','thumb_'.$item->data_values->image) }}" alt="blog">
                    </div>
                    <div class="post-content">
                        <ul class="post-meta">
                            <li>
                                <span class="date"><i class="las la-calendar-check"></i>{{ showDateTime($item->created_at, 'd M Y') }}</span>
                            </li>
                        </ul>
                        <h4 class="title"><a href="{{ route('blog.details',slug($item->data_values->title)) }}">{{ __(@$item->data_values->title) }}</a></h4>
                        <p>{{ __(strLimit(strip_tags($item->data_values->description),50)) }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
<!-- Blog Section Ends Here -->
