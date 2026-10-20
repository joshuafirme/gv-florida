@section('content')

    @php
        use Carbon\Carbon;
        $kiosk_id = request()->kiosk_id;
        $allowed_advance_booking_days = getAllowedAdvanceBookingDays($kiosk_id);
        $businessToday = \App\Support\BusinessTime::today();
        $advance_window_end = $businessToday->copy()->addDays($allowed_advance_booking_days);
        $kioskHeroPath = getFilePath('kioskHero') . '/kiosk-hero.png';
        $kioskHeroVersion = file_exists($kioskHeroPath) ? filemtime($kioskHeroPath) : appVersion();
        $kioskHeroUrl = getImage($kioskHeroPath) . '?v=' . $kioskHeroVersion;
        $kioskHeroCopy = $kiosk_id ? app(\App\Services\KioskSettingsService::class)->get() : [];
    @endphp
    @if ($kiosk_id)
        @php
            $layout = 'layouts.kiosk';
        @endphp
        @include('templates.basic.partials.kiosk_nav')
    @endif
    @php
        $selected_counter = request('pickup') ? request('pickup') : request('counter_id');
        $selected_destination = request('destination') ? request('destination') : request('selected_destination');
        $date_of_journey = request('date_of_journey')
            ? \App\Support\BusinessTime::parse(request('date_of_journey'))->format('Y-m-d')
            : $businessToday->format('Y-m-d');
        $dateOfJourneyQuery = request('date_of_journey')
            ? \App\Support\BusinessTime::parse(request('date_of_journey'))->format('m/d/Y')
            : $businessToday->format('m/d/Y');
        $dateOfJourneyDisplay = Carbon::parse($date_of_journey)->format('d M Y');
    @endphp
    @extends($activeTemplate . $layout)

    <style>
        .ticket-search-bar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #fff;
        }

        .kiosk-navbar {
            z-index: 1030;
        }

        .ticket-search-bar--kiosk {
            top: 97px;
            z-index: 1020;
        }

        .kiosk-advance-window {
            padding: 16px 0 10px;
            background-position: left center;
            background-size: cover;
        }

        .kiosk-advance-window__card {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) 1px minmax(280px, .8fr);
            align-items: center;
            gap: 28px;
            padding: 15px 15px;
            background: #fff7fb;
            background: color-mix(in srgb, var(--booking-primary) 5%, #fff);
            border: 1px solid var(--booking-primary-border);
            border-radius: 8px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .08);
            margin: 10px 0;
        }

        .kiosk-advance-window__primary,
        .kiosk-advance-window__example {
            display: flex;
            align-items: center;
            min-width: 0;
        }

        .kiosk-advance-window__icon,
        .kiosk-advance-window__info-icon {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            color: var(--booking-primary);
            border: 2px solid var(--booking-primary);
            border-radius: 50%;
        }

        .kiosk-advance-window__icon {
            width: 66px;
            height: 66px;
            margin-right: 20px;
            font-size: 36px;
        }

        .kiosk-advance-window__info-icon {
            width: 42px;
            height: 42px;
            margin-right: 16px;
            font-size: 25px;
        }

        .kiosk-advance-window__eyebrow,
        .kiosk-advance-window__example p {
            margin: 0;
            color: #29303d;
            font-size: 15px;
            line-height: 1.45;
        }

        .kiosk-advance-window__limit {
            margin: 2px 0 0;
            color: #111827;
            font-size: 25px;
            font-weight: 800;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .kiosk-advance-window__limit strong,
        .kiosk-advance-window__example strong {
            color: var(--booking-primary);
        }

        .kiosk-advance-window__divider {
            width: 1px;
            height: 58px;
            background: var(--booking-primary-border);
        }

        .trip-search-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            left: 38px;
            line-height: 1;
            margin: 0;
            pointer-events: none;
            position: absolute;
            top: 8px;
            z-index: 12;
        }

        .ticket-form .ticket-search-field > i {
            align-items: center;
            bottom: auto;
            display: flex;
            height: 52px;
            justify-content: center;
            left: 8px;
            line-height: 1;
            padding: 0;
            pointer-events: none;
            top: 0;
            width: 20px;
        }

        .ticket-form .ticket-search-field > .form--control,
        .ticket-form .ticket-search-field .select2-selection--single {
            height: 52px;
            padding-left: 38px !important;
            padding-top: 16px !important;
        }

        .ticket-form .ticket-search-field .select2-selection--single {
            padding-top: 0 !important;
        }

        .ticket-form .ticket-search-field .select2-selection__rendered {
            bottom: 8px;
            display: block;
            height: auto;
            left: 38px;
            line-height: 16px !important;
            margin-left: 0;
            overflow: hidden;
            padding: 0 !important;
            position: absolute;
            right: 30px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ticket-form .ticket-search-field .select2-selection__arrow {
            top: 12px;
        }

        .ticket-search-actions {
            padding-top: 0;
        }

        .ticket-search-actions .btn {
            min-height: 52px;
        }

        @media screen and (max-width: 991px) {
            .kiosk-advance-window__card {
                gap: 12px;
                grid-template-columns: minmax(0, 1fr);
            }

            .kiosk-advance-window__divider {
                width: 100%;
                height: 1px;
            }

            .ticket-search-actions {
                padding-top: 0;
            }
        }


        @media screen and (min-width: 990px) {
            .ticket-filter-container {
                position: sticky;
                top: 250px;
                /* height of the top search bar */
                align-self: flex-start;
                z-index: 10;
            }
        }

        @media screen and (max-width: 989px) {
            .container {
                max-width: 100%;
            }

            .ticket-filter-container {
                background: #fff;
                padding: 15px;
                border-radius: 10px;
                margin-bottom: 15px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                display: none;
            }

        }


        /* TRIPS COLUMN */

        /* Trip card layout improvement */
        .ticket-item {
            background: #fff;
            border: 1px solid #edf0f3;
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .07);
            cursor: pointer;
            display: block;
            margin-bottom: 12px !important;
            padding: 17px;
            position: relative;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .ticket-item:hover {
            border-color: var(--booking-primary-border);
            box-shadow: 0 16px 32px rgba(15, 23, 42, .12);
            transform: translateY(-1px);
        }

        .ticket-item.is-disabled {
            cursor: not-allowed;
            opacity: .72;
        }

        .ticket-item.is-disabled:hover {
            border-color: #edf0f3;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .07);
            transform: none;
        }

        .trip-card-top,
        .trip-card-route,
        .trip-card-meta,
        .trip-card-actions {
            position: relative;
            z-index: 2;
        }

        .trip-card-top {
            align-items: flex-start;
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr auto;
        }

        .trip-route-title {
            color: #07162f;
            font-size: 20px;
            font-weight: 900;
            letter-spacing: .01em;
            line-height: 1.2;
            margin: 0 0 10px;
            text-transform: uppercase;
        }

        .trip-time-main {
            color: #07162f;
            font-size: 36px;
            font-weight: 900;
            line-height: 1;
            margin: 0;
        }

        .trip-duration {
            align-items: center;
            color: #718096;
            display: flex;
            flex-wrap: wrap;
            font-size: 14px;
            font-weight: 700;
            gap: 7px;
            margin-top: 7px;
        }

        .trip-duration__item {
            align-items: center;
            display: inline-flex;
            gap: 5px;
            white-space: nowrap;
        }

        .trip-duration__item i {
            color: var(--booking-primary);
            font-size: 18px;
        }

        .trip-duration__separator {
            background: #cbd5e1;
            height: 18px;
            width: 1px;
        }

        .trip-card-price {
            text-align: right;
        }

        .fleet-pill {
            align-items: center;
            background: var(--booking-primary-soft);
            border: 1px solid var(--booking-primary-border);
            border-radius: 999px;
            color: var(--booking-primary);
            display: inline-flex;
            font-size: 12px;
            font-weight: 900;
            gap: 6px;
            margin-bottom: 10px;
            padding: 2px 12px;
            text-transform: uppercase;
        }

        .trip-price {
            color: var(--booking-primary);
            font-size: 32px;
            font-weight: 900;
            line-height: 1;
            margin: 0;
        }

        .trip-price-range {
            color: #8b95a1;
            font-size: 12px;
            font-weight: 800;
            margin-top: 8px;
        }

        .trip-price-unavailable {
            color: #b45309;
            display: inline-block;
            font-size: 13px;
            line-height: 1.2;
            max-width: 130px;
        }

        .trip-card-route {
            align-items: center;
            background: #f8fafc;
            border-radius: 12px;
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr auto 1fr;
            margin-top: 12px;
            padding: 4px 16px;
        }

        .trip-point {
            min-width: 0;
        }

        .trip-point--end {
            text-align: right;
        }

        .trip-point small {
            color: #94a3b8;
            display: block;
            font-size: 11px;
            font-weight: 900;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .trip-point strong {
            color: #07162f;
            display: block;
            font-size: 15px;
            font-weight: 900;
            text-transform: uppercase;
            word-break: break-word;
        }

        .trip-route-arrow {
            color: #94a3b8;
            font-size: 26px;
        }

        .trip-card-meta {
            align-items: center;
            color: #718096;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 10px;
        }

        .trip-card-meta span {
            align-items: center;
            display: inline-flex;
            font-size: 13px;
            font-weight: 700;
            gap: 5px;
        }

        .trip-card-actions {
            display: grid;
            gap: 10px;
            margin-top: 12px;
        }

        .trip-availability {
            align-items: center;
            border-radius: 999px;
            display: flex;
            font-weight: 900;
            gap: 8px;
            justify-content: center;
            min-height: 38px;
            padding: 8px 14px;
            text-transform: uppercase;
        }

        .trip-availability.is-available {
            background: #ecfdf5;
            border: 1px solid #86efac;
            color: #047857;
        }

        .trip-availability.is-full {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #ef4444;
        }

        .trip-select-btn {
            align-items: center;
            background: var(--booking-primary);
            border: 0;
            border-radius: 10px;
            color: var(--booking-on-primary);
            display: flex;
            font-weight: 900;
            justify-content: center;
            min-height: 44px;
            text-decoration: none;
        }

        .trip-select-btn:hover {
            background: var(--booking-primary-hover);
            color: var(--booking-on-primary);
        }

        .trip-select-btn.is-disabled {
            background: #f1f5f9;
            color: #a0a9b5;
            pointer-events: none;
        }

        .route-details {
            border-top: 1px dashed #e5e7eb;
            margin-top: 10px;
            padding-top: 10px;
        }

        .route-details__toggle {
            color: var(--booking-primary);
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
            text-transform: uppercase;
        }

        .route-details__toggle:hover {
            color: var(--booking-primary-hover);
        }

        .trip-item-empty {
            cursor: default;
        }

        .trip-list-region {
            min-height: 180px;
            position: relative;
        }

        .trip-list-skeleton {
            display: grid;
            gap: 12px;
        }

        .trip-card-skeleton {
            background: #fff;
            border: 1px solid #edf0f3;
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
            min-height: 248px;
            overflow: hidden;
            padding: 17px;
        }

        .trip-card-skeleton__top,
        .trip-card-skeleton__route,
        .trip-card-skeleton__meta {
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .trip-card-skeleton__route {
            background: #f8fafc;
            border-radius: 12px;
            margin-top: 18px;
            padding: 16px;
        }

        .trip-card-skeleton__meta {
            justify-content: flex-start;
            margin-top: 16px;
        }

        .trip-skeleton-line {
            background: #e8edf2;
            border-radius: 6px;
            display: block;
            height: 14px;
            overflow: hidden;
            position: relative;
        }

        .trip-skeleton-line::after {
            animation: trip-skeleton-shimmer 1.15s ease-in-out infinite;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .85), transparent);
            content: '';
            inset: 0;
            position: absolute;
            transform: translateX(-100%);
        }

        .trip-skeleton-line--title { height: 20px; width: min(360px, 58vw); }
        .trip-skeleton-line--time { height: 34px; margin-top: 14px; width: 150px; }
        .trip-skeleton-line--pill { height: 27px; width: 150px; }
        .trip-skeleton-line--fare { height: 32px; margin-left: auto; margin-top: 14px; width: 130px; }
        .trip-skeleton-line--point { height: 34px; width: 38%; }
        .trip-skeleton-line--meta { width: 110px; }
        .trip-skeleton-line--action { height: 38px; margin-top: 16px; width: 100%; }

        @keyframes trip-skeleton-shimmer {
            to { transform: translateX(100%); }
        }

        @media (prefers-reduced-motion: reduce) {
            .trip-skeleton-line::after { animation: none; }
        }

        /* Seats badge */
        .seat-count {
            font-weight: 600;
            font-size: 14px;
            background: var(--booking-primary-soft);
            color: var(--booking-primary);
            padding: 4px 10px;
            border-radius: 20px;
        }

        @media screen and (max-width: 767px) {
            .ticket-search-bar,
            .ticket-search-bar--kiosk {
                background: #fff !important;
                position: relative;
                top: auto;
            }

            .ticket-search-bar::before {
                display: none;
            }

            .bus-search-header {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-bottom: 0;
                border-radius: 14px 14px 0 0;
                bottom: 0;
                box-shadow: 0 -8px 24px rgba(15, 23, 42, .13);
                left: 0;
                padding: 9px 12px max(9px, env(safe-area-inset-bottom));
                position: fixed;
                right: 0;
                z-index: 1040;
            }

            .bus-search-header .ticket-form-two {
                --bs-gutter-x: 8px;
                --bs-gutter-y: 6px;
                margin: 0;
            }

            .bus-search-header .ticket-form-two > [class*="col-"] {
                padding-left: 0;
                padding-right: 0;
            }

            .ticket-form .ticket-search-field > i {
                font-size: 18px;
                height: 46px;
            }

            .ticket-form .ticket-search-field > .form--control,
            .ticket-form .ticket-search-field .select2-selection--single {
                font-size: 13px;
                height: 46px;
            }

            .ticket-form .ticket-search-field .select2-selection__rendered {
                bottom: 6px;
            }

            .ticket-form .ticket-search-field .select2-selection__arrow {
                top: 9px;
            }

            .ticket-search-actions .btn {
                min-height: 40px;
            }

            .ticket-section {
                padding-bottom: calc(var(--mobile-ticket-search-height, 214px) + 24px) !important;
            }

            .kiosk-advance-window {
                padding: 10px 0 6px;
            }

            .kiosk-advance-window__card {
                gap: 10px;
                padding: 14px;
            }

            .kiosk-advance-window__icon {
                width: 44px;
                height: 44px;
                margin-right: 12px;
                font-size: 24px;
            }

            .kiosk-advance-window__info-icon {
                width: 30px;
                height: 30px;
                margin-right: 10px;
                font-size: 18px;
            }

            .kiosk-advance-window__limit {
                font-size: 16px;
                line-height: 1.25;
            }

            .kiosk-advance-window__eyebrow,
            .kiosk-advance-window__example p {
                font-size: 12px;
                line-height: 1.35;
            }

            .kiosk-advance-window__limit strong {
                white-space: nowrap;
            }

            .ticket-item {
                padding: 15px;
            }

            .trip-card-skeleton {
                min-height: 280px;
                padding: 15px;
            }

            .trip-card-skeleton__top {
                flex-direction: column;
            }

            .trip-skeleton-line--fare {
                margin-left: 0;
            }

            .trip-card-top {
                grid-template-columns: 1fr;
            }

            .trip-card-price {
                text-align: left;
            }

            .fleet-pill {
                margin-bottom: 10px;
            }

            .trip-time-main,
            .trip-price {
                font-size: 30px;
            }

            .trip-card-route {
                grid-template-columns: 1fr;
                text-align: left;
            }

            .trip-duration {
                gap: 5px;
            }

            .trip-point--end {
                text-align: left;
            }

            .trip-route-arrow {
                transform: rotate(90deg);
            }
        }

        .kiosk-idle-hero{background:#151a20;color:#fff;height:100vh;height:100dvh;inset:0;overflow:hidden;pointer-events:none;position:fixed;transform:translateY(-105%);transition:transform .62s cubic-bezier(.76,0,.24,1),visibility 0s linear .62s;visibility:hidden;width:100vw;z-index:10000}
        .kiosk-idle-hero.is-active{pointer-events:auto;transform:translateY(0);transition:transform .62s cubic-bezier(.76,0,.24,1);visibility:visible}
        .kiosk-idle-hero.is-leaving{pointer-events:none;transform:translateY(-105%)}
        .kiosk-idle-hero__image{display:block;height:100%;inset:0;object-fit:cover;object-position:center;opacity:.82;position:absolute;width:100%}
        .kiosk-idle-hero__shade{background:linear-gradient(180deg,rgba(10,14,18,.08) 35%,rgba(10,14,18,.92) 100%);inset:0;position:absolute}
        .kiosk-idle-hero__content{align-items:center;display:flex;height:100%;justify-content:center;padding:5vh 5vw;position:relative;text-align:center;z-index:1}
        .kiosk-idle-hero__footer{align-self:flex-end;padding:28px 32px;width:min(960px,100%)}
        .kiosk-idle-hero__benefits{align-items:stretch;display:grid;grid-template-columns:repeat(3,1fr);margin-bottom:26px}
        .kiosk-idle-hero__benefit{align-items:center;border-right:1px solid rgba(255,255,255,.55);display:flex;font-size:18px;font-weight:800;gap:12px;justify-content:center;padding:5px 18px;text-align:left;text-shadow:0 2px 6px rgba(0,0,0,.85);text-transform:uppercase;white-space:pre-line}
        .kiosk-idle-hero__benefit:last-child{border-right:0}
        .kiosk-idle-hero__benefit i{border:3px solid #fff;border-radius:50%;display:grid;flex:0 0 58px;font-size:27px;height:58px;place-items:center;width:58px}
        .kiosk-idle-hero__cta{align-items:center;background:#fff;border:0;border-radius:999px;box-shadow:0 10px 28px rgba(0,0,0,.24);color:var(--booking-primary);display:inline-flex;font-size:30px;font-weight:900;gap:18px;justify-content:center;min-height:86px;min-width:min(560px,100%);padding:14px 52px;text-transform:uppercase}
        .kiosk-idle-hero__cta i{background:var(--booking-primary);border-radius:50%;color:#fff;display:grid;flex:0 0 56px;font-size:30px;height:56px;place-items:center;width:56px}
        body.kiosk-attract-active{overflow:hidden}
        @media(orientation:portrait){.kiosk-idle-hero__image{height:100dvh;object-position:center center;width:100vw}.kiosk-idle-hero__content{height:100dvh;padding:4vh 4vw}.kiosk-idle-hero__footer{padding:34px 28px;width:min(760px,100%)}.kiosk-idle-hero__benefits{margin-bottom:30px}.kiosk-idle-hero__benefit{font-size:20px;gap:10px;padding:6px 12px}.kiosk-idle-hero__benefit i{flex-basis:64px;font-size:30px;height:64px;width:64px}.kiosk-idle-hero__cta{font-size:32px;min-height:94px;min-width:min(600px,100%)}.kiosk-idle-hero__cta i{flex-basis:62px;font-size:34px;height:62px;width:62px}}
        @media(max-width:575px){.kiosk-idle-hero__content{padding:18px}.kiosk-idle-hero__footer{padding:20px 14px}.kiosk-idle-hero__benefits{gap:6px;margin-bottom:20px}.kiosk-idle-hero__benefit{border-right:1px solid rgba(255,255,255,.4);display:flex;flex-direction:column;font-size:12px;gap:7px;padding:0 5px;text-align:center}.kiosk-idle-hero__benefit i{flex-basis:46px;font-size:21px;height:46px;width:46px}.kiosk-idle-hero__cta{font-size:21px;gap:10px;min-height:66px;min-width:100%;padding:8px 20px}.kiosk-idle-hero__cta i{flex-basis:44px;font-size:23px;height:44px;width:44px}}
        @media(orientation:landscape) and (max-height:700px){.kiosk-idle-hero__content{padding:16px 4vw}.kiosk-idle-hero__footer{padding:14px 24px}.kiosk-idle-hero__benefits{margin-bottom:12px}.kiosk-idle-hero__benefit{font-size:13px}.kiosk-idle-hero__benefit i{flex-basis:42px;font-size:19px;height:42px;width:42px}.kiosk-idle-hero__cta{font-size:20px;min-height:58px;padding:7px 30px}.kiosk-idle-hero__cta i{flex-basis:40px;font-size:20px;height:40px;width:40px}}
    </style>

    @if ($kiosk_id)
        <section class="kiosk-idle-hero" id="kioskIdleHero" role="button" tabindex="-1"
            aria-label="{{ $kioskHeroCopy['button_text'] }}"
            aria-hidden="true">
            <img class="kiosk-idle-hero__image" src="{{ $kioskHeroUrl }}" alt="Florida bus on a scenic route">
            <span class="kiosk-idle-hero__shade" aria-hidden="true"></span>
            <div class="kiosk-idle-hero__content">
                <div class="kiosk-idle-hero__footer">
                    <div class="kiosk-idle-hero__benefits" aria-hidden="true">
                        <span class="kiosk-idle-hero__benefit"><i class="fas fa-chair"></i> {{ $kioskHeroCopy['benefit_one'] }}</span>
                        <span class="kiosk-idle-hero__benefit"><i class="fas fa-shield-alt"></i> {{ $kioskHeroCopy['benefit_two'] }}</span>
                        <span class="kiosk-idle-hero__benefit"><i class="fas fa-map-marker-alt"></i> {{ $kioskHeroCopy['benefit_three'] }}</span>
                    </div>
                    <span class="kiosk-idle-hero__cta" aria-hidden="true">
                        <i class="fas fa-hand-pointer"></i> {{ $kioskHeroCopy['button_text'] }}
                    </span>
                </div>
            </div>
        </section>
    @endif

    <div class="ticket-search-bar {{ $kiosk_id ? 'ticket-search-bar--kiosk' : '' }} bg_img"
        style="background: url({{ getImage('assets/templates/basic/images/search_bg.jpg') }}) left center;">
        <div class="container">
            @if ($kiosk_id)
                <div class="kiosk-advance-window__card" aria-label="Kiosk advance booking period">
                    <div class="kiosk-advance-window__primary">
                        <span class="kiosk-advance-window__icon" aria-hidden="true">
                            <i class="las la-calendar-check"></i>
                        </span>
                        <div>
                            <p class="kiosk-advance-window__eyebrow">@lang('You can book trips')</p>
                            <p class="kiosk-advance-window__limit">
                                @if ($allowed_advance_booking_days === 0)
                                    <strong>@lang('Today')</strong> @lang('only')
                                @else
                                    @lang('Up to') <strong>{{ $allowed_advance_booking_days }}
                                        {{ trans_choice('day|days', $allowed_advance_booking_days) }}</strong>
                                    @lang('in advance only')
                                @endif
                            </p>
                        </div>
                    </div>
                    <span class="kiosk-advance-window__divider" aria-hidden="true"></span>
                    <div class="kiosk-advance-window__example">
                        <span class="kiosk-advance-window__info-icon" aria-hidden="true">
                            <i class="las la-info"></i>
                        </span>
                        <p>
                            @lang('Today is') {{ now()->format('F j, Y') }}.<br>
                            @if ($allowed_advance_booking_days === 0)
                                @lang('Trips can only be booked for today.')
                            @else
                                @lang('You can book trips until') <strong>{{ $advance_window_end->format('F j, Y') }}</strong>
                                @lang('only').
                            @endif
                        </p>
                    </div>
                </div>
            @endif
            <div class="bus-search-header">
                <form action="{{ route('ticket') }}" method="GET"
                    class="ticket-form ticket-form-two row g-3 justify-content-center">
                    @if (request()->kiosk_id)
                        <input type="hidden" name="kiosk_id" value="{{ request()->kiosk_id }}">
                    @endif

                    @if (isset($selected_counter) || request()->counter_id)
                        <input type="hidden" name="counter_id" value="{{ $selected_counter ?? request()->counter_id }}">
                    @endif

                    <div class="col-md-4 col-lg-3">
                        <div class="form--group ticket-search-field">
                            <label class="trip-search-label" for="ticket-pickup">@lang('From')</label>
                            <i class="las la-location-arrow"></i>
                            <select name="pickup" id="ticket-pickup" class="form--control select2">
                                <option value="">@lang('Pickup Point')</option>
                                @foreach ($counters as $counter)
                                    <option value="{{ $counter->id }}" @selected(request('pickup', $selected_counter ?? '') == $counter->id)>
                                        {{ __($counter->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 col-lg-3">
                        <div class="form--group ticket-search-field">
                            <label class="trip-search-label" for="ticket-destination">@lang('To')</label>
                            <i class="las la-map-marker"></i>
                            <select name="destination" id="ticket-destination" class="form--control select2"
                                data-default-option="@lang('All Destination')">
                                <option value="">@lang('All Destination')</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 col-lg-3">
                        <div class="form--group ticket-search-field">
                            <label class="trip-search-label" for="ticket-travel-date">@lang('Departure')</label>
                            <i class="las la-calendar-check"></i>
                            <input type="text" name="date_of_journey" id="ticket-travel-date"
                                class="form--control date-range" placeholder="@lang('Departure')" autocomplete="off"
                                value="{{ $dateOfJourneyDisplay }}">
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="form--group ticket-search-actions d-flex gap-2">
                            <button type="submit" class="btn btn--base w-100">@lang('Find Tickets')</button>
                            <a href="{{ route('ticket', ['kiosk_id' => request()->kiosk_id, 'counter_id' => request()->counter_id]) }}"
                                class="btn btn-dark w-100 d-flex align-items-center justify-content-center">
                                @lang('Clear')
                            </a>
                        </div>
                    </div>
                </form>
                {{-- <div class="d-lg-none row d-flex justify-content-center">
                    <div class="col-md-6">
                        <button class="btn btn--base w-100" data-bs-toggle="offcanvas" data-bs-target="#filterPanel">
                            @php
                                $fleetTypes = request('fleetType') ?? [];
                                $count = count($fleetTypes);
                            @endphp
                            <i class="las la-filter"></i> Filters {{ $count ? "($count)" : '' }}
                        </button>
                    </div>
                </div> --}}
            </div>
        </div>
    </div>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterPanel">
        <div class="offcanvas-header">
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body">


            @include('templates.basic.partials.ticket-filter', ['filterFormId' => 'mobileFilterForm'])

        </div>
    </div>
    <section class="ticket-section padding-bottom section-bg">
        <div class="container">
            <div class="row gy-5">
                <div class="col-lg-3 ticket-filter-container">
                    @include('templates.basic.partials.ticket-filter')
                </div>

                <div class="col-lg-9">
                    <div class="trip-list-region" id="tripListRegion" aria-live="polite" aria-busy="false">
                        @include('templates.basic.partials.trip-list')
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/global/css/daterangepicker.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/daterangepicker.min.js') }}"></script>
    @if (config('services.pusher.key'))
        <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    @endif
@endpush

@push('script')
    <script>
        function toggleRouteStops(id) {
            const stops = document.querySelectorAll('.stops-' + id);
            const dots = document.querySelectorAll('.dots-' + id);
            const textElem = document.getElementById('text-' + id);

            let isHidden = stops[0].classList.contains('d-none');

            if (isHidden) {
                // Expand
                stops.forEach(el => el.classList.remove('d-none'));
                dots.forEach(el => el.classList.add('d-none'));
                textElem.innerText = "@lang('Hide Route')";
            } else {
                // Collapse
                stops.forEach(el => el.classList.add('d-none'));
                dots.forEach(el => el.classList.remove('d-none'));
                textElem.innerText = "@lang('View Route')";
            }
        }

        (function($) {
            "use strict";

            @if ($kiosk_id)
                const IDLE_TIMEOUT = 60000;
                const EXIT_DURATION = 650;
                const idleHero = document.getElementById('kioskIdleHero');
                let idleTimer;
                let isExiting = false;

                function showIdleHero() {
                    idleHero.classList.remove('is-leaving');
                    idleHero.classList.add('is-active');
                    idleHero.setAttribute('aria-hidden', 'false');
                    idleHero.setAttribute('tabindex', '0');
                    document.body.classList.add('kiosk-attract-active');
                    idleHero.focus({ preventScroll: true });
                }

                function resetIdleTimer() {
                    if (idleHero.classList.contains('is-active') || isExiting) return;
                    clearTimeout(idleTimer);
                    idleTimer = setTimeout(showIdleHero, IDLE_TIMEOUT);
                }

                function exitIdleHero() {
                    if (isExiting) return;
                    isExiting = true;
                    clearTimeout(idleTimer);
                    idleHero.classList.add('is-leaving');

                    window.setTimeout(function() {
                        window.location.reload();
                    }, EXIT_DURATION);
                }

                ['pointerdown', 'keydown', 'scroll', 'touchstart'].forEach(function(eventName) {
                    document.addEventListener(eventName, resetIdleTimer, { capture: true, passive: true });
                });
                idleHero.addEventListener('click', exitIdleHero);
                idleHero.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        exitIdleHero();
                    }
                });
                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden) resetIdleTimer();
                });
                resetIdleTimer();
            @endif

            $('.select2').select2();
            $('.search-multiple').select2({
                placeholder: "Select an option"
            });

            const searchForm = document.querySelector('.ticket-form');
            const pickupSelect = document.getElementById('ticket-pickup');
            const destinationSelect = document.getElementById('ticket-destination');
            const travelDateInput = document.getElementById('ticket-travel-date');
            const tripListRegion = document.getElementById('tripListRegion');
            const requestedDestination = String(@json(request('destination') ?: request('selected_destination') ?: ''));
            const droppingPointsEndpoint = @json(route('getDroppingPoints', ['counter_id' => '__COUNTER__']));
            let tripRequestController = null;
            let destinationRequestController = null;
            let lastSuccessfulTripMarkup = tripListRegion.innerHTML;

            const skeletonCard = `
                <div class="trip-card-skeleton" aria-hidden="true">
                    <div class="trip-card-skeleton__top">
                        <div>
                            <span class="trip-skeleton-line trip-skeleton-line--title"></span>
                            <span class="trip-skeleton-line trip-skeleton-line--time"></span>
                        </div>
                        <div>
                            <span class="trip-skeleton-line trip-skeleton-line--pill"></span>
                            <span class="trip-skeleton-line trip-skeleton-line--fare"></span>
                        </div>
                    </div>
                    <div class="trip-card-skeleton__route">
                        <span class="trip-skeleton-line trip-skeleton-line--point"></span>
                        <span class="trip-skeleton-line trip-skeleton-line--point"></span>
                    </div>
                    <div class="trip-card-skeleton__meta">
                        <span class="trip-skeleton-line trip-skeleton-line--meta"></span>
                        <span class="trip-skeleton-line trip-skeleton-line--meta"></span>
                    </div>
                    <span class="trip-skeleton-line trip-skeleton-line--action"></span>
                </div>`;

            function showTripSkeleton() {
                tripListRegion.setAttribute('aria-busy', 'true');
                tripListRegion.innerHTML = `
                    <div class="trip-list-skeleton" role="status" aria-label="@lang('Updating trips')">
                        <span class="visually-hidden">@lang('Updating available trips')</span>
                        ${skeletonCard.repeat(3)}
                    </div>`;
            }

            function reportTripListError(message) {
                if (typeof window.notify === 'function') {
                    window.notify('error', message);
                    return;
                }

                console.error(message);
            }

            function setUrlParam(url, name, value) {
                const normalizedValue = String(value || '').trim();
                if (normalizedValue) {
                    url.searchParams.set(name, normalizedValue);
                } else {
                    url.searchParams.delete(name);
                }
            }

            function buildTripListUrl(baseUrl = window.location.href, preservePage = false) {
                const url = new URL(baseUrl, window.location.href);
                const kioskInput = searchForm.querySelector('input[name="kiosk_id"]');
                const counterInput = searchForm.querySelector('input[name="counter_id"]');

                setUrlParam(url, 'pickup', pickupSelect.value);
                setUrlParam(url, 'destination', destinationSelect.value);
                setUrlParam(url, 'date_of_journey', travelDateInput.value);
                setUrlParam(url, 'kiosk_id', kioskInput ? kioskInput.value : '');
                url.searchParams.delete('selected_destination');

                if (kioskInput && counterInput) {
                    setUrlParam(url, 'counter_id', counterInput.value);
                } else {
                    url.searchParams.delete('counter_id');
                }

                if (!preservePage) {
                    url.searchParams.delete('page');
                }

                return url;
            }

            function syncFilterContext() {
                $('.ticket-filter-form input[name="counter_id"]').val(pickupSelect.value);
                $('.ticket-filter-form input[name="selected_destination"]').val(destinationSelect.value);
            }

            async function refreshTripList(url, options = {}) {
                const settings = Object.assign({ history: 'replace', skeleton: true }, options);

                if (tripRequestController) {
                    tripRequestController.abort();
                }

                const requestController = new AbortController();
                tripRequestController = requestController;

                if (settings.skeleton) {
                    showTripSkeleton();
                } else {
                    tripListRegion.setAttribute('aria-busy', 'true');
                }

                try {
                    const response = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        signal: requestController.signal
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || '@lang('Unable to update the trip list.')');
                    }

                    if (requestController !== tripRequestController) {
                        return;
                    }

                    tripListRegion.innerHTML = payload.html;
                    tripListRegion.setAttribute('aria-busy', 'false');
                    lastSuccessfulTripMarkup = payload.html;
                    tripListRealtime.journeyDate = payload.journey_date;
                    syncFilterContext();

                    if (settings.history !== 'none') {
                        const method = settings.history === 'push' ? 'pushState' : 'replaceState';
                        window.history[method]({}, '', payload.url);
                    }
                } catch (error) {
                    if (error.name === 'AbortError' || requestController !== tripRequestController) {
                        return;
                    }

                    tripListRegion.innerHTML = lastSuccessfulTripMarkup;
                    tripListRegion.setAttribute('aria-busy', 'false');
                    reportTripListError(error.message || '@lang('Unable to update the trip list.')');
                } finally {
                    if (requestController === tripRequestController) {
                        tripRequestController = null;
                    }
                }
            }

            async function loadDroppingPoints(preferredDestination = '') {
                if (destinationRequestController) {
                    destinationRequestController.abort();
                }

                const pickup = String(pickupSelect.value || '');
                if (!pickup) {
                    destinationSelect.replaceChildren(new Option(destinationSelect.dataset.defaultOption, ''));
                    $(destinationSelect).val('').trigger('change.select2');
                    return;
                }

                const requestController = new AbortController();
                destinationRequestController = requestController;
                const url = new URL(droppingPointsEndpoint.replace('__COUNTER__', encodeURIComponent(pickup)));
                const kioskInput = searchForm.querySelector('input[name="kiosk_id"]');

                if (kioskInput && kioskInput.value) {
                    url.searchParams.set('kiosk_id', kioskInput.value);
                }
                if (travelDateInput.value) {
                    url.searchParams.set('date_of_journey', travelDateInput.value);
                }

                $(destinationSelect).prop('disabled', true).trigger('change.select2');

                try {
                    const response = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        signal: requestController.signal
                    });

                    if (!response.ok) {
                        throw new Error('@lang('Unable to load destinations.')');
                    }

                    const destinations = await response.json();
                    if (requestController !== destinationRequestController) {
                        return;
                    }

                    const options = [new Option(destinationSelect.dataset.defaultOption, '')];
                    destinations.forEach(function(destination) {
                        options.push(new Option(destination.name, destination.id));
                    });
                    destinationSelect.replaceChildren(...options);

                    const destinationExists = destinations.some(function(destination) {
                        return String(destination.id) === String(preferredDestination);
                    });
                    $(destinationSelect)
                        .val(destinationExists ? String(preferredDestination) : '')
                        .trigger('change.select2');
                } finally {
                    if (requestController === destinationRequestController) {
                        destinationRequestController = null;
                        $(destinationSelect).prop('disabled', false).trigger('change.select2');
                    }
                }
            }

            async function refreshForRouteChange(preferredDestination) {
                if (tripRequestController) {
                    tripRequestController.abort();
                    tripRequestController = null;
                }
                showTripSkeleton();

                try {
                    await loadDroppingPoints(preferredDestination);
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }
                    reportTripListError(error.message || '@lang('Unable to load destinations.')');
                }

                await refreshTripList(buildTripListUrl(), { skeleton: false });
            }

            const tripListRealtime = {
                key: @json(config('services.pusher.key')),
                cluster: @json(config('services.pusher.cluster', 'ap1')),
                channel: @json(App\Services\ScheduleBoardBroadcaster::CHANNEL),
                event: @json(App\Services\ScheduleBoardBroadcaster::EVENT),
                journeyDate: @json(\Carbon\Carbon::parse($date_of_journey)->format('Y-m-d')),
            };
            let tripListRefreshTimer = null;

            if (tripListRealtime.key && typeof window.Pusher !== 'undefined') {
                const tripListPusher = new Pusher(tripListRealtime.key, {
                    cluster: tripListRealtime.cluster || 'ap1'
                });
                const tripListChannel = tripListPusher.subscribe(tripListRealtime.channel);

                tripListChannel.bind(tripListRealtime.event, function(event) {
                    if (!event || String(event.date_of_journey || '') !== tripListRealtime.journeyDate) {
                        return;
                    }

                    const visibleTrip = Array.from(document.querySelectorAll('.js-trip-card[data-trip-id]'))
                        .some(function(card) {
                            return String(card.dataset.tripId) === String(event.trip_id);
                        });

                    if (!visibleTrip) return;

                    window.clearTimeout(tripListRefreshTimer);
                    tripListRefreshTimer = window.setTimeout(function() {
                        refreshTripList(buildTripListUrl(window.location.href, true), {
                            history: 'none'
                        });
                    }, 300);
                });
            }

            $(pickupSelect).on('change', function() {
                refreshForRouteChange(destinationSelect.value);
            });

            $(destinationSelect).on('change', function() {
                refreshTripList(buildTripListUrl());
            });

            $(searchForm).on('submit', function(event) {
                event.preventDefault();
                refreshTripList(buildTripListUrl());
            });

            $(document).on('submit', '.ticket-filter-form', function(event) {
                event.preventDefault();
                const url = buildTripListUrl();
                const multipleFilterNames = ['fleetType[]', 'routes[]', 'schedules[]'];
                const formData = new FormData(this);

                Array.from(url.searchParams.keys()).forEach(function(name) {
                    if (multipleFilterNames.some(filterName => name === filterName || name.startsWith(filterName.slice(0, -1)))) {
                        url.searchParams.delete(name);
                    }
                });
                formData.forEach(function(value, name) {
                    if (multipleFilterNames.includes(name) && value) {
                        url.searchParams.append(name, value);
                    }
                });

                refreshTripList(url);
            });

            $(document).on('click', '#tripListRegion .custom-pagination a', function(event) {
                event.preventDefault();
                refreshTripList(buildTripListUrl(this.href, true), { history: 'push' });
            });

            $(document).on('click', '#tripListRegion .js-trip-card', function(event) {
                if ($(event.target).closest('a, button, [data-trip-card-ignore]').length) {
                    return;
                }

                const url = $(this).data('href');
                if (url) {
                    window.location.href = url;
                }
            });

            $(document).on('keydown', '#tripListRegion .js-trip-card', function(event) {
                if (!['Enter', ' '].includes(event.key)) {
                    return;
                }

                const url = $(this).data('href');
                if (url) {
                    event.preventDefault();
                    window.location.href = url;
                }
            });

            const datePicker = $('.date-range').daterangepicker({
                autoUpdateInput: true,
                singleDatePicker: true,
                startDate: moment(@json($date_of_journey), 'YYYY-MM-DD'),
                minDate: new Date(),
                maxDate: moment().add("{{ $allowed_advance_booking_days }}", 'days'),
                locale: {
                    format: 'DD MMM YYYY'
                }

            });

            let selectedJourneyDate = @json($dateOfJourneyDisplay);

            datePicker.on('apply.daterangepicker', function(event, picker) {
                const newJourneyDate = picker.startDate.format('DD MMM YYYY');
                this.value = newJourneyDate;

                if (newJourneyDate === selectedJourneyDate) {
                    return;
                }

                selectedJourneyDate = newJourneyDate;
                refreshForRouteChange(destinationSelect.value);
            });

            loadDroppingPoints(requestedDestination).catch(function(error) {
                if (error.name !== 'AbortError') {
                    reportTripListError(error.message || '@lang('Unable to load destinations.')');
                }
            });

            window.addEventListener('popstate', function() {
                refreshTripList(new URL(window.location.href), { history: 'none' });
            });

            const mobileSearchPanel = document.querySelector('.bus-search-header');

            function syncMobileSearchClearance() {
                const panelHeight = window.matchMedia('(max-width: 767px)').matches && mobileSearchPanel
                    ? Math.ceil(mobileSearchPanel.getBoundingClientRect().height)
                    : 0;

                document.documentElement.style.setProperty('--mobile-ticket-search-height', `${panelHeight}px`);
            }

            syncMobileSearchClearance();
            window.addEventListener('resize', syncMobileSearchClearance);

            if (window.ResizeObserver && mobileSearchPanel) {
                new ResizeObserver(syncMobileSearchClearance).observe(mobileSearchPanel);
            }


            $('.reset-button').on('click', function() {
                const form = $(this).closest('form');
                form.find('.search').prop('checked', false);
                form.trigger('submit');
            })

        })(jQuery)
    </script>
@endpush
