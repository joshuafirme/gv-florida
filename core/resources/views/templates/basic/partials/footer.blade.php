@php
    $content = getContent('footer.content', true);
    $socialLinks = getContent('social_links.element', orderById: true);
    $policies = getContent('policy_pages.element', orderById: true);
    $pages = App\Models\Page::where('tempname', $activeTemplate)
        ->where('is_default', Status::NO)
        ->get();
@endphp
<!-- Footer Section Starts Here -->
<section class="footer-section">
    <div class="footer-top">
        <div class="container">
            <div class="row footer-wrapper gy-sm-5 gy-4">
                <div class="col-xl-4 col-lg-3 col-md-6 col-sm-6">
                    <div class="footer-widget">
                        <div class="logo">
                            <img src="{{ siteLogo('dark') }}" alt="@lang('Logo')">
                        </div>
                        <p>{{ __(@$content->data_values->short_description) }}</p>
                        <ul class="social-icons">
                            @foreach ($socialLinks as $item)
                                <li>
                                    <a href="{{ @$item->data_values->url }}">@php echo @$item->data_values->icon @endphp</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <div class="footer-widget">
                        <h4 class="widget-title">@lang('Useful Links')</h4>
                        <ul class="footer-links">
                            @foreach ($pages as $k => $data)
                                <li>
                                    <a href="{{ route('pages', [$data->slug]) }}">{{ __($data->name) }}</a>
                                </li>
                            @endforeach
                            <li>
                                <a href="{{ route('blog') }}">@lang('Blog')</a>
                            </li>
                            <li>
                                <a href="{{ route('contact') }}">@lang('Contact')</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <div class="footer-widget">
                        <h4 class="widget-title">@lang('Policies')</h4>
                        <ul class="footer-links">
                            @foreach ($policies as $policy)
                                <li>
                                    <a
                                        href="{{ route('policy.pages', $policy->slug) }}">@php
                                            echo @$policy->data_values->title;
                                        @endphp</a>
                                </li>
                            @endforeach

                        </ul>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                    <div class="footer-widget">
                        <h4 class="widget-title">@lang('Contact Info')</h4>
                        @php
                            $contacts = getContent('contact.content', true);
                        @endphp
                        <ul class="footer-contacts">
                            <li>
                                <i class="las la-map-pin"></i> {{ __(@$contacts->data_values->address) }}
                            </li>
                            <li>
                                <i class="las la-phone-volume"></i> <a
                                    href="tel:{{ __(@$contacts->data_values->contact_number) }}">
                                    {{ __(@$contacts->data_values->contact_number) }}</a>
                            </li>
                            <li>
                                <i class="las la-envelope"></i> <a
                                    href="mailto:{{ __(@$contacts->data_values->email) }}">
                                    {{ __(@$contacts->data_values->email) }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Footer Section Ends Here -->


@push('script')
    <script>
        (function($) {
            "use strict";


            $('.search').on('change', function() {
                $('#filterForm').submit();
            });
        })(jQuery);
    </script>
@endpush
