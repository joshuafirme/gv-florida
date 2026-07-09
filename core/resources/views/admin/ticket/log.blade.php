@extends('admin.layouts.app')
@php
    use Carbon\Carbon;
    $allowed_advance_booking_days = getAllowedAdvanceBookingDays();
@endphp
@section('panel')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('User')</th>
                                    <th>@lang('PNR Number')</th>
                                    <th>@lang('Journey Date')</th>
                                    <th>@lang('Trip')</th>
                                    <th>@lang('Fare')</th>
                                    <th>@lang('Passenger Type')</th>
                                    <th>@lang('Booking Source')</th>
                                    <th>@lang('Payment Method')</th>
                                    <th>@lang('Processed By')</th>
                                    <th>@lang('Is Rebooked')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $item)
                                    <tr>
                                        <td data-label="@lang('User')">
                                            @if ($item->kiosk)
                                                {{ $item->kiosk->name }}
                                                <div>{{ $item->kiosk->uid }}</div>
                                            @elseif($item->user_id)
                                                <span class="font-weight-bold">{{ __(@$item->user->fullname) }}</span>
                                                <br>
                                                <span class="small"> <a
                                                        href="{{ route('admin.users.detail', $item->user_id) }}"><span>@</span>{{ __(@$item->user->username) }}</a>
                                                </span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('PNR Number')">
                                            <div><span class="text-muted">{{ __($item->pnr_number) }}</span></div>
                                            @if ($item->status == 1)
                                                <span
                                                    class="badge badge--success font-weight-normal text--samll">@lang('Booked')</span>
                                            @elseif($item->status == 2)
                                                <span
                                                    class="badge badge--warning font-weight-normal text--samll">@lang('Pending')</span>
                                            @else
                                                <span
                                                    class="badge badge--danger font-weight-normal text--samll">@lang('Rejected')</span>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Journey Date')">
                                            {{ __(showDateTime($item->date_of_journey, 'd M, Y')) }}
                                            <div>{{ date('h:i A', strtotime($item->trip?->schedule?->start_from)) }}</div>
                                        </td>
                                        <td data-label="@lang('Trip')">
                                            <span class="font-weight-bold">{{ __($item->trip?->fleetType?->name) }}</span>
                                            <br>
                                            <span class="fw-bold text-dark text-end">
                                                {{ $item->pickup->name }}
                                                <i class="las la-long-arrow-alt-right mx-1 text-muted"></i>
                                                {{ $item->drop->name }}
                                            </span>
                                        </td>
                                        <td data-label="@lang('Fare')">
                                            {{ __(showAmount($item->sub_total)) }}
                                            <div>Ticket Count: {{ $item->seats ? __(sizeof($item->seats)) : '' }}</div>
                                            @if ($item->seats && is_array($item->seats))
                                                <div>{{ implode(', ', $item->seats) }}</div>
                                            @endif
                                        </td>
                                        <td>{{ getPassengerType($item?->deposit) }}</td>
                                        <td>{{ $item->kiosk_id ? $item->kiosk->name : 'Online' }}</td>
                                        <td>
                                            @if ($item->deposit && $item->deposit->pchannel)
                                                {{ readPaymentChannel($item->deposit->pchannel) }}
                                            @elseif($item->deposit)
                                                {{ $item->deposit->gatewayCurrency()->name }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->approved_by)
                                                {{ $item->approvedBy->name }}
                                            @elseif ($item->kiosk_id)
                                                {{ $item->kiosk->name }}
                                            @elseif ($item->deposit && $item->deposit->pchannel)
                                                Paynamics
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->is_rebooked)
                                                <span class="badge badge--info">@lang('Yes')</span>
                                            @else
                                                <span class="badge badge--dark">@lang('No')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->status == Status::BOOKED_APPROVED)
                                                <a data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                    title="Reservation slip" target="_blank"
                                                    href="{{ route('admin.trip.reservationSlip', $item->id) }}"
                                                    class="btn btn-sm btn-outline--primary ms-1">
                                                    <i class="fa-solid fa-receipt"></i>
                                                </a>

                                                {{-- @if (Carbon::parse($item->date_of_journey)->greaterThan(now()) && !$item->is_rebooked) --}}
                                                <button data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                    title="Change Schedule" target="_blank"
                                                    class="btn btn-sm btn-outline--primary ms-1 update-booking-date-btn"
                                                    data-id="{{ $item->id }}"
                                                    data-date-of-journey="{{ $item->date_of_journey }}"
                                                    data-json="{{ $item }}">
                                                    <i class="fa-solid fa-calendar-day"></i>
                                                </button>
                                                {{-- @endif --}}
                                                @if (Carbon::parse($item->date_of_journey)->isFuture() || Carbon::parse($item->date_of_journey)->isToday())
                                                    <button type="button" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom" title="Cancel Booking"
                                                        class="btn btn-sm btn-outline--danger confirmationBtn"
                                                        data-question="@lang('Are you sure you want to cancel this booking?')"
                                                        data-action="{{ route('admin.trip.ticket.cancel.booking', $item->id) }}"><i
                                                            class="fa-solid fa-circle-xmark"></i>
                                                    </button>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($tickets->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($tickets) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="updateBookingDateModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document"> <!-- Changed to modal-xl for wider seat maps -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Booking Date & Seats</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="updateBookingDateForm" method="POST">
                        @csrf
                        <!-- Hidden input to store selected seats array -->
                        <input type="hidden" name="seats" id="selected_seats_input" required>

                        <div class="row">
                            <!-- Left Side: Form Inputs -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="date_of_journey">New Date of Journey</label>
                                    <input type="date" id="rebook_date" class="form-control" name="date_of_journey"
                                        required min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                        max="{{ date('Y-m-d', strtotime("+$allowed_advance_booking_days day")) }}">
                                </div>
                                <div class="form-group mt-2">
                                    <label for="username">Admin Username</label>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                                <div class="form-group mt-2">
                                    <label for="passcode">Admin Passcode</label>
                                    <input type="password" class="form-control" name="passcode" required>
                                </div>

                                <div class="mt-4">
                                    <h6>Selection Info</h6>
                                    <p>Seats Required: <span id="seats_required_count" class="font-weight-bold">0</span></p>
                                    <p>Selected Seats: <span id="selected_seats_display"
                                            class="font-weight-bold text-primary">None</span></p>
                                </div>

                                <button type="submit" class="btn btn--primary w-100 mt-3" id="submitUpdateBtn"
                                    disabled>Update</button>
                            </div>

                            <!-- Right Side: Seat Map Container -->
                            <div class="col-md-8">
                                <div id="seat_map_loader" class="text-center d-none py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p>Loading seat layout...</p>
                                </div>
                                <div id="seat_map_container" class="seat-map-container">
                                    <p class="text-muted text-center mt-5">Select a new date to view available seats.</p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .seat {
            cursor: pointer;
        }

        /* Permanently Disabled Seats (Grey) */
        .seat.disabled-seat,
        .seat del {
            background-color: #777777 !important;
            color: white;
            cursor: not-allowed;
            opacity: 0.7;
            border-color: #777777 !important;
            text-decoration: line-through;
        }

        /* Dynamically Booked Seats (Purple) */
        .seat.booked-seat {
            background-color: #554BB9 !important;
            color: white;
            cursor: not-allowed;
            opacity: 0.8;
            border-color: #554BB9 !important;
        }

        /* Selected by Admin for Rebooking (Green) */
        .seat.selected {
            background-color: #28a745 !important;
            color: white;
            border-color: #28a745;
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

    <x-confirmation-modal />
@endsection
@push('breadcrumb-plugins')
    <form
        action="{{ route('admin.vehicle.ticket.search', $scope ?? str_replace('admin.vehicle.ticket.', '', request()->route()->getName())) }}"
        method="GET" class="form-inline float-sm-right bg--white">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="@lang('Search PNR Number')"
                value="{{ $search ?? '' }}">
            <button class="btn btn--primary" type="submit"><i class="fa fa-search"></i></button>
        </div>
    </form>
@endpush

@push('script')
    {{-- <script>
        'use strict';

        $(document).on("click", ".update-booking-date-btn", function(e) {
            e.preventDefault();

            let id = $(this).data('id');
            let dateOfJourney = $(this).data('date-of-journey') || '';

            // Build URL properly (pass base URL from Blade)
            let actionUrl = "{{ url('admin/manage/update-booking-date') }}";
            actionUrl = `${actionUrl}/${id}`;

            $("#updateBookingDateModal").modal('show');
            $("#updateBookingDateForm").attr('action', actionUrl);
            $("#updateBookingDateForm input[name='date_of_journey']").val(dateOfJourney);
        });
    </script> --}}
    <script>
        'use strict';

        let requiredSeatsCount = 0;
        let selectedSeatsArray = [];
        let originalDateOfJourney = ''; // <-- NEW: Track the original date

        function updateSeatSelectionUI() {
            $("#selected_seats_display").text(selectedSeatsArray.length > 0 ? selectedSeatsArray.join(', ') : 'None');
            $("#selected_seats_input").val(selectedSeatsArray.join(','));

            // Calculate how many seats are still missing
            let seatsStillNeeded = requiredSeatsCount - selectedSeatsArray.length;

            if (seatsStillNeeded > 0) {
                $("#seats_required_count").text(`Need ${seatsStillNeeded} more seat(s)`);
                $("#seats_required_count").removeClass('text-success').addClass('text-danger');
                $("#submitUpdateBtn").prop('disabled', true);
            } else {
                $("#seats_required_count").text(`All ${requiredSeatsCount} seat(s) selected`);
                $("#seats_required_count").removeClass('text-danger').addClass('text-success');
                $("#submitUpdateBtn").prop('disabled', false);
            }
        }

        $(document).on("click", ".update-booking-date-btn", async function(e) {
            e.preventDefault();

            let id = $(this).data('id');
            let dateOfJourney = $(this).data('date-of-journey') || '';
            let data = $(this).data('json') || '';

            // Reset state
            selectedSeatsArray = data.seats || [];
            requiredSeatsCount = selectedSeatsArray.length;
            originalDateOfJourney = dateOfJourney; // <-- Store it when modal opens

            $("#seat_map_container").html(
                '<p class="text-muted text-center mt-5">Select a new date to view available seats.</p>');
            $("#seats_required_count").text(requiredSeatsCount);
            $("#submitUpdateBtn").prop('disabled', true);

            let url = "{{ url('/admin/manage/update-booking-date') }}";
            let actionUrl = url + '/' + id;
            $("#updateBookingDateForm").attr('action', actionUrl);
            $("#updateBookingDateModal").data('ticket-id', id);

            $("#updateBookingDateModal").modal('show');
            $("#rebook_date").val(dateOfJourney).trigger('change');

            await getSeatLayout(data.date_of_journey, data.id);
            updateSeatSelectionUI();
        });

        $(document).on('change', '#rebook_date', function() {
            let selectedDate = $(this).val();
            let ticketId = $("#updateBookingDateModal").data('ticket-id');

            if (!selectedDate || !ticketId) return;

            $("#seat_map_container").addClass('d-none');
            $("#seat_map_loader").removeClass('d-none');
            $("#submitUpdateBtn").prop('disabled', true);

            getSeatLayout(selectedDate, ticketId);
        });

        async function getSeatLayout(selectedDate, ticketId) {
            return $.ajax({
                url: "{{ url('/admin/manage/get-seat-layout') }}",
                type: "GET",
                data: {
                    ticket_id: ticketId,
                    date: selectedDate
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $("#seat_map_container").html(response.html);

                        let bookedSeats = response.booked_seats || [];
                        let disabledSeats = response.disabled_seats || [];

                        disabledSeats = Array.isArray(disabledSeats) ? disabledSeats : Object.values(
                            disabledSeats || {});
                        bookedSeats = Array.isArray(bookedSeats) ? bookedSeats : Object.values(
                            bookedSeats || {});

                        let conflicts = [];

                        $('.seat').each(function() {
                            let seatText = $(this).text().trim();
                            if (!seatText || $(this).hasClass('comfort-room')) return;

                            let deckNumber = $(this).closest('.seat-plan-inner').data('deck');
                            let seatId = deckNumber + '-' + seatText;

                            let isUnavailable = disabledSeats.includes(seatText) || bookedSeats
                                .includes(seatText);

                            // If there is a conflict, but it's the original date, it's just the passenger's own seat.
                            // We only log a real conflict if the dates are different.
                            if (isUnavailable && selectedSeatsArray.includes(seatId)) {
                                if (selectedDate !== originalDateOfJourney) {
                                    conflicts.push(seatText);
                                    selectedSeatsArray = selectedSeatsArray.filter(s => s !==
                                        seatId);
                                } else {
                                    // If it's the original date, safely ignore the backend saying it's booked
                                    isUnavailable = false;
                                }
                            }

                            if (disabledSeats.includes(seatText)) {
                                $(this).addClass('disabled-seat').attr('title', 'Not Available');
                            } else if (bookedSeats.includes(seatText) && isUnavailable) {
                                $(this).addClass('booked-seat').attr('title', 'Already Booked');
                            } else if (selectedSeatsArray.includes(seatId)) {
                                $(this).addClass('selected').attr('draggable', true).attr('title',
                                    'Your Seat (Drag to move)');
                            }
                        });

                        if (conflicts.length > 0 && selectedDate !== originalDateOfJourney) {
                            alert(
                                `Warning: The seat(s) [ ${conflicts.join(', ')} ] are already booked on this new date. They have been removed from your selection.\n\nPlease click an available seat on the layout to replace them.`
                            );
                        }

                        updateSeatSelectionUI();

                        $("#seat_map_loader").addClass('d-none');
                        $("#seat_map_container").removeClass('d-none');
                    }
                },
                error: function() {
                    alert('Failed to load seat map. Please check your connection.');
                    $("#seat_map_loader").addClass('d-none');
                    $("#seat_map_container").removeClass('d-none');
                }
            });
        }

        // --- Drag and Drop logic remains exactly the same as the previous step ---
        $(document).on('dragstart', '.seat.selected', function(e) {
            let seatText = $(this).text().trim();
            let deckNumber = $(this).closest('.seat-plan-inner').data('deck');
            e.originalEvent.dataTransfer.setData('sourceSeatId', deckNumber + '-' + seatText);
        });

        $(document).on('dragover', '.seat:not(.disabled-seat):not(.booked-seat):not(.comfort-room):not(.selected)',
            function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });

        $(document).on('dragleave', '.seat', function(e) {
            $(this).removeClass('drag-over');
        });

        $(document).on('drop', '.seat:not(.disabled-seat):not(.booked-seat):not(.comfort-room):not(.selected)', function(
            e) {
            e.preventDefault();
            $(this).removeClass('drag-over');

            let sourceSeatId = e.originalEvent.dataTransfer.getData('sourceSeatId');
            if (!sourceSeatId) return;

            let targetSeatText = $(this).text().trim();
            let targetDeckNumber = $(this).closest('.seat-plan-inner').data('deck');
            let targetSeatId = targetDeckNumber + '-' + targetSeatText;

            $('.seat.selected').each(function() {
                let txt = $(this).text().trim();
                let dck = $(this).closest('.seat-plan-inner').data('deck');
                if (dck + '-' + txt === sourceSeatId) {
                    $(this).removeClass('selected').removeAttr('draggable').removeAttr('title');
                }
            });

            $(this).addClass('selected').attr('draggable', true).attr('title', 'Your Seat (Drag to move)');

            let index = selectedSeatsArray.indexOf(sourceSeatId);
            if (index !== -1) {
                selectedSeatsArray[index] = targetSeatId;
            }

            updateSeatSelectionUI();
        });

        $(document).on('click', '.seat:not(.disabled-seat):not(.booked-seat):not(.comfort-room)', function() {
            let seatText = $(this).text().trim();
            if (!seatText) return;

            let deckNumber = $(this).closest('.seat-plan-inner').data('deck');
            let seatId = deckNumber + '-' + seatText;

            if ($(this).hasClass('selected')) {
                // Deselect the seat if they click it
                $(this).removeClass('selected').removeAttr('draggable').removeAttr('title');
                selectedSeatsArray = selectedSeatsArray.filter(s => s !== seatId);
            } else {
                // Try to select a new seat
                if (selectedSeatsArray.length >= requiredSeatsCount) {
                    alert(
                        `You already have all ${requiredSeatsCount} seat(s). Drag them to move them, or click one to deselect it first.`
                        );
                    return;
                }

                // Claim the empty seat, add the selection class, and make it draggable
                $(this).addClass('selected').attr('draggable', true).attr('title', 'Your Seat (Drag to move)');
                selectedSeatsArray.push(seatId);
            }

            // Update the countdown and hidden inputs
            updateSeatSelectionUI();
        });
    </script>
@endpush
