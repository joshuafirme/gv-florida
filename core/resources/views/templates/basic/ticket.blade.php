@section('content')

    @php
        use Carbon\Carbon;
        $kiosk_id = request()->kiosk_id;
        $allowed_advance_booking_days = getAllowedAdvanceBookingDays($kiosk_id);
        $advance_window_end = now()->startOfDay()->addDays($allowed_advance_booking_days);
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
            ? date('Y-m-d', strtotime(request('date_of_journey')))
            : date('Y-m-d');
        $dateOfJourneyQuery = request('date_of_journey')
            ? Carbon::parse(request('date_of_journey'))->format('m/d/Y')
            : date('m/d/Y');
        $dateOfJourneyDisplay = Carbon::parse($date_of_journey)->format('d M Y');
    @endphp
    @extends($activeTemplate . $layout)

    <style>
        :root {
            --booking-primary: var(--main-color, #df2a82);
            --booking-primary-hover: color-mix(in srgb, var(--booking-primary) 86%, #000);
            --booking-primary-soft: color-mix(in srgb, var(--booking-primary) 9%, #fff);
            --booking-primary-border: color-mix(in srgb, var(--booking-primary) 36%, #fff);
            --booking-primary-focus: color-mix(in srgb, var(--booking-primary) 18%, transparent);
            --booking-on-primary: #fff;
        }

        .ticket-search-bar {
            position: sticky;
            top: calc(var(--booking-online-stepper-top, 64px) + 68px);
            z-index: 1000;
            background: #fff;
        }

        .kiosk-navbar {
            z-index: 1030;
        }

        .ticket-search-bar--kiosk {
            top: 165px;
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

        .ticket-search-actions .btn i {
            color: inherit;
            font-size: 20px;
            left: auto;
            line-height: 1;
            padding: 0;
            position: static;
            top: auto;
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
            letter-spacing: 0;
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

        .bus-search-header {
            background: #fff;
        }

        .journey-point-selector {
            align-items: stretch;
            column-gap: 52px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            position: relative;
        }

        .journey-point-selector__field {
            min-width: 0;
        }

        .journey-point-selector .select2-selection--single {
            border: 1px solid #d7dde6 !important;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03) !important;
        }

        .journey-point-selector .select2-container--focus .select2-selection--single,
        .journey-point-selector .select2-container--open .select2-selection--single {
            border-color: var(--booking-primary) !important;
            box-shadow: 0 0 0 3px var(--booking-primary-soft) !important;
        }

        .journey-point-selector__direction {
            align-items: center;
            background: #fff;
            border: 1px solid #d7dde6;
            border-radius: 50%;
            color: var(--booking-primary);
            display: flex;
            font-size: 18px;
            height: 36px;
            justify-content: center;
            left: 50%;
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 36px;
            z-index: 14;
        }

        .ticket-search-actions .js-clear-trip-search {
            align-items: center;
            color: #fff;
            display: inline-flex;
            flex: 0 0 58px;
            font-size: 21px;
            justify-content: center;
            padding: 0;
        }

        .ticket-search-actions .js-clear-trip-search:hover,
        .ticket-search-actions .js-clear-trip-search:focus {
            color: #fff;
        }

        .ticket-section {
            padding-top: 18px;
        }

        .trip-results-toolbar {
            align-items: center;
            display: flex;
            gap: 24px;
            justify-content: space-between;
            margin-bottom: 14px;
            min-height: 46px;
        }

        .trip-results-summary {
            color: #364152;
            font-size: 15px;
            margin: 0;
        }

        .trip-results-summary strong {
            font-weight: 900;
        }

        .trip-results-summary span {
            color: var(--booking-primary);
            font-weight: 900;
            text-transform: uppercase;
        }

        .trip-results-filters {
            align-items: center;
            display: flex;
            gap: 10px;
        }

        .trip-results-filters label {
            align-items: center;
            background: #fff;
            border: 1px solid #d7dde6;
            border-radius: 8px;
            color: #7a8492;
            display: flex;
            gap: 6px;
            min-height: 42px;
            padding: 0 10px;
        }

        .trip-results-filters i {
            font-size: 18px;
        }

        .trip-result-filter {
            appearance: none;
            background: transparent;
            border: 0;
            color: #4b5563;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            min-width: 150px;
            outline: 0;
            padding: 0 20px 0 0;
        }

        .ticket-item {
            border-radius: 8px;
        }

        .ticket-item.is-disabled {
            background: #fffafa;
            border-color: #f4a8ae;
            opacity: 1;
        }

        .ticket-item.is-disabled .trip-route-title,
        .ticket-item.is-disabled .trip-time-main,
        .ticket-item.is-disabled .trip-price {
            color: #b4232a;
        }

        .trip-card-heading {
            align-items: center;
            border-bottom: 1px solid #e8ebef;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin: -17px -17px 14px;
            padding: 14px 17px;
        }

        .trip-card-heading .trip-route-title,
        .trip-card-heading .fleet-pill {
            margin: 0;
        }

        .trip-card-main {
            align-items: stretch;
            display: grid;
            grid-template-columns: minmax(170px, .75fr) minmax(260px, 1.5fr) minmax(170px, .75fr);
        }

        .trip-card-departure,
        .trip-card-route,
        .trip-card-price {
            padding: 2px 18px;
        }

        .trip-card-departure {
            border-right: 1px solid #e5e7eb;
            padding-left: 0;
        }

        .trip-card-price {
            border-left: 1px solid #e5e7eb;
            padding-right: 0;
        }

        .trip-card-departure > small,
        .trip-card-price > small,
        .trip-card-section-label {
            color: #929baa;
            display: block;
            font-size: 10px;
            font-weight: 900;
            margin-bottom: 7px;
            text-transform: uppercase;
        }

        .trip-departure-date,
        .trip-duration-text,
        .trip-arrival-time {
            color: #64748b;
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-top: 7px;
        }

        .trip-departure-date i {
            color: var(--booking-primary);
            font-size: 16px;
            margin-right: 4px;
        }

        .trip-card-main .trip-card-route {
            background: transparent;
            border-radius: 0;
            display: block;
            margin: 0;
        }

        .trip-card-main .trip-point {
            align-items: flex-start;
            display: flex;
            gap: 9px;
            padding: 2px 0 8px;
            text-align: left;
        }

        .trip-card-main .trip-point small {
            font-size: 10px;
            margin: 1px 0 0;
            text-transform: none;
        }

        .trip-route-dot {
            background: var(--booking-primary);
            border-radius: 50%;
            flex: 0 0 10px;
            height: 10px;
            margin-top: 5px;
            width: 10px;
        }

        .trip-route-dot.is-destination {
            background: #ef4444;
        }

        .trip-card-main .trip-card-price {
            text-align: right;
        }

        .trip-card-main .trip-price {
            font-size: 38px;
        }

        .trip-availability {
            border-radius: 8px;
            margin-top: 12px;
        }

        .trip-card-meta > strong {
            color: #929baa;
            flex-basis: 100%;
            font-size: 10px;
            text-transform: uppercase;
        }

        .trip-item-empty {
            align-items: center;
            cursor: default;
            display: flex;
            justify-content: center;
            min-height: 150px;
            text-align: center;
        }

        .daterangepicker td.trip-date-available:not(.active) {
            background: color-mix(in srgb, var(--booking-primary) 7%, #fff);
            box-shadow: inset 0 0 0 1px var(--booking-primary-border);
            color: var(--booking-primary);
            font-weight: 900;
        }

        .daterangepicker td.trip-date-unavailable:not(.active) {
            background: #f5f6f8;
            color: #a0a8b3;
        }

        .daterangepicker td.trip-date-not-open:not(.active) {
            background: #f5f6f8;
            color: #a0a8b3;
            opacity: .72;
        }

        .booking-date-guide {
            border-top: 1px solid #e5e7eb;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 8px;
            padding: 10px 8px 3px;
        }

        .booking-date-guide span {
            align-items: center;
            color: #64748b;
            display: inline-flex;
            font-size: 11px;
            font-weight: 700;
            gap: 5px;
        }

        .booking-date-guide > strong {
            color: #778195;
            flex-basis: 100%;
            font-size: 10px;
            text-transform: uppercase;
        }

        .booking-date-guide i {
            background: #f5f6f8;
            border: 1px solid #d7dde6;
            border-radius: 4px;
            display: inline-block;
            height: 15px;
            width: 15px;
        }

        .booking-date-guide i.is-available {
            background: color-mix(in srgb, var(--booking-primary) 7%, #fff);
            border-color: var(--booking-primary-border);
        }

        .booking-date-guide i.is-not-open {
            background: #eceff3;
            opacity: .72;
        }

        .booking-date-guide i.is-past {
            background: repeating-linear-gradient(135deg, #fff 0, #fff 3px, #e5e7eb 3px, #e5e7eb 5px);
        }

        .daterangepicker.single {
            width: min(430px, calc(100vw - 24px));
        }

        .daterangepicker.single .drp-calendar.single {
            max-width: none;
            width: 100%;
        }

        .daterangepicker.single td {
            height: 40px;
            line-height: 1.1;
            position: relative;
        }

        .daterangepicker.single td.trip-date-available::after,
        .daterangepicker.single td.trip-date-unavailable::after,
        .daterangepicker.single td.trip-date-not-open::after {
            display: block;
            font-size: 7px;
            font-weight: 700;
            margin-top: 2px;
        }

        .daterangepicker.single td.trip-date-available::after {
            content: "{{ __('Available') }}";
        }

        .daterangepicker.single td.trip-date-unavailable::after {
            content: "{{ __('No trip') }}";
        }

        .daterangepicker.single td.trip-date-not-open::after {
            content: "{{ __('Not open') }}";
        }

        .daterangepicker td.active,
        .daterangepicker td.active:hover {
            background: var(--booking-primary) !important;
            color: var(--booking-on-primary);
        }

        .trip-item-empty {
            cursor: default;
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
            body {
                overflow-x: hidden;
            }

            .kiosk-navbar .container {
                flex-wrap: nowrap;
                gap: 10px;
                max-width: 100%;
            }

            .kiosk-navbar img {
                height: auto;
                width: 56px !important;
            }

            .kiosk-navbar .clock-widget {
                font-size: 18px;
                white-space: nowrap;
            }

            .kiosk-navbar .navbar-brand {
                font-size: 0;
                margin: 0;
            }

            .kiosk-navbar .navbar-brand::after {
                content: "Kiosk";
                font-size: 14px;
            }

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

            .daterangepicker.single {
                bottom: calc(var(--mobile-ticket-search-height, 183px) + 8px) !important;
                left: 12px !important;
                max-height: calc(100dvh - var(--mobile-ticket-search-height, 183px) - 16px);
                overflow-y: auto;
                position: fixed !important;
                right: 12px !important;
                top: auto !important;
                width: auto;
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

            .trip-card-top {
                grid-template-columns: 1fr;
            }

            .journey-point-selector__direction {
                height: 30px;
                width: 30px;
            }

            .journey-point-selector {
                column-gap: 42px;
            }

            .trip-results-toolbar {
                align-items: stretch;
                flex-direction: column;
                gap: 10px;
            }

            .trip-results-filters {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .trip-result-filter {
                min-width: 0;
                width: 100%;
            }

            .trip-card-heading {
                align-items: center;
                flex-direction: row;
                gap: 8px;
            }

            .trip-card-main {
                grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr);
            }

            .trip-card-departure,
            .trip-card-route,
            .trip-card-price {
                border: 0;
                padding: 10px 0;
            }

            .trip-card-departure {
                border-right: 1px solid #e5e7eb;
            }

            .trip-card-main .trip-card-price {
                align-items: center;
                border-top: 1px solid #e5e7eb;
                display: flex;
                flex-wrap: wrap;
                gap: 4px 10px;
                grid-column: 1 / -1;
                text-align: left;
            }

            .trip-card-main .trip-card-price > small {
                flex-basis: 100%;
            }

            .trip-card-main .trip-card-price .trip-arrival-time {
                margin-left: auto;
            }

            .trip-card-price {
                text-align: left;
            }

            .fleet-pill {
                margin-bottom: 0;
                margin-left: auto;
                max-width: 54%;
                text-align: center;
                white-space: normal;
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

    @include('templates.basic.partials.booking_stepper', [
        'currentStep' => 'trip',
        'isKioskFlow' => (bool) $kiosk_id,
    ])

    <div class="ticket-search-bar {{ $kiosk_id ? 'ticket-search-bar--kiosk' : '' }} bg_img"
        style="background: url({{ getImage('assets/templates/basic/images/search_bg.jpg') }}) left center;">
        <div class="container">
            <div class="bus-search-header">
                <form action="{{ route('ticket') }}" method="GET"
                    class="ticket-form ticket-form-two row g-2 justify-content-center" id="tripSearchForm">
                    @if (request()->kiosk_id)
                        <input type="hidden" name="kiosk_id" value="{{ request()->kiosk_id }}">
                    @endif

                    @if (isset($selected_counter) || request()->counter_id)
                        <input type="hidden" name="counter_id" value="{{ $selected_counter ?? request()->counter_id }}">
                    @endif

                    <div class="col-12">
                        <div class="journey-point-selector">
                            <div class="form--group ticket-search-field journey-point-selector__field">
                                <label class="trip-search-label" for="ticket-pickup">@lang('From')</label>
                                <i class="las la-location-arrow"></i>
                                <select name="pickup" id="ticket-pickup" class="form--control select2">
                                    <option value="">@lang('Select Origin')</option>
                                    @foreach ($counters as $counter)
                                        <option value="{{ $counter->id }}" @selected(request('pickup', $selected_counter ?? '') == $counter->id)>
                                            {{ __($counter->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <span class="journey-point-selector__direction" aria-hidden="true">
                                <i class="las la-arrow-right"></i>
                            </span>
                            <div class="form--group ticket-search-field journey-point-selector__field">
                                <label class="trip-search-label" for="ticket-destination">@lang('To')</label>
                                <i class="las la-map-marker"></i>
                                <select name="destination" id="ticket-destination" class="form--control select2"
                                    data-default-option="@lang('All Destination')">
                                    <option value="">@lang('All Destination')</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="form--group ticket-search-field">
                            <label class="trip-search-label" for="ticket-travel-date">@lang('Departure')</label>
                            <i class="las la-calendar-check"></i>
                            <input type="text" name="date_of_journey" id="ticket-travel-date"
                                class="form--control date-range" placeholder="@lang('Departure')" autocomplete="off"
                                value="{{ $dateOfJourneyDisplay }}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form--group ticket-search-actions d-flex gap-2">
                            <button type="submit" class="btn btn--base w-100">
                                <i class="las la-search"></i> @lang('Find Trip')
                            </button>
                            <button type="button" class="btn btn-dark js-clear-trip-search"
                                aria-label="@lang('Clear search')" title="@lang('Clear search')">
                                <i class="las la-times"></i>
                            </button>
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
    <section class="ticket-section padding-bottom section-bg" id="ticketResultsRegion"
        data-journey-date="{{ Carbon::parse($date_of_journey)->format('Y-m-d') }}"
        data-available-dates='@json($availableJourneyDates)'
        data-date-statuses='@json($journeyDateStatuses)'>
        <div class="container">
            <div class="trip-results-toolbar" aria-live="polite">
                <p class="trip-results-summary">
                    @if ($trips->total() > 0)
                        <strong>{{ $trips->total() }} {{ trans_choice('departure|departures', $trips->total()) }}</strong>
                        @if ($selectedDestinationCounter)
                            @lang('to') <span>{{ $selectedDestinationCounter->name }}</span>
                        @else
                            @lang('available')
                        @endif
                    @else
                        <strong>@lang('No departures available')</strong>
                    @endif
                </p>
                <div class="trip-results-filters">
                    <label>
                        <i class="las la-bus"></i>
                        <select class="trip-result-filter" name="result_fleet_type" aria-label="@lang('Bus Type')">
                            <option value="">@lang('All Bus Types')</option>
                            @foreach ($fleetType as $fleet)
                                <option value="{{ $fleet->id }}" @selected(in_array((string) $fleet->id, array_map('strval', (array) request('fleetType', [])), true))>
                                    {{ $fleet->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <i class="las la-map-marker"></i>
                        <select class="trip-result-filter" name="result_province" aria-label="@lang('Province')">
                            <option value="">@lang('All Provinces')</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province }}" @selected(request('province') === $province)>
                                    {{ $province }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            <div class="ticket-wrapper">
                        @forelse ($trips as $trip)
                            @php
                                $start = Carbon::parse($trip->schedule->start_from);
                                $end = Carbon::parse($trip->schedule->end_at);

                                if ($end->lt($start)) {
                                    $end->addDay();
                                }

                                $requestedPickupId =
                                    (string) (request('pickup') ?: request('counter_id') ?: $trip->start_from);
                                $requestedDestinationId =
                                    (string) (request('destination') ?: request('selected_destination') ?: '');
                                $requestedDropId = $requestedDestinationId ?: (string) $trip->end_to;
                                $unavailableSeatIds = app(App\Services\SeatConflictService::class)->unavailableSeats(
                                    $trip,
                                    $date_of_journey,
                                    $requestedPickupId,
                                    $requestedDropId,
                                );
                                $available_seats_ctr = app(App\Services\SeatLayoutService::class)->availableSeatCount(
                                    $trip->fleetType,
                                    ['booked' => $unavailableSeatIds],
                                );

                                $stoppageArr = $trip->route->stoppages ?? [];
                                $routeSequence = App\Models\Counter::routeStoppages($stoppageArr);
                                $isFullyBooked = $available_seats_ctr < 1;
                                $routeStopIds = $routeSequence->pluck('id')->map(fn($id) => (string) $id)->values();
                                $displayDrop = $requestedDestinationId
                                    ? $routeSequence->first(
                                        fn($counter) => (string) $counter->id === $requestedDestinationId,
                                    )
                                    : null;
                                $displayDrop = $displayDrop ?: $trip->endTo;
                                $displayDropId = (string) $displayDrop->id;

                                $ticket_price = $ticketPrices->get(
                                    $trip->vehicle_route_id . ':' . $trip->fleet_type_id,
                                );
                                $prices = $ticket_price?->prices ?? collect();
                                $segmentPrice = null;

                                if ($requestedDestinationId) {
                                    $segmentPrice = $prices->first(function ($price) use (
                                        $requestedPickupId,
                                        $displayDropId,
                                    ) {
                                        $segment = array_values(
                                            array_map('strval', (array) ($price->source_destination ?? [])),
                                        );

                                        return $segment === [$requestedPickupId, $displayDropId] ||
                                            $segment === [$displayDropId, $requestedPickupId];
                                    });
                                }

                                $minPrice = $prices->where('price', '>', 0)->min('price') ?? 0;
                                $maxPrice = $prices->max('price') ?? $minPrice;
                                $displayPrice = $segmentPrice?->price;

                                $tripStartIndex = $routeStopIds->search((string) $trip->start_from);
                                $tripEndIndex = $routeStopIds->search((string) $trip->end_to);
                                $pickupIndex = $routeStopIds->search($requestedPickupId);
                                $dropIndex = $routeStopIds->search($displayDropId);
                                $fullTripMinutes = max((int) round($start->diffInMinutes($end)), 0);
                                $arrivalMinutes = $fullTripMinutes;
                                $displayDurationMinutes = $fullTripMinutes;

                                if ($tripStartIndex !== false && $tripEndIndex !== false && $dropIndex !== false) {
                                    $totalLegs = abs($tripEndIndex - $tripStartIndex);
                                    if ($totalLegs > 0) {
                                        $arrivalMinutes = (int) round(
                                            ($fullTripMinutes * abs($dropIndex - $tripStartIndex)) / $totalLegs,
                                        );
                                        if ($pickupIndex !== false) {
                                            $displayDurationMinutes = (int) round(
                                                ($fullTripMinutes * abs($dropIndex - $pickupIndex)) / $totalLegs,
                                            );
                                        }
                                    }
                                }

                                $durationHours = intdiv($displayDurationMinutes, 60);
                                $durationRemainder = $displayDurationMinutes % 60;
                                $displayDurationLabel =
                                    trim(
                                        ($durationHours
                                            ? $durationHours . ' ' . ($durationHours === 1 ? 'hr' : 'hrs')
                                            : '') . ($durationRemainder ? ' ' . $durationRemainder . ' mins' : ''),
                                    ) ?:
                                    '0 mins';
                                $departureAt = Carbon::parse(
                                    Carbon::parse($date_of_journey)->format('Y-m-d') .
                                        ' ' .
                                        $trip->schedule->start_from,
                                );
                                $estimatedArrivalAt = $departureAt->copy()->addMinutes($arrivalMinutes);
                                $selectSeatUrl = route('ticket.seats', [
                                    $trip->id,
                                    slug($trip->title),
                                    'start_from' => $requestedPickupId,
                                    'end_to' => $trip->end_to,
                                    'dropping_point' => $requestedDestinationId ?: $trip->end_to,
                                    'kiosk_id' => $kiosk_id,
                                    'date_of_journey' => $dateOfJourneyQuery,
                                ]);
                                $routeId = uniqid('route_');
                                $totalStops = $routeSequence?->count() ?? 0;
                                $shouldCollapse = $totalStops >= 5;
                                $availableSeatLabel = $available_seats_ctr === 1 ? 'Seat Available' : 'Seats Available';
                            @endphp

                            <div class="ticket-item js-trip-card {{ $isFullyBooked ? 'is-disabled' : '' }}"
                                data-trip-id="{{ $trip->id }}"
                                @unless ($isFullyBooked) data-href="{{ $selectSeatUrl }}" tabindex="0" role="link" @endunless
                                aria-disabled="{{ $isFullyBooked ? 'true' : 'false' }}">
                                <div class="trip-card-heading">
                                    <h5 class="trip-route-title">{{ __($trip->route->name) }}</h5>
                                    <span class="fleet-pill">
                                        <i class="las la-bus"></i> {{ __($trip->fleetType->name) }}
                                    </span>
                                </div>

                                <div class="trip-card-main">
                                    <div class="trip-card-departure">
                                        <small>@lang('Departure Time')</small>
                                        <p class="trip-time-main">{{ showDateTime($trip->schedule->start_from, 'h:i A') }}</p>
                                        <span class="trip-departure-date">
                                            <i class="las la-calendar-alt"></i>
                                            {{ Carbon::parse($date_of_journey)->format('D, M d, Y') }}
                                        </span>
                                        <span class="trip-duration-text">@lang('Approx.') {{ $displayDurationLabel }}</span>
                                    </div>

                                    <div class="trip-card-route">
                                        <small class="trip-card-section-label">@lang('Route')</small>
                                        <div class="trip-point">
                                            <span class="trip-route-dot is-origin" aria-hidden="true"></span>
                                            <div>
                                                <strong>{{ __($trip->startFrom->name) }}</strong>
                                                <small>@lang('Origin')</small>
                                            </div>
                                        </div>
                                        <div class="trip-point">
                                            <span class="trip-route-dot is-destination" aria-hidden="true"></span>
                                            <div>
                                                <strong>{{ __($displayDrop->name) }}</strong>
                                                <small>@lang('Drop-off Point')</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="trip-card-price">
                                        <small>@lang('Fare')</small>
                                        <p class="trip-price">
                                            @if ($requestedDestinationId && $displayPrice !== null)
                                                {{ showAmount($displayPrice) }}
                                            @elseif ($requestedDestinationId)
                                                <span class="trip-price-unavailable">@lang('Fare unavailable')</span>
                                            @else
                                                {{ showAmount($maxPrice) }}
                                            @endif
                                        </p>
                                        @if (!$requestedDestinationId && $minPrice > 0 && $minPrice != $maxPrice)
                                            <div class="trip-price-range">{{ showAmount($minPrice) }} -
                                                {{ showAmount($maxPrice) }}</div>
                                        @endif
                                        <span class="trip-arrival-time">@lang('Arrives') {{ $estimatedArrivalAt->format('h:i A') }}</span>
                                    </div>
                                </div>

                                <div class="trip-availability {{ $isFullyBooked ? 'is-full' : 'is-available' }}">
                                    <i class="las {{ $isFullyBooked ? 'la-times-circle' : 'la-couch' }}"></i>
                                    @if ($isFullyBooked)
                                        @lang('Fully Booked')
                                    @else
                                        {{ $available_seats_ctr }} {{ __($availableSeatLabel) }}
                                        <i class="las la-arrow-right"></i>
                                    @endif
                                </div>

                                <div class="trip-card-meta">
                                    <strong>@lang('Amenities')</strong>
                                    <span><i class="las la-chair"></i>{{ __($trip->fleetType->seat_layout) }}</span>
                                    @if ($trip->fleetType->facilities)
                                        @foreach (collect($trip->fleetType->facilities)->take(5) as $facility)
                                            <span><i class="las la-check-circle"></i>{{ __($facility) }}</span>
                                        @endforeach
                                    @endif
                                </div>

                                @if ($routeSequence && $routeSequence->count() > 0)
                                    <div class="route-details">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="d-block text-muted"
                                                style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0;">
                                                <i class="las la-map-marked-alt"></i> @lang('Route')
                                            </span>

                                            @if ($shouldCollapse)
                                                <a href="javascript:void(0)" class="route-details__toggle"
                                                    onclick="toggleRouteStops('{{ $routeId }}')"
                                                    data-trip-card-ignore>
                                                    <span id="text-{{ $routeId }}">@lang('View Route')</span>
                                                </a>
                                            @endif
                                        </div>

                                        <div class="d-flex align-items-center flex-wrap gap-2 user-select-none"
                                            style="font-size: 12px;">
                                            @foreach ($routeSequence as $stop)
                                                @if ($loop->first)
                                                    <span class="badge bg-success px-2 py-1">{{ $stop->name }}</span>

                                                    @if ($totalStops > 1)
                                                        <i class="las la-long-arrow-alt-right text-muted fs-6"></i>
                                                    @endif

                                                    @if ($shouldCollapse)
                                                        <span
                                                            class="badge bg-light text-muted border px-2 py-1 dots-{{ $routeId }}">
                                                            +{{ $totalStops - 2 }} @lang('Locations')
                                                        </span>
                                                        <i
                                                            class="las la-long-arrow-alt-right text-muted fs-6 dots-{{ $routeId }}"></i>
                                                    @endif
                                                @elseif ($loop->last)
                                                    <span class="badge bg-danger px-2 py-1">{{ $stop->name }}</span>
                                                @else
                                                    <span
                                                        class="badge bg-secondary px-2 py-1 stops-{{ $routeId }} {{ $shouldCollapse ? 'd-none' : '' }}">
                                                        {{ $stop->name }}
                                                    </span>
                                                    <i
                                                        class="las la-long-arrow-alt-right text-muted fs-6 stops-{{ $routeId }} {{ $shouldCollapse ? 'd-none' : '' }}"></i>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="ticket-item trip-item-empty">
                                <h5>{{ __($emptyMessage) }}</h5>
                            </div>
                        @endforelse

                        @if ($trips->hasPages())
                            <div class="custom-pagination">
                                {{ paginateLinks($trips) }}
                            </div>
                        @endif
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
    <script src="{{ asset('assets/global/js/dropping-points.js?v=' . buildVer()) }}"></script>
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

            const searchForm = document.getElementById('tripSearchForm');
            const mobileSearchPanel = document.querySelector('.bus-search-header');
            let tripRequestController = null;
            let tripRefreshTimer = null;
            let selectedJourneyDate = @json($dateOfJourneyDisplay);
            let journeyDateStatuses = {};

            function getResultsRegion() {
                return document.getElementById('ticketResultsRegion');
            }

            function readDateStatuses(region) {
                if (!region) return {};

                try {
                    return JSON.parse(region.dataset.dateStatuses || '{}');
                } catch (error) {
                    console.error('Unable to read trip date availability.', error);
                    return {};
                }
            }

            function buildTripSearchUrl(baseUrl) {
                const url = new URL(baseUrl || searchForm.action, window.location.origin);
                const page = baseUrl ? url.searchParams.get('page') : null;
                const params = new URLSearchParams(new FormData(searchForm));
                const region = getResultsRegion();
                const fleetType = region?.querySelector('[name="result_fleet_type"]')?.value || '';
                const province = region?.querySelector('[name="result_province"]')?.value || '';

                params.delete('fleetType');
                params.delete('fleetType[]');
                params.delete('province');

                if (fleetType) params.append('fleetType[]', fleetType);
                if (province) params.set('province', province);
                if (page) params.set('page', page);

                url.search = params.toString();
                return url;
            }

            async function refreshTripResults(url, historyMode = 'replace') {
                if (!searchForm) return;

                if (tripRequestController) tripRequestController.abort();
                tripRequestController = new AbortController();

                try {
                    const response = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        signal: tripRequestController.signal
                    });

                    if (!response.ok) throw new Error(`Trip refresh failed (${response.status})`);

                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const nextRegion = page.getElementById('ticketResultsRegion');
                    const currentRegion = getResultsRegion();

                    if (!nextRegion || !currentRegion) throw new Error('Trip results were not found in the response.');

                    currentRegion.replaceWith(nextRegion);
                    journeyDateStatuses = readDateStatuses(nextRegion);
                    tripListRealtime.journeyDate = nextRegion.dataset.journeyDate || tripListRealtime.journeyDate;

                    if (historyMode === 'push') {
                        window.history.pushState({}, '', response.url);
                    } else if (historyMode === 'replace') {
                        window.history.replaceState({}, '', response.url);
                    }

                    syncMobileSearchClearance();
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error(error);
                    }
                }
            }

            function scheduleTripRefresh(delay = 180) {
                window.clearTimeout(tripRefreshTimer);
                tripRefreshTimer = window.setTimeout(function() {
                    refreshTripResults(buildTripSearchUrl());
                }, delay);
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

                    window.clearTimeout(tripListRefreshTimer);
                    tripListRefreshTimer = window.setTimeout(function() {
                        refreshTripResults(buildTripSearchUrl(), false);
                    }, 300);
                });
            }

            $('.search-multiple').select2({
                placeholder: "Select an option"
            });

            searchForm.addEventListener('submit', function(event) {
                event.preventDefault();
                refreshTripResults(buildTripSearchUrl());
            });

            $(document).on('change', 'select[name="destination"]', function() {
                scheduleTripRefresh();
            });

            document.addEventListener('droppingPoints:updated', function() {
                scheduleTripRefresh(40);
            });

            document.addEventListener('change', function(event) {
                if (event.target.matches('.trip-result-filter')) {
                    scheduleTripRefresh(40);
                }
            });

            document.addEventListener('click', function(event) {
                const paginationLink = event.target.closest('#ticketResultsRegion .pagination a');
                if (paginationLink) {
                    event.preventDefault();
                    refreshTripResults(buildTripSearchUrl(paginationLink.href), 'push');
                    return;
                }

                const card = event.target.closest('.js-trip-card');
                if (!card || event.target.closest('a, button, [data-trip-card-ignore]')) return;

                const url = card.dataset.href;
                if (url) {
                    window.location.href = url;
                }
            });

            document.addEventListener('keydown', function(event) {
                if (!['Enter', ' '].includes(event.key)) {
                    return;
                }

                const card = event.target.closest('.js-trip-card');
                const url = card?.dataset.href;
                if (url) {
                    event.preventDefault();
                    window.location.href = url;
                }
            });

            journeyDateStatuses = readDateStatuses(getResultsRegion());

            const datePicker = $('.date-range').daterangepicker({
                autoUpdateInput: true,
                singleDatePicker: true,
                drops: window.matchMedia('(max-width: 767px)').matches ? 'up' : 'down',
                startDate: moment(@json($date_of_journey), 'YYYY-MM-DD'),
                minDate: new Date(),
                maxDate: moment().add("{{ $allowed_advance_booking_days }}", 'days'),
                locale: {
                    format: 'DD MMM YYYY'
                },
                isCustomDate: function(date) {
                    const status = journeyDateStatuses[date.format('YYYY-MM-DD')];

                    if (status === 'available') return 'trip-date-available';
                    if (status === 'no_trip') return 'trip-date-unavailable';
                    if (status === 'not_open') return 'trip-date-not-open';

                    return '';
                }

            });

            datePicker.on('show.daterangepicker', function(event, picker) {
                if (picker.container.find('.booking-date-guide').length) return;

                picker.container.append(`
                    <div class="booking-date-guide">
                        <strong>@lang('Date Guide')</strong>
                        <span><i class="is-available"></i>@lang('Open for Booking')</span>
                        <span><i class="is-unavailable"></i>@lang('No Trip Available')</span>
                        <span><i class="is-not-open"></i>@lang('Not Open for Booking')</span>
                        <span><i class="is-past"></i>@lang('Past Date')</span>
                    </div>
                `);
            });

            datePicker.on('apply.daterangepicker', function(event, picker) {
                const newJourneyDate = picker.startDate.format('DD MMM YYYY');
                this.value = newJourneyDate;

                if (newJourneyDate === selectedJourneyDate || !this.form) return;

                selectedJourneyDate = newJourneyDate;
                tripListRealtime.journeyDate = picker.startDate.format('YYYY-MM-DD');
                $(this).trigger('change');
                scheduleTripRefresh(260);
            });

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

            $('.js-clear-trip-search').on('click', function() {
                const pickup = searchForm.querySelector('[name="pickup"]');
                const destination = searchForm.querySelector('[name="destination"]');
                const kioskId = searchForm.querySelector('[name="kiosk_id"]');
                const counterId = searchForm.querySelector('[name="counter_id"]');
                const defaultPickup = kioskId ? (counterId?.value || '') : '';
                const today = moment();

                $(pickup).val(defaultPickup).trigger('change');
                $(destination).val('').trigger('change');
                $('.date-range').val(today.format('DD MMM YYYY'));
                datePicker.data('daterangepicker').setStartDate(today);
                datePicker.data('daterangepicker').setEndDate(today);
                selectedJourneyDate = today.format('DD MMM YYYY');

                const region = getResultsRegion();
                if (region) {
                    region.querySelectorAll('.trip-result-filter').forEach(function(filter) {
                        filter.value = '';
                    });
                }

                scheduleTripRefresh(260);
            });

            window.addEventListener('popstate', function() {
                refreshTripResults(new URL(window.location.href), false);
            });

        })(jQuery)
    </script>
@endpush
