@section('content')
    @php
        $kiosk_id = request()->kiosk_id;
        $date_of_journey = request('date_of_journey') ? request('date_of_journey') : date('m/d/Y');
        $date_of_journey_formatted = formatDate($date_of_journey);
    @endphp
    @if ($kiosk_id)
        @php
            $layout = 'layouts.kiosk';
        @endphp
        @include('templates.basic.partials.kiosk_nav')
    @endif
    @extends($activeTemplate . $layout)

    <div class="padding-top padding-bottom">
        <div class="container">
            <a class="btn btn-outline-dark w-auto mb-3"
                href="{{ url("/tickets?kiosk_id=$kiosk_id&counter_id={$trip->startFrom->id}&pickup={$trip->startFrom->id}&destination={$trip->endTo->id}&date_of_journey=$date_of_journey") }}">
                <i class="fa-solid fa-arrow-left"></i> Go Back
            </a>
            <div class="row gx-xl-5 gy-4 gy-sm-5 justify-content-center">
                <div class="col-md-6">
                    <div class="seat-overview-wrapper">
                        <form action="{{ route('ticket.book', $trip->id) }}" method="POST" id="bookingForm" class="row gy-2">
                            @csrf
                            <input type="hidden" name="kiosk_id" value="{{ request('kiosk_id') }}" hidden>
                            <input type="hidden" name="start_from_time" value="{{ $trip->schedule->start_from }}" hidden>
                            <input type="hidden" name="fleet_type_id" value="{{ $trip->fleetType->id }}" hidden>

                            <input type="text" name="price" hidden>
                            <div class="col-12 mb-2">
                                <div class="form-group">
                                    <label for="date_of_journey" class="form-label">@lang('Journey Date')</label>
                                    <h5>{{ $date_of_journey_formatted }}</h5>

                                    <input type="hidden" value="{{ $date_of_journey }}" name="date_of_journey">
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="form-group">
                                    <label for="date_of_journey" class="form-label">@lang('Departure Time')</label>
                                    <h5>{{ date('h:i A', strtotime($trip->schedule->start_from)) }}</h5>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="form-group">
                                    <label for="pickup_point" class="form-label">@lang('Pickup Point')</label>
                                    {{-- <select name="pickup_point" id="pickup_point" class="form--control select2">
                                        <option value="">@lang('Select One')</option>
                                        @foreach ($stoppages as $item)
                                            <option value="{{ $item->id }}"
                                                @if (request('start_from') == $item->id) selected @endif>
                                                {{ __($item->name) }}
                                            </option>
                                        @endforeach
                                    </select> --}}
                                    <input type="hidden" name="pickup_point" id="pickup_point"
                                        value="{{ $trip->startFrom->id }}">

                                    <h5>{{ $trip->startFrom->name }}</h5>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="form-group">
                                    <label for="dropping_point" class="form-label">@lang('Dropping Point')</label>
                                    {{-- <select name="dropping_point" id="dropping_point" class="form--control select2">
                                        <option value="">@lang('Select One')</option>
                                    </select> --}}
                                    <input type="hidden" name="dropping_point" id="dropping_point"
                                        value="{{ $trip->endto->id }}">
                                    <h5>{{ $trip->endto->name }}</h5>
                                </div>
                            </div>
                            {{-- <div class="col-12">
                                <label class="form-label">@lang('Select Gender')</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-group custom--radio">
                                        <input id="male" type="radio" name="gender" value="1">
                                        <label class="form-label" for="male">@lang('Male')</label>
                                    </div>
                                    <div class="form-group custom--radio">
                                        <input id="female" type="radio" name="gender" value="2">
                                        <label class="form-label" for="female">@lang('Female')</label>
                                    </div>
                                </div>
                            </div> --}}

                            <div class="booked-seat-details my-3 d-none">
                                <label>@lang('Selected Seats')</label>
                                <div class="list-group seat-details-animate">
                                    <span
                                        class="list-group-item d-flex bg--base text-white justify-content-between">@lang('Seat Details')<span>@lang('Price')</span></span>
                                    <div class="selected-seat-details">
                                    </div>
                                </div>
                            </div>
                            <input type="text" name="seats" hidden>
                            <div class="col-12 mt-3">
                                <button type="submit" class="book-bus-btn">@lang('Continue')</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
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
                    </div>
                    @include('templates.basic.partials.seat_layout', ['fleetType' => $trip->fleetType])

                </div>
            </div>
        </div>

        {{-- confirmation modal --}}
        <div class="modal fade" id="bookConfirm" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"> @lang('Confirm Booking')</h5>
                        <button type="button" class="w-auto btn--close" data-bs-dismiss="modal"><i
                                class="las la-times"></i></button>
                    </div>
                    <div class="modal-body">
                        <strong class="text-dark">@lang('Are you sure you want to book the selected seat(s)?')</strong>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--danger w-auto btn--sm px-3" data-bs-dismiss="modal">
                            @lang('Close')
                        </button>
                        <button type="submit" class="btn btn--base btn--sm w-auto" id="btnBookConfirm">@lang('Confirm')
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
            /* Selected by Admin for Rebooking (Green) */
            .seat.selected {
                cursor: grab;
                /* Shows the user they can drag it */
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

                const params = new URLSearchParams(window.location.search);

                const reload_page = params.get('reload');

                if (reload_page == 'yes') {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('reload');
                    window.location.href = url.toString();
                }

                showBookedSeat()

                var date_of_journey = "{{ Session::get('date_of_journey ') }}";
                var pickup = "{{ Session::get('pickup ') }}";
                var destination = "{{ Session::get('destination ') }}";

                $(".select2").select2();

                if (date_of_journey && pickup && destination) {
                    showBookedSeat();
                }

                $('.date-range').daterangepicker({
                    autoUpdateInput: true,
                    singleDatePicker: true,
                    minDate: new Date(),
                    maxDate: moment().add(3, 'days')
                });

                function reset() {
                    $('.seat-wrapper .seat').removeClass('selected').removeAttr('draggable').removeAttr('title');
                    $('.seat-wrapper .seat').parent().removeClass(
                        'seat-condition selected-by-ladies selected-by-gents selected-by-others disabled');
                    $('.selected-seat-details').html('');
                }

                // ==========================================
                // CLICK SEAT LOGIC (Updated for dragging)
                // ==========================================
                $('.seat-wrapper .seat').off('click');

                $(document).on('click', '.seat-wrapper .seat', function(e) {

                    // 2. Stop any external theme scripts from double-toggling the seat
                    e.stopImmediatePropagation();

                    // 3. Ignore clicks on disabled, booked, or comfort room seats
                    if ($(this).hasClass('disabled-seat') || $(this).hasClass('comfort-room') || $(this).parent()
                        .hasClass('disabled')) {
                        $(this).removeClass('selected');
                        return;
                    }

                    var pickupPoint = $('input[name="pickup_point"]').val();
                    var droppingPoint = $('input[name="dropping_point"]').val();
                    var seat = $(this).attr('data-seat');

                    if (seat) {
                        if (pickupPoint && droppingPoint) {
                            // 4. Manually handle the selection toggle
                            if ($(this).hasClass('selected')) {
                                console.log('remove selected')
                                $(this).removeClass('selected');
                            } else {
                                $(this).addClass('selected');
                                console.log('selected')
                            }

                            // 5. Update the UI and Drag attributes
                            selectSeat();

                        } else {
                            $(this).removeClass('selected');
                            notify('error', "@lang('Please select pickup point and dropping point before select any seat')");
                        }
                    }
                });

                // ==========================================
                // DRAG AND DROP SEAT LOGIC
                // ==========================================
                $(document).on('dragstart', '.seat.selected', function(e) {
                    let seatData = $(this).attr('data-seat');
                    e.originalEvent.dataTransfer.setData('sourceSeat', seatData);
                });

                $(document).on('dragover', '.seat-wrapper .seat:not(.disabled-seat):not(.comfort-room):not(.selected)',
                    function(e) {
                        // Prevent drop if the seat is booked (parent has 'disabled' class)
                        if ($(this).parent().hasClass('disabled')) return;

                        e.preventDefault(); // Required to allow dropping
                        $(this).addClass('drag-over');
                    });

                $(document).on('dragleave', '.seat-wrapper .seat', function(e) {
                    $(this).removeClass('drag-over');
                });

                $(document).on('drop', '.seat-wrapper .seat:not(.disabled-seat):not(.comfort-room):not(.selected)',
                    function(e) {
                        if ($(this).parent().hasClass('disabled')) return;

                        e.preventDefault();
                        $(this).removeClass('drag-over');

                        let sourceSeat = e.originalEvent.dataTransfer.getData('sourceSeat');
                        if (!sourceSeat) return;

                        // 1. Remove selection from the old dragged seat
                        let oldSeat = $(`.seat-wrapper .seat[data-seat="${sourceSeat}"]`);
                        oldSeat.removeClass('selected').removeAttr('draggable').removeAttr('title');

                        // 2. Add selection to the new dropped seat
                        $(this).addClass('selected').attr('draggable', true).attr('title', 'Your Seat (Drag to move)');

                        // 3. Update the UI, pricing, and hidden inputs
                        selectSeat();
                    });


                // ==========================================
                // BOOKING & UI LOGIC
                // ==========================================
                function selectSeat() {
                    let selectedSeats = $('.seat.selected');
                    let seatDetails = ``;
                    let price = $('input[name=price]').val();
                    let subtotal = 0;
                    let currency = '{{ __(gs('cur_text')) }}';
                    let seats = '';

                    // Sync drag-and-drop attributes across all seats safely
                    $('.seat-wrapper .seat').not('.selected').removeAttr('draggable').removeAttr('title');
                    selectedSeats.attr('draggable', true).attr('title', 'Your Seat (Drag to move)');

                    if (selectedSeats.length > 0) {
                        $('.booked-seat-details').removeClass('d-none');
                        $.each(selectedSeats, function(i, value) {
                            seats += $(value).data('seat') + ',';
                            seatDetails +=
                                `<span class="list-group-item d-flex justify-content-between">${$(value).data('seat')} <span>${price} ${currency}</span></span>`;
                            subtotal = subtotal + parseFloat(price);
                        });

                        // Remove trailing comma
                        seats = seats.replace(/,+$/, "");

                        $('input[name=seats]').val(seats);
                        $('.selected-seat-details').html(seatDetails);
                        $('.selected-seat-details').append(
                            `<span class="list-group-item d-flex justify-content-between">@lang('Sub total')<span>${subtotal} ${currency}</span></span>`
                        );
                    } else {
                        $('.selected-seat-details').html('');
                        $('.booked-seat-details').addClass('d-none');
                        $('input[name=seats]').val('');
                    }
                }

                function showBookedSeat() {
                    reset();
                    var date = $('input[name="date_of_journey"]').val();
                    var sourceId = $('input[name="pickup_point"]').val();
                    var destinationId = $('input[name="dropping_point"]').val();

                    if (sourceId == destinationId && destinationId != '') {
                        notify('error', "@lang('Source Point and Destination Point Must Not Be Same')");
                        $('input[name="dropping_point"]').val('').select2();
                        return false;
                    } else if (sourceId != destinationId) {
                        var routeId = '{{ $trip->route->id }}';
                        var fleetTypeId = '{{ $trip->fleetType->id }}';

                        if (sourceId && destinationId) {
                            getPrice(routeId, fleetTypeId, sourceId, destinationId, date)
                        }
                    }
                }

                // check price, booked seat etc
                function getPrice(routeId, fleetTypeId, sourceId, destinationId, date) {
                    var data = {
                        "trip_id": "{{ $trip->id }}",
                        "vehicle_route_id": routeId,
                        "fleet_type_id": fleetTypeId,
                        "source_id": sourceId,
                        "destination_id": destinationId,
                        "date": date,
                        "start_from_time": '{{ $trip->schedule->start_from }}'
                    }
                    $.ajax({
                        type: "get",
                        url: "{{ route('ticket.get-price') }}",
                        data: data,
                        success: function(response) {

                            if (response.error) {
                                var modal = $('#alertModal');
                                modal.find('.error-message').text(response.error);
                                modal.modal('show');
                                $('input[name="pickup_point"]').val('');
                                $('input[name="dropping_point"]').val('');
                            } else {
                                var stoppages = response.stoppages;
                                var reqSource = response.reqSource;
                                var reqDestination = response.reqDestination;

                                reqSource = stoppages.indexOf(reqSource.toString());
                                reqDestination = stoppages.indexOf(reqDestination.toString());

                                if (response.reverse == true) {
                                    $.each(response.bookedSeats, function(i, v) {
                                        var bookedSource = v.pickup_point;
                                        var bookedDestination = v.dropping_point;

                                        bookedSource = stoppages.indexOf(bookedSource.toString());
                                        bookedDestination = stoppages.indexOf(bookedDestination
                                            .toString());

                                        if (reqDestination >= bookedSource || reqSource <=
                                            bookedDestination) {
                                            $.each(v.seats, function(index, val) {
                                                if (v.gender == 1) {
                                                    $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                        .parent().removeClass(
                                                            'seat-condition selected-by-gents disabled'
                                                        );
                                                }
                                                if (v.gender == 2) {
                                                    $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                        .parent().removeClass(
                                                            'seat-condition selected-by-ladies disabled'
                                                        );
                                                }
                                                if (v.gender == 3) {
                                                    $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                        .parent().removeClass(
                                                            'seat-condition selected-by-others disabled'
                                                        );
                                                }
                                            });
                                        } else {
                                            $.each(v.seats, function(index, val) {
                                                if (v.gender == 1) {
                                                    $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                        .parent().addClass(
                                                            'seat-condition selected-by-gents disabled'
                                                        );
                                                }
                                                if (v.gender == 2) {
                                                    $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                        .parent().addClass(
                                                            'seat-condition selected-by-ladies disabled'
                                                        );
                                                }
                                                if (v.gender == 3) {
                                                    $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                        .parent().addClass(
                                                            'seat-condition selected-by-others disabled'
                                                        );
                                                }
                                            });
                                        }
                                    });
                                } else {
                                    $.each(response.bookedSeats, function(i, v) {
                                        var bookedSource = v.pickup_point;
                                        var bookedDestination = v.dropping_point;

                                        bookedSource = stoppages.indexOf(bookedSource.toString());
                                        bookedDestination = stoppages.indexOf(bookedDestination
                                            .toString());

                                        $.each(v.seats, function(index, val) {
                                            if (v.gender == 1) {
                                                $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                    .parent().addClass(
                                                        'seat-condition selected-by-gents disabled'
                                                    );
                                            }
                                            if (v.gender == 2) {
                                                $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                    .parent().addClass(
                                                        'seat-condition selected-by-ladies disabled'
                                                    );
                                            }
                                            if (v.gender == 3) {
                                                $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                    .parent().addClass(
                                                        'seat-condition selected-by-others disabled'
                                                    );
                                            }
                                        });
                                    });
                                }

                                if (response.price.error) {
                                    var modal = $('#alertModal');
                                    modal.find('.error-message').text(response.price.error);
                                    modal.modal('show');
                                } else {
                                    $('input[name=price]').val(response.price);
                                }
                            }
                        }
                    });
                }

                //booking form submit
                $('#bookingForm').on('submit', function(e) {
                    e.preventDefault();
                    let selectedSeats = $('.seat.selected');
                    if (selectedSeats.length > 0) {
                        var modal = $('#bookConfirm');
                        modal.modal('show');
                    } else {
                        notify('error', 'Select at least one seat.');
                    }
                });

                //confirmation modal
                $(document).on('click', '#btnBookConfirm', function(e) {
                    var modal = $('#bookConfirm');
                    modal.modal('hide');
                    document.getElementById("bookingForm").submit();
                });

            })(jQuery);
        </script>
    @endpush
