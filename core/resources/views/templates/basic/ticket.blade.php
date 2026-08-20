@section('content')

    @php
        use Carbon\Carbon;
        $kiosk_id = request()->kiosk_id;
        $allowed_advance_booking_days = getAllowedAdvanceBookingDays($kiosk_id);
        $advance_window_end = now()->startOfDay()->addDays($allowed_advance_booking_days);
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
            display: block;
            margin: 0 0 7px;
            color: #1f2937;
            font-size: 14px;
            font-weight: 700;
        }

        .ticket-form .ticket-search-field > i {
            align-items: center;
            bottom: 0;
            display: flex;
            height: 40px;
            justify-content: center;
            left: 8px;
            line-height: 1;
            padding: 0;
            pointer-events: none;
            top: auto;
            width: 20px;
        }

        .ticket-form .ticket-search-field > .form--control,
        .ticket-form .ticket-search-field .select2-selection--single {
            padding-left: 38px !important;
        }

        .ticket-form .ticket-search-field .select2-selection__rendered {
            margin-left: 0;
            padding-left: 0;
        }

        .ticket-search-actions {
            padding-top: 27px;
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
    </style>
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
                            <label class="trip-search-label" for="ticket-travel-date">@lang('Travel Date')</label>
                            <i class="las la-calendar-check"></i>
                            <input type="text" name="date_of_journey" id="ticket-travel-date"
                                class="form--control date-range" placeholder="@lang('Date of Journey')" autocomplete="off"
                                value="{{ $dateOfJourneyQuery }}">
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
                                @unless ($isFullyBooked) data-href="{{ $selectSeatUrl }}" tabindex="0" role="link" @endunless
                                aria-disabled="{{ $isFullyBooked ? 'true' : 'false' }}">
                                <div class="trip-card-top">
                                    <div>
                                        <h5 class="trip-route-title">{{ __($trip->route->name) }}</h5>
                                        <p class="trip-time-main">{{ showDateTime($trip->schedule->start_from, 'h:i A') }}
                                        </p>
                                        <div class="trip-duration">
                                            <span class="trip-duration__item">
                                                <i class="las la-calendar-alt"></i>
                                                {{ Carbon::parse($date_of_journey)->format('D, M d, Y') }}
                                            </span>
                                            <span class="trip-duration__separator" aria-hidden="true"></span>
                                            <span class="trip-duration__item">
                                                <i class="las la-clock"></i>
                                                {{ $displayDurationLabel }}
                                            </span>
                                            <span class="trip-duration__separator" aria-hidden="true"></span>
                                            <span class="trip-duration__item">
                                                @lang('Arrives') {{ $estimatedArrivalAt->format('h:i A') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="trip-card-price">
                                        <span class="fleet-pill">
                                            <i class="las la-bus"></i> {{ __($trip->fleetType->name) }}
                                        </span>
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
                                    </div>
                                </div>

                                <div class="trip-card-route">
                                    <div class="trip-point">
                                        <small>@lang('Pickup')</small>
                                        <strong>{{ __($trip->startFrom->name) }}</strong>
                                    </div>
                                    <div class="trip-route-arrow">
                                        <i class="las la-arrow-right"></i>
                                    </div>
                                    <div class="trip-point trip-point--end">
                                        <small>@lang('Drop-off')</small>
                                        <strong>{{ __($displayDrop->name) }}</strong>
                                    </div>
                                </div>

                                <div class="trip-card-meta">
                                    <span><i class="las la-chair"></i>{{ __($trip->fleetType->seat_layout) }}</span>
                                    @if ($trip->fleetType->facilities)
                                        @foreach (collect($trip->fleetType->facilities)->take(5) as $facility)
                                            <span><i class="las la-check-circle"></i>{{ __($facility) }}</span>
                                        @endforeach
                                    @endif
                                </div>

                                <div class="trip-card-actions">
                                    <div class="trip-availability {{ $isFullyBooked ? 'is-full' : 'is-available' }}">
                                        <i class="las {{ $isFullyBooked ? 'la-times-circle' : 'la-couch' }}"></i>
                                        @if ($isFullyBooked)
                                            @lang('Fully Booked')
                                        @else
                                            {{ $available_seats_ctr }} {{ __($availableSeatLabel) }}
                                        @endif
                                    </div>

                                    @if ($isFullyBooked)
                                        <span class="trip-select-btn is-disabled">@lang('Unavailable')</span>
                                    @else
                                        <a class="trip-select-btn" href="{{ $selectSeatUrl }}">@lang('Select Seat')</a>
                                    @endif
                                </div>

                                @if ($routeSequence && $routeSequence->count() > 0)
                                    <div class="route-details">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="d-block text-muted"
                                                style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
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

            // 1 minute in milliseconds
            const IDLE_TIMEOUT = 60000;
            let idleTimer;

            function reloadPage() {
                window.location.reload();
            }

            function resetTimer() {
                clearTimeout(idleTimer);
                idleTimer = setTimeout(reloadPage, IDLE_TIMEOUT);
            }

            const activityEvents = [
                'mousemove',
                'mousedown',
                'keydown',
                'scroll',
                'touchstart',
                'click'
            ];

            activityEvents.forEach(function(event) {
                document.addEventListener(event, resetTimer, true);
            });

            resetTimer();

            $('.select2').select2();

            const requestedDestination = String(@json(request('destination') ?: request('selected_destination') ?: ''));
            let destinationSubmitting = false;

            $('select[name="destination"]').on('change', function() {
                const selectedDestination = String($(this).val() || '');
                if (destinationSubmitting || selectedDestination === requestedDestination) {
                    return;
                }

                destinationSubmitting = true;
                this.form.submit();
            });

            $('.search-multiple').select2({
                placeholder: "Select an option"
            });

            $('.js-trip-card').on('click', function(event) {
                if ($(event.target).closest('a, button, [data-trip-card-ignore]').length) {
                    return;
                }

                const url = $(this).data('href');
                if (url) {
                    window.location.href = url;
                }
            });

            $('.js-trip-card').on('keydown', function(event) {
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
                minDate: new Date(),
                maxDate: moment().add("{{ $allowed_advance_booking_days }}", 'days')

            })


            $('.reset-button').on('click', function() {
                const form = $(this).closest('form');
                form.find('.search').prop('checked', false);
                form.find('.search').val(null).trigger('change');
                form.trigger('submit');
            })

        })(jQuery)
    </script>
@endpush
