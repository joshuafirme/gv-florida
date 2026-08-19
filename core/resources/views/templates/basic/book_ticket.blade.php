@section('content')
    @php
        $kiosk_id = request()->kiosk_id;
        $date_of_journey = request('date_of_journey') ? request('date_of_journey') : date('m/d/Y');
        $dateOfJourneyQuery = \Carbon\Carbon::parse($date_of_journey)->format('m/d/Y');
        $date_of_journey_formatted = formatDate($date_of_journey);
        $selectedPickupPoint = request('start_from') ?: request('pickup') ?: $trip->startFrom->id;
        $selectedDroppingPoint = request('dropping_point') ?: request('destination') ?: request('end_to') ?: $trip->endTo->id;
    @endphp
    @if ($kiosk_id)
        @php
            $layout = 'layouts.kiosk';
        @endphp
        @include('templates.basic.partials.kiosk_nav')
    @endif
    @extends($activeTemplate . $layout)

    <div class="padding-top padding-bottom booking-seat-flow {{ $kiosk_id ? 'is-kiosk' : '' }}">
        <div class="container">
            @include('templates.basic.partials.booking_stepper', ['currentStep' => 'seat'])
            <a class="seat-back-link"
                href="{{ url('/tickets?' . urldecode(http_build_query([
                    'kiosk_id' => $kiosk_id,
                    'counter_id' => $selectedPickupPoint,
                    'pickup' => $selectedPickupPoint,
                    'destination' => $selectedDroppingPoint,
                    'date_of_journey' => $dateOfJourneyQuery,
                ]))) }}">
                <i class="las la-arrow-left"></i> Go Back
            </a>
            <div class="card border-0 shadow-sm rounded-4 mb-4 trip-header-banner">
                <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">

                    <div class="header-left">
                        <h4 class="fw-bolder mb-1 text-uppercase trip-route-title">
                            {{ $trip->route->name }}
                        </h4>
                        <div class="d-flex align-items-center fw-bold trip-fleet-type">
                            <i class="las la-bus fs-5 me-1"></i>
                            {{ strtoupper($trip->fleetType->name) }}
                        </div>
                    </div>

                    <div class="header-right text-md-end text-start">
                        <h3 class="fw-bolder mb-1 trip-time">
                            {{ showDateTime($trip->schedule->start_from, 'h:i A') }}
                        </h3>
                        <div class="text-muted trip-date-duration">
                            @php
                                $journeyDate = isset($date_of_journey) ? $date_of_journey : request('date_of_journey');
                            @endphp
                            {{ \Carbon\Carbon::parse($journeyDate)->format('D, M d, Y') }}
                            &middot;
                            {{ timeDifferenceReadable($trip->schedule->start_from, $trip->schedule->end_at) }}
                        </div>
                    </div>

                </div>
            </div>
            <div class="row gx-xl-5 gy-4 gy-sm-5 justify-content-center">
                <div class="col-md-6 seat-overview-column">
                    <div class="seat-overview-wrapper card border-0 shadow-sm rounded-4" style="overflow: hidden;">
                        <form action="{{ route('ticket.book', $trip->id) }}" method="POST" id="bookingForm">
                            @csrf
                            <input type="hidden" name="kiosk_id" value="{{ request('kiosk_id') }}">
                            <input type="hidden" name="start_from_time" value="{{ $trip->schedule->start_from }}">
                            <input type="hidden" name="fleet_type_id" value="{{ $trip->fleetType->id }}">

                            <input type="hidden" name="price" value="0">
                            <input type="hidden" name="date_of_journey" value="{{ $dateOfJourneyQuery }}">
                            <input type="hidden" name="pickup_point" id="pickup_point" value="{{ $selectedPickupPoint }}">
                            <input type="hidden" name="seats">

                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-4 seat-overview-item">
                                    <div class="overview-icon-box me-3">
                                        <i class="las la-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <span class="d-block text-muted small fw-bold mb-1">@lang('Departure Date')</span>
                                        <h6 class="mb-0 fw-bold text-dark">
                                            {{ \Carbon\Carbon::parse($date_of_journey)->format('D, M d, Y') }}</h6>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center mb-4 seat-overview-item">
                                    <div class="overview-icon-box me-3">
                                        <i class="las la-clock"></i>
                                    </div>
                                    <div>
                                        <span class="d-block text-muted small fw-bold mb-1">@lang('Departure Time')</span>
                                        <h6 class="mb-0 fw-bold text-dark">
                                            {{ date('g:i A', strtotime($trip->schedule->start_from)) }}</h6>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center mb-4 seat-overview-item">
                                    <div class="overview-icon-box me-3">
                                        <i class="las la-map-marker"></i>
                                    </div>
                                    <div>
                                        <span class="d-block text-muted small fw-bold mb-1">@lang('Pickup Point')</span>
                                        <h6 class="mb-0 fw-bold text-dark text-uppercase">{{ $trip->startFrom->name }}</h6>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center seat-overview-item seat-overview-item--dropoff">
                                    <div class="overview-icon-box me-3">
                                        <i class="las la-map-marker"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="d-block text-muted small fw-bold mb-1">@lang('Dropping Point')</span>
                                        <select name="dropping_point" id="dropping_point"
                                            class="form-control select2 custom-select-ui" required>
                                            <option value="">@lang('Select Dropping Point')</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer bg-light border-0 p-4 pt-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted fw-bold">@lang('Selected Seat')</span>
                                    <span class="text-muted fw-bold selected-seat-text">None yet</span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h6 class="mb-0 text-dark fw-bold">@lang('Total Fare')</h6>
                                    <h4 class="mb-0 fw-bold total-fare-amount">{{ gs('cur_sym') }}0.00</h4>
                                </div>

                                <div class="price-error-message text-danger small fw-bold mb-2 text-center d-none"></div>

                                <button type="submit" class="w-100 book-bus-btn" style="font-size: 16px;">
                                    @lang('Continue')
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-6 seat-map-column">
                    <h6 class="title">@lang('Click on Seat to select or deselect')</h6>
                    @if ($trip->day_off)
                        <span class="fs--14px">
                            @lang('Off Days') :
                            @foreach ($trip->day_off as $item)
                                <span class="badge badge--success">
                                    {{ __(showDayOff($item)) }}
                                    @if (!$loop->last)
                                        ,
                                    @endif
                                </span>
                            @endforeach
                        </span>
                    @endif


                    <div class="seat-for-reserved">
                        <div class="seat-condition available-seat">
                            <span class="seat"><span></span></span>
                            <p>@lang('Available Seats')</p>
                        </div>
                        <div class="seat-condition selected-by-you">
                            <span class="seat"><span></span></span>
                            <p>@lang('Selected by You')</p>
                        </div>
                        <div class="seat-condition selected-by-gents">
                            <div class="seat"><span></span></div>
                            <p>@lang('Already Booked')</p>
                        </div>

                        <div class="seat-condition non-operational-seats">
                            <div class="seat"><span></span></div>
                            <p>@lang('Non-Operational Seats')</p>
                        </div>
                        <div class="seat-condition sc-pwd-legend">
                            <div class="seat"><span></span></div>
                            <p>@lang('Senior Citizen / PWD')</p>
                        </div>
                    </div>
                    @include('templates.basic.partials.seat_layout', ['fleetType' => $trip->fleetType])

                </div>
            </div>
        </div>

        {{-- confirmation modal --}}
        <div class="modal fade seat-confirm-modal" id="bookConfirm" tabindex="-1" role="dialog"
            aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="seat-confirm-icon"><i class="las la-check"></i></div>
                    <h5 class="seat-confirm-title">Confirm <span class="js-confirm-count">0</span> <span class="js-confirm-seat-word">seats</span>?</h5>
                    <p class="seat-confirm-copy">
                        You've selected <strong><span class="js-confirm-count">0</span> <span class="js-confirm-seat-word">seats</span></strong>.
                        Your selection will be reserved while you finish booking.
                    </p>
                    <div class="seat-confirm-tags js-confirm-tags"></div>
                    <div class="seat-confirm-total">
                        <span>Total Fare</span>
                        <strong class="js-confirm-total">{{ gs('cur_sym') }}0.00</strong>
                    </div>
                    <div class="seat-confirm-unit js-confirm-unit"></div>
                    <div class="seat-confirm-actions">
                        <button type="button" class="seat-confirm-secondary" data-bs-dismiss="modal">
                            Choose again
                        </button>
                        <button type="submit" class="seat-confirm-primary" id="btnBookConfirm">
                            Confirm <span class="js-confirm-seat-word">seats</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="alertModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"> @lang('Alert Message')</h5>
                        <button type="button" class="w-auto btn--close" data-bs-dismiss="modal"><i
                                class="las la-times"></i></button>
                    </div>
                    <div class="modal-body">
                        <strong>
                            <p class="error-message text-danger"></p>
                        </strong>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--danger w-auto btn--sm px-3" data-bs-dismiss="modal">
                            @lang('Continue')
                        </button>
                    </div>
                </div>
            </div>
        </div>

    @endsection

    @push('style-lib')
        <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/global/css/daterangepicker.css') }}">

        <style>
            .booking-seat-flow {
                --seat-overview-sticky-top: 173px;
                padding-bottom: 24px !important;
                padding-top: 8px !important;
            }

            .booking-seat-flow input::placeholder,
            .booking-seat-flow textarea::placeholder,
            .booking-seat-flow .select2-selection__placeholder {
                font-style: italic;
                opacity: .58;
            }

            .booking-seat-flow .container {
                max-width: 1240px;
            }

            .seat-overview-column {
                align-self: flex-start;
                position: sticky;
                top: var(--seat-overview-sticky-top);
                z-index: 1025;
            }

            .seat-overview-wrapper {
                scroll-margin-top: var(--seat-overview-sticky-top);
            }

            .seat-back-link {
                align-items: center;
                color: #7b8490;
                display: inline-flex;
                font-weight: 700;
                gap: 6px;
                margin-bottom: 10px;
                text-decoration: none;
            }

            .seat-back-link:hover,
            .seat-back-link:focus {
                color: #df2a82;
                text-decoration: none;
            }

            .trip-header-banner {
                margin-bottom: 14px !important;
            }

            .trip-header-banner .card-body,
            .booking-seat-flow .seat-overview-wrapper .card-body {
                padding: 16px !important;
            }

            .booking-seat-flow .seat-overview-wrapper .card-footer {
                padding: 14px 16px !important;
            }

            .booking-seat-flow .row {
                --bs-gutter-y: 1rem;
            }

            .booking-seat-flow h6.title {
                margin-bottom: 8px;
            }

            /* Selected by Admin for Rebooking (Green) */
            .seat.selected {
                cursor: grab;
                /* Shows the user they can drag it */
            }

            .booking-seat-flow .seat-wrapper .seat.selected,
            .booking-seat-flow .seat-for-reserved .selected-by-you .seat {
                background: var(--booking-primary);
                border-color: var(--booking-primary);
                color: var(--booking-on-primary);
            }

            .booking-seat-flow .seat-plan-inner .seat-wrapper .seat {
                border-radius: 8px;
            }

            .booking-seat-flow .seat-plan-inner .seat-wrapper .seat.comfort-room {
                border-radius: 8px;
                height: calc((var(--seat-cell-height) * var(--seat-row-span)) + (var(--seat-row-gap) * (var(--seat-row-span) - 1))) !important;
                line-height: 1 !important;
                width: 100% !important;
            }

            .booking-seat-flow .seat-for-reserved .seat {
                border-radius: 6px;
            }

            .booking-seat-flow .sc-pwd-row .seat:not(.comfort-room) {
                flex-direction: column;
                line-height: 1;
            }

            .booking-seat-flow .sc-pwd-row .seat:not(.comfort-room):not(.selected) {
                background: #ffd60a;
                border-color: #d6ad00;
                color: #342b00;
            }

            .booking-seat-flow .sc-pwd-row .seat:not(.comfort-room)::after {
                content: "SC/PWD";
                display: block;
                font-size: 7px;
                font-weight: 800;
                line-height: 1;
                margin-top: 3px;
            }

            .booking-seat-flow .sc-pwd-row .selected-by-gents .seat,
            .booking-seat-flow .sc-pwd-row .selected-by-ladies .seat,
            .booking-seat-flow .sc-pwd-row .selected-by-others .seat {
                color: #fff;
            }

            .booking-seat-flow .seat-for-reserved .sc-pwd-legend .seat {
                background: #ffd60a;
                border-color: #d6ad00;
            }

            .booking-seat-flow .seat-for-reserved .sc-pwd-legend .seat span {
                border-color: #8f7300;
            }

            .booking-seat-flow.is-kiosk .seat-plan-inner .seat-wrapper .seat:not(.comfort-room) {
                font-size: 16px;
                height: 56px;
                margin-right: 10px;
                width: 48px;
            }

            .booking-seat-flow.is-kiosk .shared-seat-layout {
                --seat-cell-height: 56px;
                --seat-cell-width: 48px;
                --seat-row-gap: 18px;
            }

            .booking-seat-flow.is-kiosk .seat-plan-inner .seat-wrapper .seat.comfort-room {
                height: calc((var(--seat-cell-height) * var(--seat-row-span)) + (var(--seat-row-gap) * (var(--seat-row-span) - 1))) !important;
                width: 100% !important;
            }

            .booking-seat-flow.is-kiosk .seat-plan-inner .seat-wrapper {
                margin-bottom: 18px;
            }

            .booking-seat-flow.is-kiosk .sc-pwd-row .seat:not(.comfort-room)::after {
                font-size: 8px;
                margin-top: 5px;
            }

            .seat.selected:active {
                cursor: grabbing;
            }

            /* Highlight the target seat when hovering over it with a dragged seat */
            .seat.drag-over {
                border: 2px dashed #28a745 !important;
                opacity: 0.8;
            }

            .seat.comfort-room {
                cursor: default;
            }

            .overview-icon-box {
                width: 34px;
                height: 34px;
                background-color: var(--booking-primary-soft);
                color: var(--booking-primary);
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
                flex-shrink: 0;
            }

            .custom-select-ui {
                border-radius: 8px;
                border: 1px solid #ddd;
            }

            .btn-continue:hover {
                background-color: var(--booking-primary-hover) !important;
            }

            .booking-seat-flow .book-bus-btn {
                background: var(--booking-primary);
                border: 1px solid var(--booking-primary);
                border-radius: 8px;
                color: var(--booking-on-primary);
                font-weight: 800;
            }

            .booking-seat-flow .book-bus-btn:not(:disabled):hover,
            .booking-seat-flow .book-bus-btn:not(:disabled):focus {
                background: var(--booking-primary-hover);
                border-color: var(--booking-primary-hover);
                color: var(--booking-on-primary);
            }

            /* Hide the old default details box since it's replaced by the new UI */
            .booked-seat-details {
                display: none !important;
            }

            .book-bus-btn:disabled,
            .book-bus-btn[disabled] {
                background-color: #d1d5db !important;
                /* Light grey */
                border-color: #d1d5db !important;
                color: #6b7280 !important;
                /* Darker grey text */
                cursor: not-allowed;
                opacity: 0.8;
                box-shadow: none;
            }

            .seat-confirm-modal .modal-content {
                border: 0;
                border-radius: 14px;
                padding: 20px;
                text-align: center;
            }

            .seat-confirm-icon {
                align-items: center;
                background: var(--booking-primary-soft);
                border-radius: 14px;
                color: var(--booking-primary);
                display: inline-flex;
                font-size: 30px;
                height: 52px;
                justify-content: center;
                margin: 0 auto 14px;
                width: 52px;
            }

            .seat-confirm-title {
                color: #111827;
                font-weight: 900;
                margin-bottom: 6px;
            }

            .seat-confirm-copy {
                color: #6b7280;
                margin: 0 auto 12px;
                max-width: 320px;
            }

            .seat-confirm-tags {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                justify-content: center;
                margin-bottom: 14px;
            }

            .seat-confirm-tags span {
                background: var(--booking-primary-soft);
                border-radius: 999px;
                color: var(--booking-primary);
                font-size: 12px;
                font-weight: 900;
                padding: 6px 10px;
            }

            .seat-confirm-total {
                align-items: center;
                background: #f8fafc;
                border-radius: 8px;
                color: #64748b;
                display: flex;
                font-weight: 700;
                justify-content: space-between;
                padding: 12px 14px;
            }

            .seat-confirm-total strong {
                color: var(--booking-primary);
                font-size: 17px;
                font-weight: 900;
            }

            .seat-confirm-unit {
                color: #98a1ad;
                font-size: 12px;
                font-weight: 700;
                margin: 8px 0 18px;
            }

            .seat-confirm-actions {
                display: grid;
                gap: 10px;
                grid-template-columns: 1fr 1fr;
            }

            .seat-confirm-primary,
            .seat-confirm-secondary {
                border: 0;
                border-radius: 8px;
                font-weight: 900;
                min-height: 44px;
            }

            .seat-confirm-primary {
                background: var(--booking-primary);
                color: var(--booking-on-primary);
            }

            .seat-confirm-primary:hover,
            .seat-confirm-primary:focus {
                background: var(--booking-primary-hover);
            }

            .total-fare-amount {
                color: var(--booking-primary);
            }

            .seat-confirm-secondary {
                background: #f1f5f9;
                color: #334155;
            }

            @media (max-width: 575px) {
                .booking-seat-flow.is-kiosk .seat-plan-inner .single {
                    padding-left: 12px;
                    padding-right: 12px;
                }

                .booking-seat-flow.is-kiosk .seat-plan-inner .seat-wrapper .seat:not(.comfort-room) {
                    height: 52px;
                    margin-right: 5px;
                    width: 42px;
                }

                .booking-seat-flow.is-kiosk .seat-plan-inner .seat-wrapper .seat.comfort-room {
                    height: calc((var(--seat-cell-height) * var(--seat-row-span)) + (var(--seat-row-gap) * (var(--seat-row-span) - 1))) !important;
                    width: 100% !important;
                }

                .booking-seat-flow.is-kiosk .shared-seat-layout {
                    --seat-cell-height: 52px;
                    --seat-cell-width: 42px;
                }

                .seat-confirm-actions {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 767px) {
                .seat-back-link {
                    font-size: 12px;
                    margin-bottom: 5px;
                }

                .trip-header-banner {
                    margin-bottom: 8px !important;
                }

                .trip-header-banner .card-body {
                    flex-wrap: nowrap !important;
                    gap: 8px !important;
                    padding: 10px 12px !important;
                }

                .trip-header-banner .header-left,
                .trip-header-banner .header-right {
                    min-width: 0;
                }

                .trip-header-banner .header-right {
                    flex: 0 0 auto;
                    text-align: right !important;
                }

                .trip-route-title {
                    font-size: 14px;
                    line-height: 1.15;
                    overflow-wrap: anywhere;
                }

                .trip-fleet-type,
                .trip-date-duration {
                    font-size: 10px;
                    line-height: 1.2;
                }

                .trip-time {
                    font-size: 17px;
                }

                .booking-seat-flow .seat-overview-wrapper .card-body,
                .booking-seat-flow .seat-overview-wrapper .card-footer {
                    padding: 10px 12px !important;
                }

                .seat-overview-item:not(.seat-overview-item--dropoff) {
                    display: none !important;
                }

                .seat-overview-item--dropoff .overview-icon-box {
                    height: 30px;
                    width: 30px;
                }

                .seat-overview-wrapper .card-footer > .d-flex {
                    margin-bottom: 8px !important;
                }

                .seat-overview-wrapper .book-bus-btn {
                    min-height: 40px;
                }

                .booking-seat-flow .row {
                    --bs-gutter-y: .75rem;
                }
            }

            @media (max-height: 760px) and (min-width: 768px) {
                .booking-seat-flow .seat-overview-wrapper .card-body,
                .booking-seat-flow .seat-overview-wrapper .card-footer {
                    padding: 9px 12px !important;
                }

                .seat-overview-item {
                    margin-bottom: 7px !important;
                }

                .seat-overview-item:last-child {
                    margin-bottom: 0 !important;
                }

                .seat-overview-item .overview-icon-box {
                    height: 29px;
                    width: 29px;
                }

                .seat-overview-item .small {
                    font-size: 10px;
                    margin-bottom: 0 !important;
                }

                .seat-overview-item h6 {
                    font-size: 12px;
                }

                .seat-overview-wrapper .card-footer > .d-flex {
                    margin-bottom: 6px !important;
                }

                .seat-overview-wrapper .book-bus-btn {
                    min-height: 38px;
                }
            }
        </style>
    @endpush

    @push('script-lib')
        <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
        <script src="{{ asset('assets/global/js/moment.min.js') }}"></script>
        <script src="{{ asset('assets/global/js/daterangepicker.min.js') }}"></script>
    @endpush

    @push('script')
        <script>
            (function($) {
                "use strict";

                const bookingFlow = document.querySelector('.booking-seat-flow');
                const bookingStepper = document.querySelector('.booking-flow-stepper');

                function synchronizeStickySeatSections() {
                    if (!bookingFlow) return;

                    const stepperBottom = bookingStepper
                        ? Math.max(Math.ceil(bookingStepper.getBoundingClientRect().bottom), 0)
                        : 0;
                    const overviewTop = stepperBottom + 8;

                    bookingFlow.style.setProperty('--seat-overview-sticky-top', `${overviewTop}px`);
                }

                window.addEventListener('load', synchronizeStickySeatSections);
                window.addEventListener('resize', synchronizeStickySeatSections);
                requestAnimationFrame(synchronizeStickySeatSections);

                if ('ResizeObserver' in window && bookingStepper) {
                    const stickySeatObserver = new ResizeObserver(synchronizeStickySeatSections);
                    stickySeatObserver.observe(bookingStepper);
                }

                // ==========================================
                // INITIALIZATION
                // ==========================================
                $(".select2").select2();

                var date_of_journey = $('input[name="date_of_journey"]').val();
                var pickup = $('input[name="pickup_point"]').val();
                var routeId = '{{ $trip->route->id }}';
                var fleetTypeId = '{{ $trip->fleetType->id }}';

                // Map counter IDs to Names safely from Blade's $routeSequence
                const routeCounters = {};
                @foreach ($routeSequence as $stop)
                    routeCounters["{{ $stop->id }}"] = "{{ $stop->name }}";
                @endforeach

                // 1. Initial Load: Call getPrice just to get stoppages and populate dropdown
                if (pickup) {
                    getPrice(routeId, fleetTypeId, pickup, '', date_of_journey, true);
                }

                // 2. Listen to Dropping Point changes
                $('select[name="dropping_point"]').on('change', function() {
                    const selectedDroppingPoint = String($(this).val() || '');
                    const backLink = document.querySelector('.seat-back-link');

                    if (selectedDroppingPoint && backLink) {
                        const backUrl = new URL(backLink.href, window.location.origin);
                        backUrl.searchParams.set('destination', selectedDroppingPoint);
                        backLink.href = backUrl.toString();
                    }

                    showBookedSeat();
                });

                // ==========================================
                // API FETCH VIA getTicketPrice
                // ==========================================
                function getPrice(routeId, fleetTypeId, sourceId, destinationId, date, isInitialLoad = false) {
                    var data = {
                        "trip_id": "{{ $trip->id }}",
                        "vehicle_route_id": routeId,
                        "fleet_type_id": fleetTypeId,
                        "source_id": sourceId,
                        "destination_id": destinationId,
                        "date": date,
                        "start_from_time": '{{ $trip->schedule->start_from }}',
                        "kiosk_id": @json(request('kiosk_id'))
                    };

                    $.ajax({
                        type: "GET",
                        url: "{{ route('ticket.get-price') }}",
                        data: data,
                        success: function(response) {
                            if (response.error) {
                                if (!isInitialLoad) notify('error', response.error);
                                return;
                            }

                            // --- INITIAL LOAD: Build Dropping Points ---
                            if (isInitialLoad) {
                                var stoppages = response.stoppages;
                                var reqSource = stoppages.indexOf(sourceId.toString());

                                let $destination = $('select[name="dropping_point"]');
                                $destination.empty();
                                let options = `<option value="">@lang('Select Dropping Point')</option>`;

                                if (reqSource !== -1) {
                                    if (response.reverse) {
                                        for (let i = reqSource - 1; i >= 0; i--) {
                                            let stopId = stoppages[i];
                                            if (routeCounters[stopId]) {
                                                options +=
                                                    `<option value="${stopId}">${routeCounters[stopId]}</option>`;
                                            }
                                        }
                                    } else {
                                        for (let i = reqSource + 1; i < stoppages.length; i++) {
                                            let stopId = stoppages[i];
                                            if (routeCounters[stopId]) {
                                                options +=
                                                    `<option value="${stopId}">${routeCounters[stopId]}</option>`;
                                            }
                                        }
                                    }
                                }

                                $destination.append(options);

                                const urlParams = new URLSearchParams(window.location.search);
                                let destParam = urlParams.get('dropping_point') || urlParams.get('destination');
                                let end_to = urlParams.get('end_to')
                                if (destParam) {
                                    setTimeout(() => {
                                        $destination.val(destParam).trigger("change");
                                    }, 100);
                                }
                                else if (end_to) {
                                    setTimeout(() => {
                                        $destination.val(end_to).trigger("change");
                                    }, 100);
                                }
                                return;
                            }

                            // --- REGULAR BOOKING FLOW: Price & Seat Locking ---
                            let fetchedPrice = parseFloat(response.price) || 0;

                            if (response.price.error || fetchedPrice <= 0) {
                                let errorMsg = response.price.error ? response.price.error :
                                    "Ticket price is not configured for this route segment.";

                                // Show notification
                                notify('error', errorMsg);

                                // Reset inputs and UI
                                $('input[name=price]').val(0);

                                // Disable button and show error above it
                                $('.book-bus-btn').prop('disabled', true);
                                $('.price-error-message')
                                    .html('<i class="las la-exclamation-circle"></i> ' + errorMsg)
                                    .removeClass('d-none');

                            } else {
                                // Valid Price - Set dynamically
                                $('input[name=price]').val(response.price);

                                // Enable button and hide error
                                $('.book-bus-btn').prop('disabled', false);
                                $('.price-error-message').html('').addClass('d-none');
                            }

                            // Lock Booked Seats Logic
                            var stoppages = response.stoppages;
                            var reqSource = stoppages.indexOf(response.reqSource.toString());
                            var reqDestination = stoppages.indexOf(response.reqDestination.toString());

                            let disableSeat = function(val, gender) {
                                let seatNode = $(`.seat-wrapper .seat[data-seat="${val}"]`).parent();
                                if (gender == 1) seatNode.addClass(
                                    'seat-condition selected-by-gents disabled');
                                else if (gender == 2) seatNode.addClass(
                                    'seat-condition selected-by-ladies disabled');
                                else seatNode.addClass('seat-condition selected-by-others disabled');
                            };

                            let requestedBounds = [Math.min(reqSource, reqDestination), Math.max(reqSource,
                                reqDestination)];

                            $.each(response.bookedSeats, function(i, v) {
                                let bookedSource = stoppages.indexOf(v.pickup_point.toString());
                                let bookedDestination = stoppages.indexOf(v.dropping_point.toString());

                                if (bookedSource === -1 || bookedDestination === -1) {
                                    return;
                                }

                                let bookedBounds = [Math.min(bookedSource, bookedDestination), Math.max(
                                    bookedSource, bookedDestination)];
                                let overlaps = Math.max(requestedBounds[0], bookedBounds[0]) < Math.min(
                                    requestedBounds[1], bookedBounds[1]);

                                if (overlaps) {
                                    $.each(v.seats || [], function(index, val) {
                                        disableSeat(val, v.gender);
                                    });
                                }
                            });

                            // --- CONFLICT CHECK: Validate pre-selected seats against new destination ---
                            let conflicts = 0;
                            $('.seat.selected').each(function() {
                                if ($(this).parent().hasClass('disabled')) {
                                    $(this).removeClass('selected').removeAttr('draggable').removeAttr(
                                        'title');
                                    conflicts++;
                                }
                            });

                            if (conflicts > 0) {
                                notify('error',
                                    'Some previously selected seats are already booked for this destination and were removed.'
                                );
                            }

                            // Update UI total fare with kept seats and new price
                            selectSeat();
                        }
                    });
                }

                // ==========================================
                // UI HELPERS & SEAT LOGIC
                // ==========================================
                function showBookedSeat() {
                    // ONLY clear the disabled/booked classes. DO NOT remove the `.selected` class.
                    $('.seat-wrapper .seat').parent().removeClass(
                        'seat-condition selected-by-ladies selected-by-gents selected-by-others disabled');

                    var destinationId = $('select[name="dropping_point"]').val();

                    if (pickup == destinationId && destinationId != '') {
                        notify('error', "@lang('Source Point and Destination Point Must Not Be Same')");
                        $('select[name="dropping_point"]').val('').trigger('change');
                        return false;
                    } else if (pickup && destinationId) {
                        getPrice(routeId, fleetTypeId, pickup, destinationId, date_of_journey, false);
                    } else {
                        // If dropping point is cleared, reset price to 0 but keep selected seats intact
                        $('input[name=price]').val(0);
                        selectSeat();
                    }
                }

                function updateFareUI() {
                    let price = parseFloat($('input[name=price]').val()) || 0;
                    let selectedSeats = $('.seat.selected');
                    let selectedCount = selectedSeats.length;
                    let total = price * selectedCount;

                    if (selectedCount > 0) {
                        let seatNames = [];
                        $.each(selectedSeats, function(i, val) {
                            seatNames.push(formatSeatName($(val).data('seat')));
                        });
                        $('.selected-seat-text').text(seatNames.join(', '));
                    } else {
                        $('.selected-seat-text').text('None yet');
                    }

                    $('.total-fare-amount').text('{{ gs('cur_sym') }}' + total.toLocaleString('en-US', {
                        minimumFractionDigits: 2
                    }));
                }

                function deckLabel(seat) {
                    let deck = $(seat).closest('.seat-plan-inner').data('deck');
                    if (deck == 1) return 'Lower Deck';
                    if (deck == 2) return 'Upper Deck';
                    return `Deck ${deck}`;
                }

                function formatSeatName(seat) {
                    return String(seat || '').trim().replace(/^\d+-/, '');
                }

                function updateConfirmModal() {
                    let price = parseFloat($('input[name=price]').val()) || 0;
                    let selectedSeats = $('.seat.selected');
                    let selectedCount = selectedSeats.length;
                    let seatWord = selectedCount === 1 ? 'seat' : 'seats';
                    let total = price * selectedSeats.length;
                    let tags = [];

                    selectedSeats.each(function() {
                        tags.push(`<span>${formatSeatName($(this).data('seat'))} &middot; ${deckLabel(this)}</span>`);
                    });

                    $('.js-confirm-count').text(selectedCount);
                    $('.js-confirm-seat-word').text(seatWord);
                    $('.js-confirm-tags').html(tags.join(''));
                    $('.js-confirm-total').text('{{ gs('cur_sym') }}' + total.toLocaleString('en-US', {
                        minimumFractionDigits: 2
                    }));
                    $('.js-confirm-unit').text(`${selectedCount} ${seatWord} x {{ gs('cur_sym') }}${price.toLocaleString('en-US', { minimumFractionDigits: 2 })}`);
                    $('#btnBookConfirm').text(`Confirm ${seatWord}`);
                }

                // Handle Seat Clicks
                $('.seat-wrapper .seat').off('click');
                $(document).on('click', '.seat-wrapper .seat', function(e) {
                    e.stopImmediatePropagation();

                    if ($(this).hasClass('disabled-seat') || $(this).hasClass('comfort-room') || $(this).parent()
                        .hasClass('disabled')) {
                        return;
                    }

                    var droppingPoint = $('select[name="dropping_point"]').val();

                    if (pickup && droppingPoint) {
                        if ($(this).hasClass('selected')) {
                            $(this).removeClass('selected');
                        } else {
                            $(this).addClass('selected');
                        }
                        selectSeat();
                    } else {
                        notify('error', "@lang('Please select a Dropping Point before selecting a seat')");
                    }
                });

                function selectSeat() {
                    let selectedSeats = $('.seat.selected');
                    let seats = '';

                    $('.seat-wrapper .seat').not('.selected').removeAttr('draggable').removeAttr('title');
                    selectedSeats.attr('draggable', true).attr('title', 'Your Seat (Drag to move)');

                    if (selectedSeats.length > 0) {
                        $.each(selectedSeats, function(i, value) {
                            seats += $(value).data('seat') + ',';
                        });
                        seats = seats.replace(/,+$/, "");
                        $('input[name=seats]').val(seats);
                    } else {
                        $('input[name=seats]').val('');
                    }

                    updateFareUI();
                }

                // ==========================================
                // SUBMISSION
                // ==========================================
                let seatValidationInProgress = false;

                function markConflictingSeats(seats) {
                    (seats || []).forEach(function(seat) {
                        let seatElement = $(`.seat-wrapper .seat[data-seat="${seat}"]`);
                        seatElement.removeClass('selected').removeAttr('draggable').removeAttr('title');
                        seatElement.parent().addClass('seat-condition selected-by-gents disabled');
                    });

                    selectSeat();
                }

                $('#bookingForm').on('submit', function(e) {
                    e.preventDefault();

                    if (seatValidationInProgress) {
                        return;
                    }

                    if ($('select[name="dropping_point"]').val() == '') {
                        notify('error', 'Please select a Dropping Point.');
                        return;
                    }

                    if ($('.seat.selected').length === 0) {
                        notify('error', 'Select at least one seat.');
                        return;
                    }

                    const form = this;
                    const continueButton = $(form).find('.book-bus-btn');
                    const originalLabel = continueButton.html();
                    seatValidationInProgress = true;
                    continueButton.prop('disabled', true)
                        .html('<i class="las la-spinner la-spin"></i> Validating seats...');

                    $.ajax({
                        type: 'POST',
                        url: "{{ route('ticket.validate-seats', $trip->id) }}",
                        data: $(form).serialize(),
                        dataType: 'json',
                        headers: {
                            Accept: 'application/json'
                        }
                    }).done(function(response) {
                        if (!response.available) {
                            notify('error', response.message || 'One or more seats are no longer available.');
                            return;
                        }

                        $('input[name="seats"]').val((response.seats || []).join(','));
                        updateConfirmModal();
                        $('#bookConfirm').modal('show');
                    }).fail(function(xhr) {
                        const response = xhr.responseJSON || {};
                        const validationError = response.errors ? Object.values(response.errors).flat()[0] : null;

                        markConflictingSeats(response.conflicting_seats || []);
                        notify('error', response.message || validationError || 'Unable to validate the selected seats.');
                    }).always(function() {
                        seatValidationInProgress = false;
                        const hasValidPrice = (parseFloat($('input[name="price"]').val()) || 0) > 0;
                        continueButton.prop('disabled', !hasValidPrice).html(originalLabel);
                    });
                });

                $(document).on('click', '#btnBookConfirm', function(e) {
                    $('#bookConfirm').modal('hide');
                    document.getElementById("bookingForm").submit();
                });

            })(jQuery);
        </script>
    @endpush
