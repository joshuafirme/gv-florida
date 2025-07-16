@php
    $content = getContent('contact.content', true);
    $language = App\Models\Language::all();
    $selectedLang = $language->where('code', session('lang'))->first();
@endphp
<!-- Header Section Starts Here -->
<div class="header-top">
    <div class="container">
        <div class="header-top-area">
            <ul class="left-content">
                <li>
                    <i class="las la-phone"></i>
                    <a href="tel:{{ __(@$content->data_values->contact_number) }}">
                        {{ __(@$content->data_values->contact_number) }}
                    </a>
                </li>
                <li>
                    <i class="las la-envelope-open"></i>
                    <a href="mailto:{{ __(@$content->data_values->email) }}">
                        {{ __(@$content->data_values->email) }}
                    </a>
                </li>
            </ul>
            <div class="right-content">
                <div>
                    @if (gs('multi_language'))
                        <div>
                            <div class="language dropdown">
                                <button class="language-wrapper" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="language-content">
                                        <div class="language_flag">
                                            <img src="{{ getImage(getFilePath('language') . '/' . @$selectedLang->image, getFileSize('language')) }}" alt="flag">
                                        </div>
                                        <p class="language_text_select">{{ __(@$selectedLang->name) }}</p>
                                    </div>
                                    <span class="collapse-icon"><i class="las la-angle-down"></i></span>
                                </button>
                                <div class="dropdown-menu langList_dropdow py-2">
                                    <ul class="langList">
                                        @foreach ($language as $item)
                                            <li class="language-list langSel" data-code="{{ $item->code }}">
                                                <div class="language_flag">
                                                    <img src="{{ getImage(getFilePath('language') . '/' . $item->image, getFileSize('language')) }}" alt="flag">
                                                </div>
                                                <p class="language_text">{{ $item->name }}</p>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<div class="header-bottom">
    <div class="container">
        <div class="header-bottom-area">
            <div class="logo">
                <a href="{{ route('home') }}">
                    <img src="{{ siteLogo() }}" alt="@lang('Logo')">
                </a>
            </div> <!-- Logo End -->
            <ul class="menu">
                <li>
                    <a href="{{ route('user.home') }}">@lang('Dashboard')</a>
                </li>
                <li>
                    <a href="javascript::void()">@lang('Booking')</a>
                    <ul class="sub-menu">
                        <li>
                            <a href="{{ route('ticket') }}">@lang('Buy Ticket')</a>
                        </li>
                        <li>
                            <a href="{{ route('user.ticket.history') }}">@lang('Booking History')</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="javascript::void()">@lang('Support Ticket')</a>
                    <ul class="sub-menu">
                        <li>
                            <a href="{{ route('ticket.open') }}">@lang('Create New')</a>
                        </li>
                        <li>
                            <a href="{{ route('ticket.index') }}">@lang('Tickets')</a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="#0">@lang('Profile')</a>
                    <ul class="sub-menu">
                        <li>
                            <a href="{{ route('user.profile.setting') }}">@lang('Profile')</a>
                        </li>
                        <li>
                            <a href="{{ route('user.change.password') }}">@lang('Change Password')</a>
                        </li>
                        <li>
                            <a href="{{ route('user.logout') }}">@lang('Logout')</a>
                        </li>
                    </ul>
                </li>
            </ul>
            <div class="d-flex flex-wrap algin-items-center">
                <a href="{{ route('ticket') }}" class="cmn--btn btn--sm">@lang('Buy Tickets')</a>
                <div class="header-trigger-wrapper d-flex d-lg-none ms-4">
                    <div class="header-trigger d-block d-lg-none">
                        <span></span>
                    </div>
                    <div class="top-bar-trigger">
                        <i class="las la-ellipsis-v"></i>
                    </div>
                </div><!-- Trigger End-->
            </div>
        </div>
    </div>
</div>
<!-- Header Section Ends Here -->

@push('style')
    <style>
        .language-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            width: max-content;
            margin-left: 12px;
            padding: 0;
            background-color: transparent;
            border: 0;
        }

        .language_flag {
            flex-shrink: 0;
            display: flex;
        }

        .language_flag img {
            height: 20px;
            width: 20px;
            object-fit: cover;
            border-radius: 50%;
        }

        .language-wrapper.show .collapse-icon {
            transform: rotate(180deg)
        }

        .collapse-icon {
            font-size: 14px;
            display: flex;
            transition: all linear 0.2s;
            color: #111
        }

        .language_text_select {
            font-size: 14px;
            font-weight: 400;
            color: #111;
        }

        .language-content {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .language_text {
            color: #111
        }

        .language-list {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            cursor: pointer;
        }

        .language-list:hover {
            background-color: rgba(0, 0, 0, 0.04);
        }

        .language .dropdown-menu {
            position: absolute;
            opacity: 0;
            visibility: hidden;
            top: 100%;
            display: unset;
            background: #ffffffea;
            box-shadow: 0px 0px 4px 0px rgba(0, 0, 0, 0.04), 0px 8px 16px 0px rgba(0, 0, 0, 0.08);
            min-width: 150px;
            padding: 7px 0 !important;
            border-radius: 8px;
            border: 1px solid rgb(255 255 255 / 10%);
        }

        .language .dropdown-menu.show {
            visibility: visible;
            opacity: 1;
        }
    </style>
@endpush

@push('script')
    <script>
        $(document).ready(function() {
            "use strict";
            $(".langSel").on("click", function() {
                window.location.href = "{{ route('home') }}/change/" + $(this).data('code');
            });
        });
    </script>
@endpush
