@section('content')
    @php
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
                <form action="{{ route('search') }}" class="ticket-form ticket-form-two row g-3 justify-content-center">
                    @if (request()->kiosk_id)
                        <input type="hidden" name="kiosk_id" value="{{ request()->kiosk_id }}">
                    @endif
                    <input type="hidden" name="counter_id" value="{{ $selected_counter }}">
                    <div class="col-md-4 col-lg-3">
                        <div class="form--group">
                            <i class="las la-location-arrow"></i>
                            <select name="pickup" class="form--control select2">
                                <option value="">@lang('Pickup Point')</option>
                                @foreach ($counters as $counter)
                                    <option value="{{ $counter->id }}" @if ($selected_counter == $counter->id) selected @endif>
                                        {{ __($counter->name) }}</option>
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
                                placeholder="@lang('Date of Journey')" autocomplete="off" value="{{ request()->date_of_journey }}">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="form--group">
                            <button class="btn btn--base w-100">@lang('Find Tickets')</button>
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
                                $start = Carbon\Carbon::parse($trip->schedule->start_from);
                                $end = Carbon\Carbon::parse($trip->schedule->end_at);

                                if ($end->lt($start)) {
                                    $end->addDay();
                                }

                                $ticket = App\Models\TicketPrice::where('fleet_type_id', $trip->fleetType->id)
                                    ->where('vehicle_route_id', $trip->route->id)
                                    ->first();

                                $tickets = App\Models\BookedTicket::where('trip_id', $trip->id)
                                    ->wheredate('date_of_journey', date('Y-m-d'))
                                    ->whereIn('status', [Status::BOOKED_APPROVED, Status::BOOKED_PENDING])
                                    ->get();

                                $occupied_seats_ctr = 0;

                                foreach ($tickets as $key => $ticket) {
                                    $occupied_seats_ctr += count($ticket->seats);
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
                                    continue;
                                }
                            @endphp

                            @if ($ticket)
                                <div class="ticket-item mb-2">
                                    <div class="ticket-item-inner">
                                        <h5 class="bus-name">{{ __($trip->route->name) }}</h5>
                                        <span class="bus-info">@lang('Seat Layout - ')
                                            {{ __($trip->fleetType->seat_layout) }}</span>
                                        <span class="ratting"><i
                                                class="las la-bus"></i>{{ __($trip->fleetType->name) }}</span>
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
                                        <p class="rent mb-0">
                                            {{ __(gs('cur_sym')) }}{{ showAmount($ticket->price, currencyFormat: false) }}
                                        </p>
                                        <div class="seat-count mt-2">
                                            Available Seats: {{ $available_seats_ctr }}
                                        </div>
                                        @if ($trip->day_off)
                                            <div class="seats-left mt-2 mb-3 fs--14px">
                                                @lang('Off Days'): <div class="d-inline-flex flex-wrap" style="gap:5px">
                                                    @foreach ($trip->day_off as $item)
                                                        <span
                                                            class="badge badge--primary">{{ __(showDayOff($item)) }}</span>
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
                                            'kiosk_id' => $kiosk_id,
                                            'date_of_journey' => request('date_of_journey'),
                                        ]) }}">@lang('Select Seat')</a>


                                    @if ($trip->fleetType->facilities)
                                        <div class="ticket-item-footer">
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
                            @endif
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
        (function($) {
            "use strict";

            // $('.search').on('change', function() {
            //     $('#filterForm').submit();
            // });

            $('.select2').select2();

            $('.search-multiple').select2({
                placeholder: "Select an option" // This sets the placeholder text
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

            var windowHeight = $(window).height();
            console.log(windowHeight)

            // if (windowHeight < 920) {
            //     $('.ticket-filter').css({
            //         'overflow-y': 'auto',
            //         'height': '500px'
            //     })
            // }
        })(jQuery)
    </script>
@endpush
