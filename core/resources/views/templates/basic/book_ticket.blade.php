@section('content')
    @php
        $kiosk_id = request()->kiosk_id;
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
            <div class="row gx-xl-5 gy-4 gy-sm-5 justify-content-center">
                <div class="col-md-6">
                    <div class="seat-overview-wrapper">
                        <form action="{{ route('ticket.book', $trip->id) }}" method="POST" id="bookingForm" class="row gy-2">
                            @csrf
                            <input type="hidden" name="kiosk_id" value="{{ request('kiosk_id') }}" hidden>
                            <input type="text" name="price" hidden>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="date_of_journey" class="form-label">@lang('Journey Date')</label>
                                    <input type="text" id="date_of_journey" class="form--control date-range"
                                        value="{{ request('date_of_journey') ? request('date_of_journey') : date('m/d/Y') }}"
                                        name="date_of_journey">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="pickup_point" class="form-label">@lang('Pickup Point')</label>
                                    <select name="pickup_point" id="pickup_point" class="form--control select2">
                                        <option value="">@lang('Select One')</option>
                                        @foreach ($stoppages as $item)
                                            <option value="{{ $item->id }}"
                                                @if (request('start_from') == $item->id) selected @endif>
                                                {{ __($item->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="dropping_point" class="form-label">@lang('Dropping Point')</label>
                                    <select name="dropping_point" id="dropping_point" class="form--control select2">
                                        <option value="">@lang('Select One')</option>
                                        @foreach ($stoppages as $item)
                                            <option value="{{ $item->id }}"
                                                @if (request('end_to') == $item->id) selected @endif>
                                                {{ __($item->name) }}
                                            </option>
                                        @endforeach
                                    </select>
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
                        <strong class="text-dark">@lang('Are you sure to book these seats?')</strong>
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
                    minDate: new Date()
                });



                //reset all seats
                function reset() {
                    $('.seat-wrapper .seat').removeClass('selected');
                    $('.seat-wrapper .seat').parent().removeClass(
                        'seat-condition selected-by-ladies selected-by-gents selected-by-others disabled');
                    $('.selected-seat-details').html('');
                }

                //click on seat
                $('.seat-wrapper .seat').on('click', function() {
                    if ($(this).hasClass('disabled-seat') || $(this).hasClass('comfort-room')) {
                        $(this).removeClass('selected')
                    }

                    var pickupPoint = $('select[name="pickup_point"]').val();
                    var droppingPoint = $('select[name="dropping_point"]').val();
                    var seat = $(this).attr('data-seat')
                    if (seat) {
                        const params = new URLSearchParams(window.location.href);

                        if (pickupPoint && droppingPoint) {
                            selectSeat();
                        } else {
                            $(this).removeClass('selected');
                            notify('error', "@lang('Please select pickup point and dropping point before select any seat')")
                        }
                    }
                });

                //select and booked seat
                function selectSeat() {
                    let selectedSeats = $('.seat.selected');
                    let seatDetails = ``;
                    let price = $('input[name=price]').val();
                    let subtotal = 0;
                    let currency = '{{ __(gs('cur_text')) }}';
                    let seats = '';
                    if (selectedSeats.length > 0) {
                        $('.booked-seat-details').removeClass('d-none');
                        $.each(selectedSeats, function(i, value) {
                            seats += $(value).data('seat') + ',';
                            seatDetails +=
                                `<span class="list-group-item d-flex justify-content-between">${$(value).data('seat')} <span>${price} ${currency}</span></span>`;
                            subtotal = subtotal + parseFloat(price);
                        });

                        $('input[name=seats]').val(seats);
                        $('.selected-seat-details').html(seatDetails);
                        $('.selected-seat-details').append(
                            `<span class="list-group-item d-flex justify-content-between">@lang('Sub total')<span>${subtotal} ${currency}</span></span>`
                        );
                    } else {
                        $('.selected-seat-details').html('');
                        $('.booked-seat-details').addClass('d-none');
                    }
                }

                //on change date, pickup point and destination point show available seats
                $(document).on('change',
                    'select[name="pickup_point"], select[name="dropping_point"], input[name="date_of_journey"]',
                    function(e) {
                        showBookedSeat();
                    });

                //booked seat
                function showBookedSeat() {
                    reset();
                    var date = $('input[name="date_of_journey"]').val();
                    var sourceId = $('select[name="pickup_point"]').find("option:selected").val();
                    var destinationId = $('select[name="dropping_point"]').find("option:selected").val();

                    if (sourceId == destinationId && destinationId != '') {
                        notify('error', "@lang('Source Point and Destination Point Must Not Be Same')");
                        $('select[name="dropping_point"]').val('').select2();
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
                                $('select[name="pickup_point"]').val('');
                                $('select[name="dropping_point"]').val('');
                            } else {
                                var stoppages = response.stoppages;

                                var reqSource = response.reqSource;
                                var reqDestination = response.reqDestination;

                                reqSource = stoppages.indexOf(reqSource.toString());
                                reqDestination = stoppages.indexOf(reqDestination.toString());

                                if (response.reverse == true) {
                                    $.each(response.bookedSeats, function(i, v) {
                                        var bookedSource = v.pickup_point; //Booked
                                        var bookedDestination = v.dropping_point; //Booked

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

                                        var bookedSource = v.pickup_point; //Booked
                                        var bookedDestination = v.dropping_point; //Booked

                                        bookedSource = stoppages.indexOf(bookedSource.toString());
                                        bookedDestination = stoppages.indexOf(bookedDestination
                                            .toString());


                                        if (reqDestination <= bookedSource || reqSource >=
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
