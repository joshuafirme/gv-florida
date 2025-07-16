@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <!-- Blog Section Starts Here -->
    <section class="blog-section padding-top">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12">
                    <div class="row justify-content-center g-4">
                        @foreach ($blogs as $item)
                            <div class="col-lg-3 col-md-3 col-sm-10">
                                <div class="post-item">
                                    <div class="post-thumb">
                                        <img src="{{ getImage('assets/images/frontend/blog/thumb_' . $item->data_values->image) }}" alt="{{ __(@$item->data_values->title) }}">
                                    </div>
                                    <div class="post-content">
                                        <ul class="post-meta">
                                            <li>
                                                <span class="date"><i class="las la-calendar-check"></i>{{ showDateTime($item->created_at, 'd M Y') }}</span>
                                            </li>
                                        </ul>
                                        <h4 class="title"><a href="{{ route('blog.details', slug($item->data_values->title)) }}">{{ __(@$item->data_values->title) }}</a></h4>
                                        <p>{{ __(strLimit(strip_tags($item->data_values->description), 50)) }}</p>

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($blogs->hasPages())
                        <div class="custom-pagination">
                            {{ paginateLinks($blogs) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Blog Section Ends Here -->
    @if ($sections->secs != null)
        @foreach (json_decode($sections->secs) as $sec)
            @include($activeTemplate . 'sections.' . $sec)
        @endforeach
    @endif
@endsection
