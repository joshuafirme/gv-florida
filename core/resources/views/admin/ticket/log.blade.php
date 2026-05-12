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
                                            <div>{{ date('h:i A', strtotime($item->trip->schedule->start_from)) }}</div>
                                        </td>
                                        <td data-label="@lang('Trip')">
                                            <span class="font-weight-bold">{{ __($item->trip->fleetType->name) }}</span>
                                            <br>
                                            <span class="font-weight-bold"> {{ __($item->trip->startFrom->name) }} -
                                                {{ __($item->trip->endTo->name) }}</span>
                                        </td>
                                        <td data-label="@lang('Fare')">
                                            {{ __(showAmount($item->sub_total)) }}
                                            <div>Ticket Count: {{ $item->seats ? __(sizeof($item->seats)) : '' }}</div>
                                            @if ($item->seats && is_array($item->seats))
                                                <div>{{ implode(', ', $item->seats) }}</div>
                                            @endif
                                        </td>
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

                                                @if (Carbon::parse($item->date_of_journey)->greaterThan(now()) && !$item->is_rebooked)
                                                <button data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                    title="Change Schedule" target="_blank"
                                                    class="btn btn-sm btn-outline--primary ms-1 update-booking-date-btn"
                                                    data-id="{{ $item->id }}"
                                                    data-date-of-journey="{{ $item->date_of_journey }}">
                                                    <i class="fa-solid fa-calendar-day"></i>
                                                </button>
                                                @endif
                                                @if (Carbon::parse($item->date_of_journey)->isFuture())
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
                                    disabled>Update Date & Seats</button>
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

        // 1. Open Modal and Setup Initial Data
        $(document).on("click", ".update-booking-date-btn", function(e) {
            e.preventDefault();

            let id = $(this).data('id');
            let dateOfJourney = $(this).data('date-of-journey') || '';

            // Reset state
            selectedSeatsArray = [];
            requiredSeatsCount = 0;
            $("#seat_map_container").html(
                '<p class="text-muted text-center mt-5">Select a new date to view available seats.</p>');
            $("#selected_seats_display").text('None');
            $("#seats_required_count").text('0');
            $("#selected_seats_input").val('');
            $("#submitUpdateBtn").prop('disabled', true);

            let url = "{{ url('/admin/manage/update-booking-date') }}";
            let actionUrl = url + '/' + id;
            $("#updateBookingDateForm").attr('action', actionUrl);
            $("#updateBookingDateModal").data('ticket-id', id); // Store ID on modal for AJAX

            // Show modal and set initial date
            $("#updateBookingDateModal").modal('show');
            $("#rebook_date").val(dateOfJourney).trigger('change'); // Trigger change to load seats immediately
        });

        // 2. Handle Date Change -> Fetch Layout via AJAX
        $(document).on('change', '#rebook_date', function() {
            let selectedDate = $(this).val();
            let ticketId = $("#updateBookingDateModal").data('ticket-id');

            if (!selectedDate || !ticketId) return;

            $("#seat_map_container").addClass('d-none');
            $("#seat_map_loader").removeClass('d-none');
            $("#submitUpdateBtn").prop('disabled', true);
            selectedSeatsArray = []; // Reset selections

            $.ajax({
                url: "{{ url('/admin/manage/get-seat-layout') }}",
                type: "GET",
                data: {
                    ticket_id: ticketId,
                    date: selectedDate
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $("#seat_map_container").html(response.html);
                        requiredSeatsCount = response.required_seats;
                        $("#seats_required_count").text(requiredSeatsCount);
                        $("#selected_seats_display").text('None');

                        let bookedSeats = response.booked_seats || [];
                        let disabledSeats = response.disabled_seats || [];

                        // Apply classes based on which array the seat belongs to
                        $('.seat').each(function() {
                            let seatText = $(this).text().trim();

                            if (!seatText || $(this).hasClass('comfort-room'))
                                return; // Skip empty/CR

                            if (disabledSeats.includes(seatText)) {
                                $(this).addClass('disabled-seat').attr('title',
                                    'Not Available');
                            } else if (bookedSeats.includes(seatText)) {
                                $(this).addClass('booked-seat').attr('title', 'Already Booked');
                            }
                        });

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
        });

        // 3. Handle Seat Selection Clicks
        $(document).on('click', '.seat:not(.disabled-seat):not(.booked-seat):not(.comfort-room)', function() {
            let seatText = $(this).text().trim();
            if (!seatText) return; // Ignore empty wrappers

            // Grab the deck number from the parent container
            let deckNumber = $(this).closest('.seat-plan-inner').data('deck');

            // Create the final prefixed string (e.g., "1-A1" or "2-S4")
            let seatId = deckNumber + '-' + seatText;

            if ($(this).hasClass('selected')) {
                // Deselect seat
                $(this).removeClass('selected');
                selectedSeatsArray = selectedSeatsArray.filter(s => s !== seatId);
            } else {
                // Select seat
                if (selectedSeatsArray.length >= requiredSeatsCount) {
                    alert(`You can only select ${requiredSeatsCount} seat(s) for this rebooking.`);
                    return;
                }
                $(this).addClass('selected');
                selectedSeatsArray.push(seatId);
            }

            // Update UI and hidden input using the prefixed seatId
            $("#selected_seats_display").text(selectedSeatsArray.length > 0 ? selectedSeatsArray.join(', ') :
                'None');
            $("#selected_seats_input").val(selectedSeatsArray.join(','));

            // Enable submit button only if exact required seats are picked
            if (selectedSeatsArray.length === requiredSeatsCount && requiredSeatsCount > 0) {
                $("#submitUpdateBtn").prop('disabled', false);
            } else {
                $("#submitUpdateBtn").prop('disabled', true);
            }
        });
    </script>
@endpush
