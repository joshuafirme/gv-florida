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
                            <a href="{{ route('user.ticket.history') }}">@lang('My Tickets')</a>
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
                            <button type="button" class="passenger-notification-read-all js-passenger-notification-read-all"
                                data-read-all-url="{{ route('user.notifications.read-all') }}">
                                @lang('Mark all read')
                            </button>
                        </div>
                        <div id="passengerNotificationDropdown" class="passenger-notification-list">
                            @forelse (($passengerNotifications ?? collect()) as $notification)
                                <button type="button"
                                    class="passenger-notification-item {{ $notification->is_read ? '' : 'is-unread' }}"
                                    data-detail-url="{{ route('user.notifications.show', $notification) }}"
                                    data-read-url="{{ route('user.notifications.read', $notification) }}">
                                    <span class="passenger-notification-icon is-{{ $notification->uiTone() }}">
                                        <i class="las {{ $notification->uiIcon() }}"></i>
                                    </span>
                                    <span class="passenger-notification-copy">
                                        <strong>{{ $notification->title }}</strong>
                                        <span>{{ $notification->message }}</span>
                                        <small>{{ diffForHumans($notification->created_at) }}</small>
                                    </span>
                                </button>
                            @empty
                                <div class="passenger-notification-empty">@lang('No notifications yet.')</div>
                            @endforelse
                        </div>
                        <a class="passenger-notification-history" href="{{ route('user.notifications.index') }}">
                            <span>@lang('Notification history')</span>
                            <i class="las la-angle-right"></i>
                        </a>
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

<div class="modal fade passenger-notification-modal" id="passengerNotificationModal" tabindex="-1"
    aria-labelledby="passengerNotificationModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="passenger-notification-modal-head">
                <span class="passenger-notification-modal-icon is-success" id="passengerNotificationModalIcon">
                    <i class="las la-bell"></i>
                </span>
                <div>
                    <h5 id="passengerNotificationModalTitle">@lang('Notification')</h5>
                    <small id="passengerNotificationModalDate"></small>
                </div>
                <button type="button" class="passenger-notification-modal-close" data-bs-dismiss="modal"
                    aria-label="@lang('Close')" title="@lang('Close')">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="passenger-notification-modal-message" id="passengerNotificationModalMessage"></p>
                <section id="passengerNotificationMovementSection" hidden>
                    <h6 id="passengerNotificationMovementTitle">@lang('Update Details')</h6>
                    <div class="passenger-notification-detail-list" id="passengerNotificationMovement"></div>
                </section>
                <section>
                    <h6>@lang('Ticket Details')</h6>
                    <div class="passenger-notification-detail-list" id="passengerNotificationDetails"></div>
                </section>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('Close')</button>
                <a href="#" target="_blank" rel="noopener" class="btn btn--base" id="passengerNotificationVoucher" hidden>
                    <i class="las la-download"></i> @lang('E-voucher')
                </a>
            </div>
        </div>
    </div>
</div>

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

        .header-bottom.is-notification-open { z-index: 1060 !important; }
        .passenger-notification { align-items: center; display: flex; }
        .passenger-notification-bell { align-items: center; background: transparent; border: 0; color: #343a4a; display: inline-flex; justify-content: center; padding: 7px; position: relative; }
        .passenger-notification-bell i { font-size: 21px; }
        .passenger-notification-count { align-items: center; background: var(--booking-primary, #df2a82); border: 2px solid #fff; border-radius: 999px; color: #fff; display: flex; font-size: 9px; font-weight: 800; height: 19px; justify-content: center; min-width: 19px; padding: 0 4px; position: absolute; right: -6px; top: -6px; }
        .passenger-notification-menu { border: 1px solid #e8ebef; border-radius: 8px; box-shadow: 0 16px 38px rgba(31, 41, 55, .18); margin-top: 12px !important; min-width: 370px; overflow: hidden; padding: 0; z-index: 1070; }
        .passenger-notification-head { align-items: center; border-bottom: 1px solid #edf0f5; display: flex; justify-content: space-between; padding: 14px 16px; }
        .passenger-notification-head strong { color: #172033; font-size: 15px; }
        .passenger-notification-read-all { background: transparent; border: 0; color: #168c91; font-size: 12px; font-weight: 700; padding: 3px 0; text-transform: none; }
        .passenger-notification-list { max-height: 350px; overflow-y: auto; overscroll-behavior: contain; }
        .passenger-notification-item { align-items: flex-start; background: #fff; border: 0; border-bottom: 1px solid #f0f1f4; color: #343a4a; display: flex; gap: 11px; padding: 13px 16px; text-align: left; text-transform: none; white-space: normal; width: 100%; }
        .passenger-notification-item:hover,
        .passenger-notification-item:focus { background: #f8fafc; color: #343a4a; outline: 0; }
        .passenger-notification-item.is-unread { background: #fff6fa; }
        .passenger-notification-icon,
        .passenger-notification-modal-icon { align-items: center; background: #e4f4ff; border-radius: 50%; color: #1383a8; display: inline-flex; flex: 0 0 auto; height: 38px; justify-content: center; width: 38px; }
        .passenger-notification-icon i,
        .passenger-notification-modal-icon i { font-size: 21px; }
        .passenger-notification-icon.is-success,
        .passenger-notification-modal-icon.is-success { background: #dcf8e8; color: #15945a; }
        .passenger-notification-icon.is-warning,
        .passenger-notification-modal-icon.is-warning { background: #fff1d6; color: #ad6c00; }
        .passenger-notification-icon.is-danger,
        .passenger-notification-modal-icon.is-danger { background: #ffe3e6; color: #c93443; }
        .passenger-notification-copy { display: flex; flex: 1; flex-direction: column; min-width: 0; }
        .passenger-notification-copy strong { color: #172033; font-size: 13px; line-height: 1.3; }
        .passenger-notification-copy > span { color: #606a79; display: -webkit-box; font-size: 11px; line-height: 1.45; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .passenger-notification-copy small { color: #969daa; font-size: 10px; margin-top: 3px; }
        .passenger-notification-empty { color: #818896; font-size: 12px; padding: 30px 16px; text-align: center; }
        .passenger-notification-history { align-items: center; color: #168c91; display: flex; font-size: 13px; font-weight: 800; gap: 7px; justify-content: center; padding: 13px 16px; }
        .passenger-notification-history:hover { background: #f8fafc; color: #126f73; }

        .passenger-notification-modal .modal-dialog { max-width: 520px; }
        .passenger-notification-modal .modal-content { border: 0; border-radius: 8px; box-shadow: 0 24px 70px rgba(15, 23, 42, .25); max-height: min(760px, calc(100dvh - 32px)); overflow: hidden; }
        .passenger-notification-modal-head { align-items: center; border-bottom: 1px solid #e8ebef; display: flex; gap: 12px; padding: 16px 20px; }
        .passenger-notification-modal-head > div { min-width: 0; }
        .passenger-notification-modal-head h5 { color: #172033; font-size: 16px; font-weight: 800; margin: 0; }
        .passenger-notification-modal-head small { color: #9098a5; display: block; font-size: 10px; margin-top: 2px; }
        .passenger-notification-modal-close { align-items: center; background: transparent; border: 0; color: #8b94a1; display: inline-flex; font-size: 22px; height: 34px; justify-content: center; margin-left: auto; padding: 0; width: 34px; }
        .passenger-notification-modal .modal-body { padding: 18px 20px; }
        .passenger-notification-modal-message { background: #f7fafc; border-left: 3px solid #23aeb4; color: #5f6978; font-size: 12px; line-height: 1.55; margin: 0 0 18px; padding: 10px 12px; }
        .passenger-notification-modal section + section { margin-top: 18px; }
        .passenger-notification-modal section h6 { color: #168c91; font-size: 11px; font-weight: 800; margin: 0 0 8px; text-transform: uppercase; }
        .passenger-notification-detail-list { border: 1px solid #e8ebef; border-radius: 8px; overflow: hidden; }
        .passenger-notification-detail-row { align-items: center; border-bottom: 1px solid #edf0f4; display: flex; gap: 16px; justify-content: space-between; min-height: 40px; padding: 9px 14px; }
        .passenger-notification-detail-row:last-child { border-bottom: 0; }
        .passenger-notification-detail-label { color: #7b8491; flex: 0 0 35%; font-size: 12px; }
        .passenger-notification-detail-value { color: #2e3746; font-size: 12px; font-weight: 700; text-align: right; }
        .passenger-notification-detail-value.is-amount { color: var(--booking-primary, #df2a82); font-size: 14px; }
        .passenger-notification-change { align-items: center; display: inline-flex; flex-wrap: wrap; gap: 6px; justify-content: flex-end; }
        .passenger-notification-change del { color: #9ba2ad; font-weight: 500; }
        .passenger-notification-change i { color: #1aa7ad; }
        .passenger-notification-status { border-radius: 999px; display: inline-flex; font-size: 10px; font-weight: 800; padding: 4px 9px; }
        .passenger-notification-status.is-success { background: #dcf8e8; color: #15834f; }
        .passenger-notification-status.is-warning { background: #fff1d6; color: #996000; }
        .passenger-notification-status.is-danger { background: #ffe3e6; color: #b82d3b; }
        .passenger-notification-status.is-neutral { background: #edf0f4; color: #616b78; }
        .passenger-notification-modal .modal-footer { border-top: 1px solid #e8ebef; gap: 8px; padding: 12px 20px; }
        .passenger-notification-modal .modal-footer .btn { align-items: center; border-radius: 7px; display: inline-flex; font-size: 12px; font-weight: 800; gap: 6px; justify-content: center; min-height: 38px; padding: 8px 14px; }
        .passenger-notification-modal-active .scrollToTop { display: none !important; }

        @media (max-width: 575px) {
            .passenger-notification-menu { min-width: min(350px, calc(100vw - 20px)); }
            .passenger-notification-modal .modal-dialog { margin: 8px; }
            .passenger-notification-modal .modal-content { max-height: calc(100dvh - 16px); }
            .passenger-notification-modal-head,
            .passenger-notification-modal .modal-body,
            .passenger-notification-modal .modal-footer { padding-left: 14px; padding-right: 14px; }
            .passenger-notification-detail-row { padding-left: 11px; padding-right: 11px; }
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
