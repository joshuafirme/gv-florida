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
                <div class="passenger-notification dropdown me-3">
                    <button class="passenger-notification-bell" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false" aria-label="@lang('Notifications')">
                        <i class="las la-bell"></i>
                        <span id="passengerNotificationCount"
                            class="passenger-notification-count {{ empty($passengerNotificationCount) ? 'd-none' : '' }}">
                            {{ $passengerNotificationCount ?? 0 }}
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end passenger-notification-menu">
                        <div class="passenger-notification-head">
                            <strong>@lang('Notifications')</strong>
                            <a href="{{ route('user.notifications.index') }}">@lang('View history')</a>
                        </div>
                        <div id="passengerNotificationDropdown" class="passenger-notification-list">
                            @forelse (($passengerNotifications ?? collect()) as $notification)
                                <a href="{{ route('user.notifications.index') }}"
                                    class="passenger-notification-item {{ $notification->is_read ? '' : 'is-unread' }}"
                                    data-read-url="{{ route('user.notifications.read', $notification) }}">
                                    <strong>{{ $notification->title }}</strong>
                                    <span>{{ $notification->message }}</span>
                                    <small>{{ diffForHumans($notification->created_at) }}</small>
                                </a>
                            @empty
                                <div class="passenger-notification-empty">@lang('No notifications yet.')</div>
                            @endforelse
                        </div>
                    </div>
                </div>
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

        .passenger-notification { display: flex; align-items: center; }
        .passenger-notification-bell { align-items: center; background: transparent; border: 0; color: #343a4a; display: inline-flex; justify-content: center; padding: 6px; position: relative; }
        .passenger-notification-bell i { font-size: 20px; }
        .passenger-notification-count { align-items: center; background: var(--booking-primary, #df2a82); border: 2px solid #fff; border-radius: 999px; color: #fff; display: flex; font-size: 9px; font-weight: 700; height: 19px; justify-content: center; min-width: 19px; padding: 0 4px; position: absolute; right: -7px; top: -7px; }
        .passenger-notification-menu { border: 0; border-radius: 12px; box-shadow: 0 14px 38px rgba(31, 41, 55, .18); min-width: 380px; overflow: hidden; padding: 0; }
        .passenger-notification-head { align-items: center; border-bottom: 1px solid #edf0f5; display: flex; justify-content: space-between; padding: 14px 16px; }
        .passenger-notification-head a { color: var(--booking-primary, #df2a82); font-size: 12px; }
        .passenger-notification-list { max-height: 390px; overflow-y: auto; }
        .passenger-notification-item { border-bottom: 1px solid #f0f1f4; color: #343a4a; display: flex; flex-direction: column; gap: 3px; padding: 12px 16px; white-space: normal; }
        .passenger-notification-item:hover { background: #f8f9fb; color: #343a4a; }
        .passenger-notification-item.is-unread { background: color-mix(in srgb, var(--booking-primary) 7%, #fff); border-left: 3px solid var(--booking-primary, #df2a82); }
        .passenger-notification-item strong { font-size: 13px; }
        .passenger-notification-item span { color: #646c7b; font-size: 11px; line-height: 1.45; }
        .passenger-notification-item small { color: #969daa; font-size: 10px; }
        .passenger-notification-empty { color: #818896; font-size: 12px; padding: 28px 16px; text-align: center; }

        @media (max-width: 575px) {
            .passenger-notification-menu { min-width: min(360px, calc(100vw - 24px)); }
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
