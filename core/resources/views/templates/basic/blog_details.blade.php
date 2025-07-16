@extends($activeTemplate.'layouts.frontend')
@section('content')

<section class="blog-details padding-top padding-bottom">
	<div class="container">
		<div class="row gy-5">
			<div class="col-lg-8">
				<div class="post-thumb">
					<img src="{{ frontendImage('blog',@$blog->data_values->image) }}" alt="{{ __(@$blog->data_values->title) }}">
				</div>
				<div class="post-details-content">
					<div class="content-inner">
						<ul class="meta-post">
							<li>
								<i class="las la-calendar-check"></i>
								<span>{{ showDateTime($blog->created_at, 'd M Y') }}</span>
							</li>
						</ul>
						<h4 class="title">{{ __(@$blog->data_values->title) }}</h4>
						<p>@php
							echo @$blog->data_values->description
						@endphp</p>
						<ul class="meta-content">
							<li>
								<h5 class="title">@lang('Share On')</h5>
								<ul class="social-icons">
									<li>
										<a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" class="facebook"><i class="lab la-facebook-f"></i></a>
									</li>
									<li>
										<a href="http://pinterest.com/pin/create/button/?url={{urlencode(url()->current()) }}&description={{ __(@$blog->data_values->short_description) }}&media={{ frontendImage('blog',@$blog->data_values->image) }}" title="@lang('Pinterest')">

                                            <i class="lab la-pinterest-p"></i>
                                        </a>
									</li>
									<li>
										<a href="http://www.linkedin.com/shareArticle?mini=true&amp;url={{urlencode(url()->current()) }}&amp;title=my share text&amp;summary=dit is de linkedin summary" title="@lang('Linkedin')">

                                            <i class="lab la-linkedin-in"></i>
                                        </a>
									</li>
									<li>
										<a href="https://twitter.com/intent/tweet?text={{ __(@$blog->data_values->title) }}%0A{{ url()->current() }}" title="@lang('Twitter')">

                                            <i class="lab la-twitter"></i>
                                        </a>
									</li>
								</ul>
							</li>
						</ul>
					</div>
					<div class="fb-comments mt-3" data-href="{{ route('blog.details',slug($blog->data_values->title)) }}" data-numposts="5"></div>
				</div>
			</div>
			<div class="col-lg-4 col-md-12">
				<div class="blog-sidebar">
					<div class="sidebar-item">
						<div class="latest-post-wrapper item-inner">
							<h5 class="title">@lang('Latest Post')</h5>
							@foreach($latestPost as $latest)
							<div class="lastest-post-item">
								<div class="thumb">
									<img src="{{ getImage('assets/images/frontend/blog/thumb_'.$latest->data_values->image) }}" alt="blog">
								</div>
								<div class="content">
									<h6 class="title"><a href="{{ route('blog.details',slug($latest->data_values->title)) }}">{{ __(@$latest->data_values->title) }}</a></h6>
									<ul class="meta-post">
										<li>
											<i class="fas fa-calendar-week"></i> <span> {{ showDateTime($latest->created_at, 'd M Y') }}</span>
										</li>
									</ul>
								</div>
							</div>
							@endforeach
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

@endsection


@push('fbComment')
    @php echo loadExtension('fb-comment') @endphp
@endpush
