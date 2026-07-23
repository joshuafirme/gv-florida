@section('content')

    @php
        use Carbon\Carbon;
        $kiosk_id = request()->kiosk_id;
        $allowed_advance_booking_days = getAllowedAdvanceBookingDays($kiosk_id);
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
            margin-bottom: 16px !important;
            padding: 20px;
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
        .trip-card-actions,
        .trip-card-footer {
            position: relative;
            z-index: 2;
        }

        .trip-card-top {
            align-items: flex-start;
            display: grid;
            gap: 16px;
            grid-template-columns: 1fr auto;
        }

        .trip-route-title {
            color: #07162f;
            font-size: 20px;
            font-weight: 900;
            letter-spacing: .01em;
            line-height: 1.2;
            margin: 0 0 18px;
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
            color: #718096;
            font-size: 14px;
            font-weight: 700;
            margin-top: 8px;
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
            margin-bottom: 20px;
            padding: 6px 12px;
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

        .trip-card-route {
            align-items: center;
            background: #f8fafc;
            border-radius: 12px;
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr auto 1fr;
            margin-top: 18px;
            padding: 14px 16px;
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
            margin-top: 14px;
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
            margin-top: 16px;
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
            min-height: 50px;
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

        .trip-card-footer {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            margin-top: 12px;
            text-align: center;
        }

        .route-details {
            border-top: 1px dashed #e5e7eb;
            margin-top: 14px;
            padding-top: 12px;
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
            .ticket-item {
                padding: 16px;
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

            .trip-point--end {
                text-align: left;
            }

            .trip-route-arrow {
                transform: rotate(90deg);
            }
        }
    </style>

    <div class="ticket-search-bar bg_img padding-top"
        style="background: url({{ getImage('assets/templates/basic/images/search_bg.jpg') }}) left center;">
        <div class="container">
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
                        <div class="form--group">
                            <i class="las la-location-arrow"></i>
                            <select name="pickup" class="form--control select2">
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
                        <div class="form--group">
                            <i class="las la-map-marker"></i>
                            <select name="destination" class="form--control select2"
                                data-default-option="@lang('All Destination')">
                                <option value="">@lang('All Destination')</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 col-lg-3">
                        <div class="form--group">
                            <i class="las la-calendar-check"></i>
                            <input type="text" name="date_of_journey" class="form--control date-range"
                                placeholder="@lang('Date of Journey')" autocomplete="off" value="{{ $dateOfJourneyQuery }}">
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="form--group d-flex gap-2">
                            <button type="submit" class="btn btn--base w-100">@lang('Find Tickets')</button>
                            <a href="{{ route('ticket', ['kiosk_id' => request()->kiosk_id, 'counter_id' => request()->counter_id]) }}"
                                class="btn btn-dark w-100 d-flex align-items-center justify-content-center">
                                @lang('Clear')
                            </a>
                        </div>
                    </div>
                </form>
                <div class="d-lg-none row d-flex justify-content-center">
                    <div class="col-md-6">
                        <button class="btn btn--base w-100" data-bs-toggle="offcanvas" data-bs-target="#filterPanel">
                            @php
                                $fleetTypes = request('fleetType') ?? [];
                                $count = count($fleetTypes);
                            @endphp
                            <i class="las la-filter"></i> Filters {{ $count ? "($count)" : '' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterPanel">
        <div class="offcanvas-header">
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body">


            @include('templates.basic.partials.ticket-filter')

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

                                $tickets = App\Models\BookedTicket::where('trip_id', $trip->id)
                                    ->whereDate('date_of_journey', Carbon::parse($date_of_journey)->format('Y-m-d'))
                                    ->where(function ($query) {
                                        $query->where('status', Status::BOOKED_APPROVED)->orWhere(function ($subQuery) {
                                            $subQuery
                                                ->where('status', Status::BOOKED_PENDING)
                                                ->whereHas('deposit', function ($depositQuery) {
                                                    $depositQuery->where(
                                                        'created_at',
                                                        '>=',
                                                        Carbon::now()->subMinutes(15),
                                                    );
                                                });
                                        });
                                    })
                                    ->get();

                                $occupied_seats_ctr = 0;

                                foreach ($tickets as $key => $ticket) {
                                    if ($ticket->seats) {
                                        $occupied_seats_ctr += count($ticket->seats);
                                    }
                                }

                                $available_seats_ctr = 0;
                                $deck_seats = $trip->fleetType->deck_seats;
                                $deck_seats = (int) $deck_seats[0];
                                if ($trip->fleetType->deck == 2) {
                                    $deck_seats += (int) $trip->fleetType->deck_seats[1];
                                }
                                $available_seats_ctr = $deck_seats - $occupied_seats_ctr;
                                if ($trip->fleetType->cr_position) {
                                    $available_seats_ctr -= (int) $trip->fleetType->cr_row_covered;
                                }
                                $available_seats_ctr = max($available_seats_ctr, 0);

                                $stoppageArr = $trip->route->stoppages ?? [];
                                $routeSequence = App\Models\Counter::routeStoppages($stoppageArr);
                                $isFullyBooked = $available_seats_ctr < 1;
                                $selectSeatUrl = route('ticket.seats', [
                                    $trip->id,
                                    slug($trip->title),
                                    'start_from' => $trip->start_from,
                                    'end_to' => $trip->end_to,
                                    'dropping_point' => request('destination'),
                                    'kiosk_id' => $kiosk_id,
                                    'date_of_journey' => $dateOfJourneyQuery,
                                ]);
                                $ticket_price = $trip->ticketPrice;
                                $prices = $ticket_price?->prices ?? collect();
                                $minPrice = $prices->where('price', '>', 0)->min('price') ?? 0;
                                $maxPrice = $prices->max('price') ?? $minPrice;
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
                                        <p class="trip-time-main">{{ showDateTime($trip->schedule->start_from, 'h:i A') }}</p>
                                        <div class="trip-duration">
                                            {{ timeDifferenceReadable($trip->schedule->start_from, $trip->schedule->end_at) }}
                                            &middot; arrives ~ {{ showDateTime($trip->schedule->end_at, 'h:i A') }}
                                        </div>
                                    </div>
                                    <div class="trip-card-price">
                                        <span class="fleet-pill">
                                            <i class="las la-bus"></i> {{ __($trip->fleetType->name) }}
                                        </span>
                                        <p class="trip-price">
                                            @if ($minPrice > 0 && $minPrice != $maxPrice)
                                                {{ showAmount($maxPrice) }}
                                            @else
                                                {{ showAmount($maxPrice) }}
                                            @endif
                                        </p>
                                        @if ($minPrice > 0 && $minPrice != $maxPrice)
                                            <div class="trip-price-range">{{ showAmount($minPrice) }} - {{ showAmount($maxPrice) }}</div>
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
                                        <strong>{{ __($trip->endTo->name) }}</strong>
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

                                <div class="trip-card-footer">
                                    @lang('Travel date') &middot; {{ Carbon::parse($date_of_journey)->format('D, M d, Y') }}
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
                                                    <span id="text-{{ $routeId }}">@lang('View Stops')</span>
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
                                                            +{{ $totalStops - 2 }} @lang('Stops')
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
                textElem.innerText = "@lang('Hide Stops')";
            } else {
                // Collapse
                stops.forEach(el => el.classList.add('d-none'));
                dots.forEach(el => el.classList.remove('d-none'));
                textElem.innerText = "@lang('View Stops')";
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
                $('.search').attr('checked', false);
                $('.search').val(null).trigger('change');
                $('#filterForm').submit();
            })

        })(jQuery)
    </script>
@endpush
