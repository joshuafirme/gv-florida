@section('content')

    @php
        use Carbon\Carbon;
        $kiosk_id = request()->kiosk_id;
        $allowed_advance_booking_days = getAllowedAdvanceBookingDays();
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* Seats badge */
        .seat-count {
            font-weight: 600;
            font-size: 14px;
            background: #eef6ff;
            color: #0d6efd;
            padding: 4px 10px;
            border-radius: 20px;
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
                            <select name="destination" class="form--control select2">
                                <option value="">@lang('Dropping Point')</option>
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
                                if ($available_seats_ctr < 1) {
                                    //  continue;
                                }

                                $stoppageArr = $trip->route->stoppages ?? [];
                                $routeSequence = App\Models\Counter::routeStoppages($stoppageArr);
                            @endphp

                            <div class="ticket-item mb-2">
                                <div class="ticket-item-inner">
                                    <h5 class="bus-name">{{ __($trip->route->name) }}</h5>
                                    <span class="bus-info">@lang('Seat Layout - ') {{ __($trip->fleetType->seat_layout) }}</span>
                                    <span class="ratting"><i class="las la-bus"></i>{{ __($trip->fleetType->name) }}</span>
                                </div>

                                <div class="ticket-item-inner travel-time">
                                    <div class="bus-time">
                                        <p class="time">{{ showDateTime($trip->schedule->start_from, 'h:i A') }}</p>
                                        <p class="place">{{ __($trip->startFrom->name) }}</p>
                                    </div>
                                    <div class=" bus-time">
                                        <i class="las la-arrow-right"></i>
                                        <p>{{ timeDifferenceReadable($trip->schedule->start_from, $trip->schedule->end_at) }}
                                        </p>
                                    </div>
                                    <div class=" bus-time">
                                        <p class="time">{{ showDateTime($trip->schedule->end_at, 'h:i A') }}</p>
                                        <p class="place">{{ __($trip->endTo->name) }}</p>
                                    </div>
                                </div>

                                <div class="ticket-item-inner book-ticket">
                                    @php
                                        $ticket_price = $trip->ticketPrice;
                                        // Filter out the 0 values (Origin to Origin) to get the true minimum price
                                        $minPrice = $ticket_price->prices->where('price', '>', 0)->min('price') ?? 0;
                                        $maxPrice = $ticket_price->prices->max('price') ?? $item->price;
                                    @endphp

                                    <p class="rent mb-0">
                                        @if ($minPrice > 0 && $minPrice != $maxPrice)
                                            {{ showAmount($minPrice) }} - {{ showAmount($maxPrice) }}
                                        @else
                                            {{ showAmount($maxPrice) }}
                                        @endif
                                    </p>

                                    <div class="seat-count mt-2">
                                        Available Seats: {{ $available_seats_ctr }}
                                    </div>

                                    @if ($trip->day_off)
                                        <div class="seats-left mt-2 mb-3 fs--14px">
                                            @lang('Off Days'): <div class="d-inline-flex flex-wrap" style="gap:5px">
                                                @foreach ($trip->day_off as $item)
                                                    <span class="badge badge--primary">{{ __(showDayOff($item)) }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        @lang('Every day available')
                                    @endif
                                </div>

                                <a class="btn btn--base"
                                    href="{{ route('ticket.seats', [
                                        $trip->id,
                                        slug($trip->title),
                                        'start_from' => $trip->start_from,
                                        'end_to' => $trip->end_to,
                                        'dropping_point' => request('destination'),
                                        'kiosk_id' => $kiosk_id,
                                        'date_of_journey' => $dateOfJourneyQuery,
                                    ]) }}">@lang('Select Seat')</a>

                                @if ($routeSequence && $routeSequence->count() > 0)
                                    @php
                                        $routeId = uniqid('route_');
                                        $totalStops = $routeSequence->count();
                                        // Only collapse if there are 5 or more total stops (Origin + 3 Intermediate + Destination)
                                        $shouldCollapse = $totalStops >= 5;
                                    @endphp

                                    <div class="w-100 mt-4 pt-3" style="border-top: 1px dashed #e5e5e5; flex-basis: 100%;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="d-block text-muted"
                                                style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                                                <i class="las la-map-marked-alt"></i> @lang('Route')
                                            </span>

                                            @if ($shouldCollapse)
                                                <a href="javascript:void(0)" class="text-primary text-decoration-none"
                                                    onclick="toggleRouteStops('{{ $routeId }}')"
                                                    style="font-size: 11px; font-weight: 600;">
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
                                @if ($trip->fleetType->facilities)
                                    <div class="ticket-item-footer mt-3 w-100" style="flex-basis: 100%;">
                                        <div class="d-flex content-justify-center">
                                            <span>
                                                <strong>@lang('Amenities - ')</strong>
                                                @foreach ($trip->fleetType->facilities as $item)
                                                    <span class="facilities">{{ __($item) }}</span>
                                                @endforeach
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="ticket-item">
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
