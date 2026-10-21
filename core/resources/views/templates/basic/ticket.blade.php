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
        $dateOfJourneyDisplay = Carbon::parse($date_of_journey)->format('D, M j, Y');
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
            font-size: 9px;
            font-weight: 800;
            left: 48px;
            line-height: 1;
            margin: 0;
            pointer-events: none;
            position: absolute;
            text-transform: uppercase;
            top: 11px;
            z-index: 12;
        }

        .ticket-form .ticket-search-field > i {
            align-items: center;
            bottom: auto;
            color: var(--booking-primary);
            display: flex;
            font-size: 20px;
            height: 64px;
            justify-content: center;
            left: 15px;
            line-height: 1;
            padding: 0;
            pointer-events: none;
            top: 0;
            width: 22px;
        }

        .ticket-form .ticket-search-field .select2-container {
            display: block;
            height: 64px;
            width: 100% !important;
        }

        .ticket-form .ticket-search-field > .form--control,
        .ticket-form .ticket-search-field .select2-container--default .select2-selection--single {
            background: #fff;
            border: 1px solid #d7dde6 !important;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
            box-sizing: border-box;
            color: #111827;
            font-size: 18px;
            font-weight: 900;
            height: 64px;
            min-height: 64px;
            line-height: 20px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .ticket-form .ticket-search-field > .form--control {
            padding: 25px 38px 7px 48px !important;
        }

        .ticket-form .ticket-search-field > .date-range[readonly] {
            background-color: #fff;
            cursor: pointer;
        }

        .ticket-form .ticket-search-field .select2-container--default .select2-selection--single {
            padding: 0 !important;
        }

        .ticket-form .ticket-search-field > .form--control:focus,
        .ticket-form .ticket-search-field .select2-container--focus .select2-selection--single,
        .ticket-form .ticket-search-field .select2-container--open .select2-selection--single {
            border-color: var(--booking-primary) !important;
            box-shadow: 0 0 0 3px var(--booking-primary-soft);
            outline: 0;
        }

        .ticket-form .ticket-search-field .select2-selection__rendered {
            bottom: 11px;
            color: #111827;
            display: block;
            font-size: 18px;
            font-weight: 900;
            height: auto;
            left: 48px;
            line-height: 20px !important;
            margin-left: 0;
            overflow: hidden;
            padding: 0 !important;
            position: absolute;
            right: 30px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ticket-form .ticket-search-field .select2-selection__placeholder {
            color: #8a93a2;
            font-weight: 500;
        }

        .ticket-form .ticket-search-field .select2-selection__arrow {
            right: 12px;
            top: 18px;
        }

        .bus-search-header {
            border: 1px solid #e2e7ee;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
        }

        .ticket-search-actions {
            align-items: center;
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding-top: 0;
        }

        .ticket-search-actions .btn {
            border-radius: 11px;
            font-size: 15px;
            font-weight: 900;
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
            letter-spacing: 0;
            line-height: 1;
            margin: 0;
            white-space: nowrap;
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
            padding: 14px 0 10px;
        }

        .journey-point-selector {
            align-items: stretch;
            border: 1px solid #d7dde6;
            border-radius: 12px;
            column-gap: 0;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            position: relative;
        }

        .journey-point-selector__field {
            min-width: 0;
        }

        .journey-point-selector__field:first-child {
            border-right: 1px solid #e5e7eb;
        }

        .journey-point-selector .trip-search-label {
            left: 16px;
            z-index: 30;
        }

        .journey-point-selector .ticket-search-field > i {
            display: none;
        }

        .journey-point-selector .select2-selection--single {
            border: 0 !important;
            border-radius: 12px;
            box-shadow: none !important;
            height: 64px !important;
        }

        .journey-point-selector .ticket-search-field:first-child .select2-selection--single {
            border-radius: 12px 0 0 12px;
        }

        .journey-point-selector .ticket-search-field:last-child .select2-selection--single {
            border-radius: 0 12px 12px 0;
        }

        .journey-point-selector .select2-selection__rendered {
            left: 16px !important;
            right: 32px !important;
        }

        .journey-point-selector .select2-selection__arrow {
            display: none;
        }

        .journey-point-selector__field:last-child .trip-search-label {
            left: auto;
            right: 16px;
            text-align: right;
        }

        .journey-point-selector__field:last-child .select2-selection__rendered {
            left: 32px !important;
            right: 16px !important;
            text-align: right;
        }

        .journey-point-selector .select2-container--focus .select2-selection--single,
        .journey-point-selector .select2-container--open .select2-selection--single {
            background: var(--booking-primary-soft);
            box-shadow: none !important;
        }

        .journey-point-selector__direction {
            align-items: center;
            background: #fff;
            border: 1px solid #d7dde6;
            border-radius: 50%;
            color: var(--booking-primary);
            display: flex;
            box-shadow: 0 4px 10px rgba(15, 23, 42, .14);
            font-size: 21px;
            height: 44px;
            justify-content: center;
            left: 50%;
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 44px;
            z-index: 14;
        }

        .ticket-search-actions .js-clear-trip-search {
            align-items: center;
            background: transparent;
            border: 0;
            color: #8a93a0;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 4px;
            justify-content: center;
            min-height: 24px;
            padding: 0 10px;
        }

        .ticket-search-actions .js-clear-trip-search:hover,
        .ticket-search-actions .js-clear-trip-search:focus {
            color: var(--booking-primary);
        }

        .ticket-search-actions .js-clear-trip-search i {
            font-size: 14px;
        }

        .ticket-section {
            padding-top: 18px;
        }

        .trip-results-toolbar {
            align-items: center;
            background: #f6f8fa;
            border: 1px solid #e4e8ee;
            border-radius: 8px;
            display: flex;
            gap: 24px;
            justify-content: space-between;
            margin-bottom: 14px;
            min-height: 46px;
            padding: 12px 14px;
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
            min-height: 46px;
            padding: 0 12px;
            position: relative;
            transition: border-color .18s ease, box-shadow .18s ease;
            width: 220px;
        }

        .trip-results-filters label:focus-within {
            border-color: var(--booking-primary);
            box-shadow: 0 0 0 3px var(--booking-primary-focus);
        }

        .trip-results-filters i {
            color: var(--booking-primary);
            flex: 0 0 20px;
            font-size: 18px;
            line-height: 1;
            text-align: center;
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

        .trip-results-filters .select2-container {
            flex: 1 1 auto;
            min-width: 160px;
            width: 100% !important;
        }

        .trip-results-filters .select2-selection--single {
            align-items: center;
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            display: flex !important;
            height: 44px !important;
        }

        .trip-results-filters .select2-selection__rendered {
            align-items: center;
            color: #344054 !important;
            display: flex !important;
            font-size: 13px;
            font-weight: 800;
            height: 44px;
            line-height: 1.2 !important;
            padding: 0 28px 0 0 !important;
            width: 100%;
        }

        .trip-results-filters .select2-selection__arrow {
            height: 44px !important;
            right: 0 !important;
            top: 0 !important;
        }

        .trip-result-filter-dropdown {
            border: 1px solid #d7dde6 !important;
            border-radius: 8px !important;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .16);
            margin-top: 6px;
            min-width: 220px;
            overflow: hidden;
            padding: 6px;
        }

        .trip-results-filters label > .select2-container--open:not(.select2) {
            left: 0 !important;
            top: 100% !important;
            width: 220px !important;
            z-index: 1060;
        }

        .trip-results-filters label:last-child > .select2-container--open:not(.select2) {
            left: auto !important;
            right: 0 !important;
        }

        .trip-result-filter-dropdown .select2-results__options {
            max-height: 260px !important;
        }

        .trip-result-filter-dropdown .select2-results__option {
            border-radius: 6px;
            color: #344054;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.3;
            margin: 1px 0;
            padding: 10px 12px;
        }

        .trip-result-filter-dropdown .select2-results__option--selected {
            background: #f2f4f7;
            color: #111827;
        }

        .trip-result-filter-dropdown .select2-results__option--highlighted[aria-selected] {
            background: var(--booking-primary-soft);
            color: var(--booking-primary);
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
            grid-template-columns: minmax(150px, .7fr) minmax(220px, 1.25fr) minmax(230px, .85fr);
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
            min-width: 0;
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

        .trip-duration-text {
            color: #64748b;
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-top: 7px;
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
            font-size: 36px;
            line-height: 1.05;
            max-width: 100%;
            overflow-wrap: anywhere;
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

        .daterangepicker td.trip-date-fully-booked:not(.active) {
            background: #fff7e6;
            box-shadow: inset 0 0 0 1px #f4c76b;
            color: #9a6700;
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
            gap: 18px;
            margin-top: 12px;
            padding: 13px 8px 3px;
        }

        .booking-date-guide span {
            align-items: center;
            color: #64748b;
            display: inline-flex;
            font-size: 12px;
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

        .booking-date-guide i.is-fully-booked {
            background: #fff7e6;
            border-color: #f4c76b;
        }

        .booking-date-guide i.is-past {
            background: repeating-linear-gradient(135deg, #fff 0, #fff 3px, #e5e7eb 3px, #e5e7eb 5px);
        }

        .daterangepicker.single {
            border: 1px solid #dfe4ea;
            border-radius: 8px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, .16);
            padding: 14px;
            width: min(760px, calc(100vw - 24px));
        }

        .daterangepicker.single .drp-calendar.single {
            max-width: none;
            padding: 0;
            width: 100%;
        }

        .daterangepicker.single .calendar-table th {
            color: #778195;
            font-size: 12px;
            font-weight: 900;
            height: 40px;
        }

        .daterangepicker.single .calendar-table th.month {
            color: #1f2937;
            font-size: 15px;
        }

        .daterangepicker.single td {
            border-radius: 7px;
            font-size: 13px;
            font-weight: 800;
            height: 58px;
            line-height: 1.1;
            position: relative;
        }

        .daterangepicker.single td.trip-date-available::after,
        .daterangepicker.single td.trip-date-unavailable::after,
        .daterangepicker.single td.trip-date-fully-booked::after,
        .daterangepicker.single td.trip-date-not-open::after {
            display: block;
            font-size: 9px;
            font-weight: 700;
            margin-top: 2px;
        }

        .daterangepicker.single td.trip-date-available::after {
            content: "{{ __('Available') }}";
        }

        .daterangepicker.single td.trip-date-unavailable::after {
            content: "{{ __('No trip') }}";
        }

        .daterangepicker.single td.trip-date-fully-booked::after {
            content: "{{ __('Fully booked') }}";
        }

        .daterangepicker.single td.trip-date-not-open::after {
            content: "{{ __('Not open') }}";
        }

        .daterangepicker td.active,
        .daterangepicker td.active:hover {
            background: var(--booking-primary) !important;
            color: var(--booking-on-primary);
        }

        .daterangepicker td.trip-date-fully-booked.active,
        .daterangepicker td.trip-date-fully-booked.active:hover {
            background: #fff7e6 !important;
            box-shadow: inset 0 0 0 1px #f4c76b;
            color: #9a6700;
        }

        .daterangepicker td.trip-date-unavailable.active,
        .daterangepicker td.trip-date-unavailable.active:hover,
        .daterangepicker td.trip-date-not-open.active,
        .daterangepicker td.trip-date-not-open.active:hover {
            background: #f5f6f8 !important;
            color: #a0a8b3;
        }

        .ticket-item {
            overflow: hidden;
            padding: 0;
        }

        .trip-card-heading {
            margin: 0;
            padding: 12px 18px;
        }

        .trip-card-heading .trip-route-title {
            color: var(--booking-primary);
            font-size: 21px;
        }

        .trip-card-heading .fleet-pill {
            background: var(--booking-primary);
            border: 0;
            border-radius: 7px;
            color: #fff;
            line-height: 1.2;
            padding: 6px 12px;
        }

        .trip-card-heading .fleet-pill i {
            display: none;
        }

        .trip-card-main {
            padding: 13px 18px 2px;
        }

        .trip-card-departure,
        .trip-card-route,
        .trip-card-price {
            padding-bottom: 2px;
            padding-top: 2px;
        }

        .trip-card-departure > small,
        .trip-card-price > small,
        .trip-card-section-label {
            line-height: 1.2;
        }

        .trip-card-main .trip-point {
            padding-bottom: 6px;
        }

        .trip-card-main .trip-point strong {
            line-height: 1.25;
        }

        .trip-card-main .trip-point small {
            line-height: 1.2;
        }

        .trip-card-main .trip-card-price {
            border-left: 0;
        }

        .trip-card-main .trip-time-main,
        .trip-card-main .trip-price {
            color: var(--booking-primary);
            font-size: 36px;
        }

        @media screen and (min-width: 768px) and (max-width: 1199px) {
            .trip-card-main {
                grid-template-columns: minmax(130px, .65fr) minmax(190px, 1.1fr) minmax(205px, .9fr);
            }

            .trip-card-main .trip-time-main,
            .trip-card-main .trip-price {
                font-size: 32px;
            }

            .trip-card-main .trip-time-main {
                font-size: clamp(26px, 2.8vw, 32px);
            }

            .trip-card-departure,
            .trip-card-route,
            .trip-card-price {
                padding-left: 14px;
                padding-right: 14px;
            }

            .trip-card-departure {
                padding-left: 0;
            }

            .trip-card-price {
                padding-right: 0;
            }
        }

        @media screen and (min-width: 768px) and (max-width: 900px) {
            .trip-card-main {
                grid-template-columns: minmax(150px, .75fr) minmax(220px, 1.25fr);
            }

            .trip-card-main .trip-card-price {
                align-items: baseline;
                border-left: 0;
                border-top: 1px solid #e5e7eb;
                display: flex;
                flex-wrap: wrap;
                gap: 5px 12px;
                grid-column: 1 / -1;
                margin-top: 8px;
                padding: 10px 0 2px;
                text-align: left;
            }

            .trip-card-main .trip-card-price > small {
                flex-basis: 100%;
                margin-bottom: 0;
            }
        }

        .trip-duration-text {
            line-height: 1.3;
            margin-top: 8px;
        }

        .trip-availability {
            margin: 10px 18px 0;
            min-height: 42px;
            padding: 7px 14px;
        }

        .trip-availability-separator {
            font-size: 18px;
            line-height: 1;
        }

        .trip-card-meta {
            gap: 0;
            margin: 0;
            padding: 10px 18px 12px;
        }

        .trip-card-meta > strong {
            line-height: 1.2;
            margin-bottom: 5px;
        }

        .trip-card-meta span {
            color: #667080;
            font-weight: 500;
            line-height: 1.3;
            padding: 0 13px;
        }

        .trip-card-meta span:nth-of-type(1) {
            padding-left: 0;
        }

        .trip-card-meta span + span {
            border-left: 1px solid #dfe3e8;
        }

        .route-details {
            border-top: 1px solid #edf0f3;
            margin: 0;
            padding: 0;
        }

        .route-details__toggle {
            align-items: center;
            color: #8b95a3;
            display: flex;
            gap: 7px;
            justify-content: flex-end;
            line-height: 1.2;
            min-height: 34px;
            padding: 7px 18px;
            width: 100%;
        }

        .route-details__toggle i {
            font-size: 15px;
            transition: transform .2s ease;
        }

        .route-details__toggle i.is-expanded {
            transform: rotate(90deg);
        }

        .route-details__stops {
            align-items: center;
            border-top: 1px dashed #e5e7eb;
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            padding: 10px 18px;
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
                height: 56px;
            }

            .ticket-form .ticket-search-field > .form--control,
            .ticket-form .ticket-search-field .select2-selection--single {
                font-size: 16px;
                height: 56px;
            }

            .ticket-form .ticket-search-field .select2-selection__rendered {
                bottom: 8px;
                font-size: 16px;
            }

            .ticket-form .ticket-search-field .select2-selection__arrow {
                top: 13px;
            }

            .ticket-search-actions .btn {
                min-height: 46px;
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
                padding: 0;
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

            .journey-point-selector__direction {
                height: 38px;
                width: 38px;
            }

            .journey-point-selector {
                column-gap: 0;
            }

            .trip-results-toolbar {
                align-items: stretch;
                flex-direction: column;
                gap: 10px;
                padding: 12px;
            }

            .trip-results-filters {
                display: grid;
                gap: 8px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .trip-results-filters label {
                gap: 4px;
                min-width: 0;
                padding-left: 8px;
                padding-right: 6px;
                width: auto;
            }

            .trip-results-filters i {
                font-size: 16px;
            }

            .trip-results-filters .select2-container {
                min-width: 0;
            }

            .trip-results-filters .select2-selection__rendered {
                font-size: 12px;
                padding-right: 18px !important;
            }

            .trip-results-filters .select2-selection__arrow {
                width: 16px;
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
                padding: 10px 14px 2px;
            }

            .trip-card-departure,
            .trip-card-route,
            .trip-card-price {
                border: 0;
                padding: 7px 0;
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

            .trip-card-heading {
                padding: 11px 14px;
            }

            .trip-card-heading .trip-route-title {
                font-size: 16px;
            }

            .trip-card-heading .fleet-pill {
                font-size: 10px;
                padding: 5px 8px;
            }

            .trip-availability {
                margin-left: 14px;
                margin-right: 14px;
            }

            .trip-card-meta {
                padding-left: 14px;
                padding-right: 14px;
            }

            .trip-card-meta span {
                padding: 0 8px;
            }

            .route-details__toggle,
            .route-details__stops {
                padding-left: 14px;
                padding-right: 14px;
            }

            .daterangepicker.single {
                padding: 8px;
            }

            .daterangepicker.single td {
                font-size: 11px;
                height: 44px;
            }

            .daterangepicker.single td.trip-date-available::after,
            .daterangepicker.single td.trip-date-unavailable::after,
            .daterangepicker.single td.trip-date-fully-booked::after,
            .daterangepicker.single td.trip-date-not-open::after {
                font-size: 7px;
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
                    class="ticket-form ticket-form-two row g-2 justify-content-center {{ $kiosk_id ? '' : 'js-passenger-reminder-form' }}"
                    id="tripSearchForm">
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
                                    data-default-option="@lang('Select Destination')">
                                    <option value="">@lang('Select Destination')</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form--group ticket-search-field">
                            <label class="trip-search-label" for="ticket-travel-date">@lang('Departure')</label>
                            <i class="las la-calendar-check"></i>
                            <input type="text" name="date_of_journey" id="ticket-travel-date"
                                class="form--control date-range" placeholder="@lang('Departure')" autocomplete="off"
                                value="{{ $dateOfJourneyDisplay }}" readonly aria-readonly="true" aria-haspopup="dialog">
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form--group ticket-search-actions">
                            <button type="submit" class="btn btn--base w-100">
                                <i class="las la-search"></i> @lang('Find Trip')
                            </button>
                            <button type="button" class="js-clear-trip-search"
                                aria-label="@lang('Clear search')" title="@lang('Clear search')">
                                <i class="las la-times-circle" aria-hidden="true"></i> <span>@lang('Clear')</span>
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

            <div class="trip-list-region" id="tripListRegion" aria-live="polite" aria-busy="false">
                @include('templates.basic.partials.trip-list')
            </div>
        </div>
    </section>
    @unless ($kiosk_id)
        @include($activeTemplate . 'partials.passenger-reminders-modal')
    @endunless
@endsection

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/global/css/daterangepicker.css') }}">
@endpush
@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/dropping-points.js') }}"></script>
    @if (config('services.pusher.key'))
        <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    @endif
@endpush

@push('script')
    <script>
        function toggleRouteStops(id) {
            const stops = document.getElementById(id);
            const textElem = document.getElementById('text-' + id);
            const icon = document.getElementById('icon-' + id);

            if (!stops || !textElem) return;

            const isHidden = stops.classList.toggle('d-none');
            textElem.innerText = isHidden ? "@lang('View Route')" : "@lang('Hide Route')";
            icon?.classList.toggle('is-expanded', !isHidden);
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

            const searchForm = document.getElementById('tripSearchForm');
            const mobileSearchPanel = document.querySelector('.bus-search-header');
            const pickupSelect = document.getElementById('ticket-pickup');
            const destinationSelect = document.getElementById('ticket-destination');
            const travelDateInput = document.getElementById('ticket-travel-date');
            const tripListRegion = document.getElementById('tripListRegion');
            const requestedDestination = String(@json(request('destination') ?: request('selected_destination') ?: ''));
            const droppingPointsEndpoint = @json(route('getDroppingPoints', ['counter_id' => '__COUNTER__']));
            let tripRequestController = null;
            let destinationRequestController = null;
            let lastSuccessfulTripMarkup = tripListRegion.innerHTML;
            let selectedJourneyDate = @json($dateOfJourneyDisplay);
            let journeyDateStatuses = readDateStatuses();
            let datePicker = null;

            function getResultsRegion() {
                return document.getElementById('ticketResultsRegion');
            }

            function readDateStatuses() {
                try {
                    return JSON.parse(getResultsRegion()?.dataset.dateStatuses || '{}');
                } catch (error) {
                    console.error('Unable to read trip date availability.', error);
                    return {};
                }
            }

            function initializeResultFilters() {
                $('.trip-result-filter').each(function() {
                    const filter = $(this);

                    if (filter.hasClass('select2-hidden-accessible')) return;

                    filter.select2({
                        dropdownParent: filter.closest('label'),
                        dropdownCssClass: 'trip-result-filter-dropdown',
                        minimumResultsForSearch: Infinity,
                        width: 'resolve'
                    });
                });
            }

            function syncTripStartOverNavigation(url = new URL(window.location.href)) {
                const navigation = document.querySelector('[data-trip-start-over]');
                if (!navigation) return;

                const filterKeys = ['pickup', 'destination', 'date_of_journey', 'fleetType[]', 'province'];
                const hasFilters = filterKeys.some(function(key) {
                    return url.searchParams.getAll(key).some(value => String(value).trim() !== '');
                });
                const shell = navigation.closest('.booking-flow-stepper-shell');
                const stepper = navigation.closest('.booking-flow-stepper');

                navigation.classList.toggle('d-none', !hasFilters);
                shell?.classList.toggle('has-navigation', hasFilters);
                stepper?.classList.toggle('has-navigation', hasFilters);
            }

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

            function deleteArrayUrlParam(url, name) {
                Array.from(new Set(url.searchParams.keys())).forEach(function(key) {
                    if (key === name || key === `${name}[]` || key.startsWith(`${name}[`)) {
                        url.searchParams.delete(key);
                    }
                });
            }

            function buildTripListUrl(baseUrl = window.location.href, preservePage = false) {
                const url = new URL(baseUrl, window.location.href);
                const kioskInput = searchForm.querySelector('input[name="kiosk_id"]');
                const counterInput = searchForm.querySelector('input[name="counter_id"]');
                const fleetType = document.querySelector('[name="result_fleet_type"]')?.value || '';
                const province = document.querySelector('[name="result_province"]')?.value || '';

                setUrlParam(url, 'pickup', pickupSelect.value);
                setUrlParam(url, 'destination', destinationSelect.value);
                setUrlParam(url, 'date_of_journey', travelDateInput.value);
                setUrlParam(url, 'kiosk_id', kioskInput ? kioskInput.value : '');
                deleteArrayUrlParam(url, 'fleetType');
                if (fleetType) url.searchParams.append('fleetType[]', fleetType);
                setUrlParam(url, 'province', province);
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

                    syncTripStartOverNavigation(new URL(payload.url));
                    syncMobileSearchClearance();
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
                scheduleEvent: @json(App\Services\ScheduleBoardBroadcaster::SCHEDULE_DATA_EVENT),
                journeyDate: @json(\Carbon\Carbon::parse($date_of_journey)->format('Y-m-d')),
            };
            let tripListRefreshTimer = null;

            function refreshLiveTripAvailability(event) {
                if (event?.date_of_journey && String(event.date_of_journey) !== tripListRealtime.journeyDate) {
                    return;
                }

                window.clearTimeout(tripListRefreshTimer);
                tripListRefreshTimer = window.setTimeout(function() {
                    refreshTripList(buildTripListUrl(window.location.href, true), {
                        history: 'none',
                        skeleton: false
                    });
                }, 300);
            }

            if (tripListRealtime.key && typeof window.Pusher !== 'undefined') {
                const tripListPusher = new Pusher(tripListRealtime.key, {
                    cluster: tripListRealtime.cluster || 'ap1'
                });
                const tripListChannel = tripListPusher.subscribe(tripListRealtime.channel);

                tripListChannel.bind(tripListRealtime.event, refreshLiveTripAvailability);
                tripListChannel.bind(tripListRealtime.scheduleEvent, refreshLiveTripAvailability);
            }

            window.setInterval(function() {
                if (!document.hidden && !tripRequestController) {
                    refreshTripList(buildTripListUrl(window.location.href, true), {
                        history: 'none',
                        skeleton: false
                    });
                }
            }, 30000);

            $(pickupSelect).on('change', function() {
                refreshForRouteChange(destinationSelect.value);
            });

            $(destinationSelect).on('change', function() {
                refreshTripList(buildTripListUrl());
            });

            $(document).on('change', '.trip-result-filter', function() {
                refreshTripList(buildTripListUrl());
            });

            $(searchForm).on('submit', function(event) {
                event.preventDefault();

                const selectedDate = moment(travelDateInput.value, 'ddd, MMM D, YYYY', true);
                const selectedStatus = selectedDate.isValid()
                    ? journeyDateStatuses[selectedDate.format('YYYY-MM-DD')]
                    : null;

                if (selectedStatus !== 'available') {
                    reportTripListError('@lang('The selected travel date is no longer available for booking.')');
                    return;
                }

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

                const card = event.target.closest('.js-trip-card');
                if (!card || event.target.closest('a, button, [data-trip-card-ignore]')) return;

                const url = card.dataset.href;
                if (url) {
                    window.location.href = url;
                }
            });

            $(document).on('keydown', '#tripListRegion .js-trip-card', function(event) {
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

            initializeResultFilters();

            datePicker = $('.date-range').daterangepicker({
                autoUpdateInput: true,
                singleDatePicker: true,
                drops: window.matchMedia('(max-width: 767px)').matches ? 'up' : 'down',
                startDate: moment(@json($date_of_journey), 'YYYY-MM-DD'),
                minDate: new Date(),
                maxDate: moment().add("{{ $allowed_advance_booking_days }}", 'days'),
                locale: {
                    format: 'ddd, MMM D, YYYY'
                },
                isCustomDate: function(date) {
                    const status = journeyDateStatuses[date.format('YYYY-MM-DD')];

                    if (status === 'available') return 'trip-date-available';
                    if (status === 'fully_booked') return 'trip-date-fully-booked';
                    if (status === 'no_trip') return 'trip-date-unavailable';
                    if (status === 'not_open') return 'trip-date-not-open';

                    return '';
                },
                isInvalidDate: function(date) {
                    return journeyDateStatuses[date.format('YYYY-MM-DD')] !== 'available';
                }

            });

            datePicker.on('show.daterangepicker', function(event, picker) {
                if (picker.container.find('.booking-date-guide').length) return;

                picker.container.append(`
                    <div class="booking-date-guide">
                        <strong>@lang('Date Guide')</strong>
                        <span><i class="is-available"></i>@lang('Open for Booking')</span>
                        <span><i class="is-fully-booked"></i>@lang('Fully Booked')</span>
                        <span><i class="is-unavailable"></i>@lang('No Trip Available')</span>
                        <span><i class="is-not-open"></i>@lang('Closed / Not Open for Booking')</span>
                        <span><i class="is-past"></i>@lang('Past Date')</span>
                    </div>
                `);
            });

            datePicker.on('apply.daterangepicker', function(event, picker) {
                const dateKey = picker.startDate.format('YYYY-MM-DD');
                if (journeyDateStatuses[dateKey] !== 'available') {
                    const previousDate = moment(selectedJourneyDate, 'ddd, MMM D, YYYY');
                    picker.setStartDate(previousDate);
                    picker.setEndDate(previousDate);
                    this.value = selectedJourneyDate;

                    if (typeof notify === 'function') {
                        notify('error', "@lang('The selected travel date is not available for booking.')");
                    }
                    return;
                }

                const newJourneyDate = picker.startDate.format('ddd, MMM D, YYYY');
                this.value = newJourneyDate;

                if (newJourneyDate === selectedJourneyDate) {
                    return;
                }

                selectedJourneyDate = newJourneyDate;
                tripListRealtime.journeyDate = dateKey;
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

                $(pickup).val(defaultPickup).trigger('change.select2');
                $(destination).val('').trigger('change.select2');
                $('.trip-result-filter').val('').trigger('change.select2');
                $('.date-range').val(today.format('ddd, MMM D, YYYY'));
                datePicker.data('daterangepicker').setStartDate(today);
                datePicker.data('daterangepicker').setEndDate(today);
                selectedJourneyDate = today.format('ddd, MMM D, YYYY');
                tripListRealtime.journeyDate = today.format('YYYY-MM-DD');

                const clearUrl = buildTripListUrl();
                window.history.replaceState({}, '', clearUrl);
                refreshForRouteChange('');
            });

            $('.reset-button').on('click', function() {
                const form = $(this).closest('form');
                form.find('.search').prop('checked', false);
                form.trigger('submit');
            });

            syncTripStartOverNavigation();

        })(jQuery)
    </script>
@endpush
