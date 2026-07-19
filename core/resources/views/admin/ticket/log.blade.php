@extends('admin.layouts.app')
@php
    use Carbon\Carbon;
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
                                    <th>@lang('PNR')</th>
                                    @if (!empty($ticketRows))
                                        <th>@lang('Reference No.')</th>
                                    @endif
                                    <th>@lang('Journey')</th>
                                    <th>@lang('Trip')</th>
                                    @if (!empty($ticketRows))
                                        <th>@lang('Seat No.')</th>
                                    @endif
                                    <th>@lang('Fare')</th>
                                    <th>@lang('Passenger')</th>
                                    <th>@lang('Booking Source')</th>
                                    <th>@lang('Payment Method')</th>
                                    <th>@lang('Processed By')</th>
                                    <th>@lang('Authorized By')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $item)
                                    @php
                                        $ticketSlip = !empty($ticketRows) ? $item : null;
                                        $item = $ticketSlip ? $ticketSlip->bookedTicket : $item;
                                        $slipCount = max($item->slipSeriesNumbers->count(), 1);
                                        $manifest = collect($item->passenger_manifest ?: ($item->deposit?->userDiscount?->passenger_manifest ?: []));
                                        $seatPassenger = $ticketSlip
                                            ? $manifest->first(fn ($passenger) => (string) ($passenger['seat'] ?? '') === (string) $ticketSlip->seat)
                                            : null;
                                        $ticketOriginalFare = $ticketSlip
                                            ? (float) ($seatPassenger['base_fare'] ?? $item->unit_price ?? (($item->deposit?->amount ?? $item->sub_total) / $slipCount))
                                            : (float) $item->sub_total;
                                        $discountPercentage = (float) ($seatPassenger['discount_percentage'] ?? $item->deposit?->userDiscount?->percentage ?? 0);
                                        $ticketDiscount = $seatPassenger
                                            ? (float) ($seatPassenger['discount_amount'] ?? ($discountPercentage > 0 ? $ticketOriginalFare * ($discountPercentage / 100) : 0))
                                            : ($ticketSlip && $item->deposit?->userDiscount
                                                ? $item->deposit->userDiscount->amount / $slipCount
                                                : 0);
                                        $ticketFare = $ticketSlip
                                            ? (float) ($seatPassenger['fare'] ?? max($ticketOriginalFare - $ticketDiscount, 0))
                                            : (float) $item->sub_total;
                                        $passengerName = $seatPassenger
                                            ? ($seatPassenger['name'] ?: 'Guest')
                                            : ($item->deposit?->userDiscount?->passenger_name ?: ($item->user?->fullname ?: 'Guest'));
                                        $passengerType = $seatPassenger
                                            ? (($seatPassenger['passenger_type'] ?? 'regular') === 'discounted'
                                                ? ($seatPassenger['discount_name'] ?: 'Discounted')
                                                : 'Regular')
                                            : getPassengerType($item?->deposit);
                                        $passengerIdNumber = $seatPassenger['id_number'] ?? $item->deposit?->userDiscount?->id_number;
                                    @endphp
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
                                            @elseif($item->status == Status::BOOKED_REFUNDED)
                                                <span
                                                    class="badge badge--danger font-weight-normal text--samll">@lang('Refunded')</span>
                                            @elseif($item->status == Status::BOOKED_CANCELLED)
                                                <span
                                                    class="badge badge--danger font-weight-normal text--samll">@lang('Cancelled')</span>
                                            @else
                                                <span
                                                    class="badge badge--danger font-weight-normal text--samll">@lang('Rejected')</span>
                                            @endif
                                        </td>
                                        @if ($ticketSlip)
                                            <td data-label="@lang('Reference No.')">{{ $ticketSlip->id }}</td>
                                        @endif
                                        <td data-label="@lang('Journey')">
                                            {{ __(showDateTime($item->date_of_journey, 'M d, Y')) }}
                                            <div>{{ date('h:i A', strtotime($item->trip?->schedule?->start_from)) }}</div>
                                        </td>
                                        <td data-label="@lang('Trip')">
                                            <span class="font-weight-bold">{{ __($item->trip?->fleetType?->name) }}</span>
                                            <br>
                                            <span class="fw-bold text-dark text-end">
                                                {{ $item?->pickup?->name }}
                                                <i class="las la-long-arrow-alt-right mx-1 text-muted"></i>
                                                {{ $item?->drop?->name }}
                                            </span>
                                        </td>
                                        @if ($ticketSlip)
                                            <td data-label="@lang('Seat No.')"><strong>{{ formatSeatLabel($ticketSlip->seat) }}</strong></td>
                                        @endif
                                        <td data-label="@lang('Fare')">
                                            @if ($ticketDiscount > 0)
                                                <div class="text-muted">Fare: {{ showAmount($ticketOriginalFare) }}</div>
                                                <div class="text--warning">Discount: -{{ showAmount($ticketDiscount) }}</div>
                                            @endif
                                            <strong>{{ __(showAmount($ticketFare)) }}</strong>
                                            @if (!$ticketSlip)
                                                <div>Ticket Count: {{ $item->seats ? __(sizeof($item->seats)) : '' }}</div>
                                            @endif
                                            @if (!$ticketSlip && $item->seats && is_array($item->seats))
                                                <div>{{ formatSeatLabel($item->seats) }}</div>
                                            @endif
                                        </td>
                                        <td data-label="@lang('Passenger')">
                                            <strong>{{ $passengerName }}</strong>
                                            <div class="text-muted">{{ $passengerType }}</div>
                                            @if ($passengerIdNumber)
                                                <small class="text-muted">ID: {{ $passengerIdNumber }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $item->kiosk_id ? $item?->kiosk?->name : 'Online' }}</td>
                                        <td>
                                            @if ($item?->deposit && $item?->deposit?->pchannel)
                                                {{ readPaymentChannel($item->deposit->pchannel) }}
                                            @elseif($item->deposit)
                                                {{ $item->deposit->gatewayCurrency()->name }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item?->approved_by)
                                                {{ $item->approvedBy->name }}
                                            @elseif ($item->kiosk_id)
                                                {{ $item->kiosk->name }}
                                            @elseif ($item?->deposit && $item->deposit?->pchannel)
                                                Paynamics
                                            @endif
                                        </td>
                                        <td>&mdash;</td>
                                        <td>
                                            @if ($item->status == 1)
                                                <span
                                                    class="badge badge--success font-weight-normal text--samll">@lang('Booked')</span>
                                            @elseif($item->status == 2)
                                                <span
                                                    class="badge badge--warning font-weight-normal text--samll">@lang('Pending')</span>
                                            @elseif($item->status == Status::BOOKED_REFUNDED)
                                                <span
                                                    class="badge badge--danger font-weight-normal text--samll">@lang('Refunded')</span>
                                            @else
                                                <span
                                                    class="badge badge--danger font-weight-normal text--samll">@lang('Rejected')</span>
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
                                                @php
                                                    $rebookOptionsUrl = $ticketSlip
                                                        ? route('admin.trip.ticket.rebook.options', [$item->id, 'slip_id' => $ticketSlip->id])
                                                        : route('admin.trip.ticket.rebook.options', $item->id);
                                                @endphp
                                                 <button data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                    title="Change Schedule" target="_blank"
                                                    class="btn btn-sm btn-outline--primary ms-1 update-booking-date-btn"
                                                    data-id="{{ $ticketSlip?->id ?? $item->id }}"
                                                    data-options-url="{{ $rebookOptionsUrl }}">
                                                     <i class="fa-solid fa-calendar-day"></i>
                                                 </button>
                                                 @if ($ticketSlip)
                                                     <button type="button" data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                         title="Refund Ticket"
                                                         class="btn btn-sm btn-outline--warning ms-1 refund-ticket-btn"
                                                         data-refund-url="{{ route('admin.vehicle.ticket.refund.options', $ticketSlip->id) }}">
                                                         <i class="las la-undo-alt"></i>
                                                     </button>
                                                     <button type="button" data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                         title="Cancel Ticket"
                                                         class="btn btn-sm btn-outline--danger ms-1 cancel-ticket-btn"
                                                         data-cancel-url="{{ route('admin.vehicle.ticket.cancel.options', $ticketSlip->id) }}">
                                                         <i class="fa-solid fa-circle-xmark"></i>
                                                     </button>
                                                     <button type="button" data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                         title="Void Ticket"
                                                         class="btn btn-sm btn-outline--danger ms-1 void-ticket-btn"
                                                         data-void-url="{{ route('admin.vehicle.ticket.void.options', $ticketSlip->id) }}">
                                                         <i class="las la-ban"></i>
                                                     </button>
                                                 @endif
                                                {{-- @endif --}}
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

    <div id="rebookModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered rebook-dialog">
            <div class="modal-content rebook-modal-content">
                <div class="modal-header rebook-header">
                    <div>
                        <h5 class="modal-title">Rebook · <span id="rebookPnr"></span> <small>Ref. <span
                                    id="rebookReference"></span></small></h5>
                        <div class="rebook-progress mt-2" aria-label="Rebooking progress">
                            <span class="active" data-progress="1"><i class="las la-check"></i></span>
                            <span data-progress="2">2</span>
                            <span data-progress="3">3</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body rebook-body">
                    <div class="rebook-stage" data-stage="loading">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <p class="text-muted mt-2 mb-0">Loading booking details…</p>
                        </div>
                    </div>

                    <div class="rebook-stage d-none" data-stage="type">
                        <p class="rebook-instruction">Choose the type of change.</p>
                        <div class="rebook-type-grid">
                            <button type="button" class="rebook-type-card" data-type="change_date">
                                <i class="las la-calendar"></i>
                                <strong>Change Date</strong>
                                <span>Keep the same trip, time, and fare</span>
                            </button>
                            <button type="button" class="rebook-type-card" data-type="new_trip">
                                <i class="las la-bus"></i>
                                <strong>New Trip</strong>
                                <span>Different schedule, same fare and stoppages</span>
                            </button>
                            <button type="button" class="rebook-type-card" data-type="change_seat">
                                <i class="las la-chair"></i>
                                <strong>Change Seat</strong>
                                <span>Same trip and travel date</span>
                            </button>
                        </div>
                    </div>

                    <div class="rebook-stage d-none" data-stage="selection">
                        <div id="rebookTripField" class="form-group d-none mb-3">
                            <label for="rebookTrip" class="rebook-label">Available trip / schedule</label>
                            <select id="rebookTrip" class="form-control"></select>
                            <small class="text-muted">Only trips with the same fare and original pickup/drop-off stoppages are listed.</small>
                        </div>
                        <div id="rebookDateField" class="form-group d-none mb-2">
                            <label for="rebookDate" class="rebook-label">New travel date</label>
                            <input type="date" id="rebookDate" class="form-control">
                        </div>
                        <p class="rebook-context" id="rebookContext"></p>

                        <div id="rebookAvailability" class="d-none">
                            <div class="rebook-seat-heading">
                                <span>Seats available on <strong id="rebookAvailabilityDate"></strong></span>
                                <span class="text-success" id="rebookSeatStatus"></span>
                            </div>
                            <div class="rebook-assignment-list" id="rebookAssignmentList"></div>
                            <div class="rebook-legend">
                                <span><i class="available"></i>Available</span>
                                <span><i class="selected"></i>Selected</span>
                                <span><i class="taken"></i>Taken</span>
                            </div>
                            <div id="rebookSeatMap" class="rebook-seat-map"></div>
                        </div>
                        <div id="rebookAvailabilityLoader" class="text-center d-none py-5">
                            <div class="spinner-border text-primary"></div>
                            <p class="text-muted mt-2">Checking availability…</p>
                        </div>
                    </div>

                    <div class="rebook-stage d-none" data-stage="review">
                        <p class="rebook-instruction">Review the changes, then confirm.</p>
                        <div class="rebook-review-card">
                            <div class="rebook-review-title">
                                <span id="reviewPassengerType">Guest · Regular</span>
                                <span>Ref. <span id="reviewReference"></span></span>
                            </div>
                            <div class="rebook-review-grid">
                                <span></span><strong>Before</strong><strong>After</strong>
                                <span>Date</span><span id="reviewBeforeDate"></span><span id="reviewAfterDate"></span>
                                <span>Time</span><span id="reviewBeforeTime"></span><span id="reviewAfterTime"></span>
                                <span>Bus</span><span id="reviewBeforeBus"></span><span id="reviewAfterBus"></span>
                                <span>Seat</span><span id="reviewBeforeSeat"></span><strong class="text--primary"
                                    id="reviewAfterSeat"></strong>
                            </div>
                        </div>
                        <div class="rebook-success-alert mt-3">
                            <i class="las la-check"></i>
                            <span>Ticket stays paid — same PNR and reference number, with no new voucher required. The updated
                                reservation slip opens after confirmation so it can be printed.</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer rebook-footer">
                    <button type="button" class="btn btn-light" id="rebookBackBtn">← Back</button>
                    <button type="button" class="btn btn--primary" id="rebookContinueBtn" disabled>Continue →</button>
                    <button type="button" class="btn btn--primary d-none" id="rebookConfirmBtn">
                        <i class="las la-redo-alt me-1"></i> Confirm Rebook
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="refundTicketModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered refund-dialog">
            <div class="modal-content refund-modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title">Refund Ticket</h5>
                        <p class="refund-subtitle mb-0"><span id="refundPnr"></span> · Enter the amount to refund. Default is
                            50% of the fare. The seat is released after confirmation.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body refund-body">
                    <div id="refundLoading" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </div>

                    <div id="refundFormStage" class="d-none">
                        <div class="refund-quick-row">
                            <span>Quick set:</span>
                            <button type="button" class="refund-chip active" data-refund-percent="50">50% (surcharge)</button>
                            <button type="button" class="refund-chip" data-refund-percent="100">Full refund</button>
                        </div>

                        <div class="refund-ticket-card mt-3">
                            <div>
                                <strong id="refundPassenger"></strong>
                                <span id="refundTicketMeta"></span>
                            </div>
                            <div class="refund-amount-input">
                                <span>₱</span>
                                <input type="number" id="refundAmount" min="0.01" step="0.01">
                                <small id="refundPercentLabel"></small>
                            </div>
                        </div>
                        <small class="text-danger" id="refundAmountError"></small>

                        <label class="refund-label mt-3">Reason</label>
                        <div id="refundReasonChips" class="refund-reason-chips"></div>

                        <label class="refund-label mt-3" for="refundRemarks">Refund remarks / explanation</label>
                        <textarea id="refundRemarks" class="form-control refund-textarea" rows="3"
                            placeholder="Provide the reason for this refund…" maxlength="1000"></textarea>

                        <label class="refund-label mt-3" for="refundAuthorizationCode">Authorization Code</label>
                        <input type="password" id="refundAuthorizationCode" class="form-control"
                            placeholder="Enter authorization code" autocomplete="off">

                        <div class="refund-total-card mt-3">
                            <span>Total refund from <strong id="refundFareLabel"></strong> fare</span>
                            <strong id="refundTotal"></strong>
                        </div>
                        <div class="refund-audit-note mt-2">
                            <i class="las la-info-circle"></i>
                            <span>Recorded under <strong id="refundCashier"></strong>. The original sale remains with the selling
                                cashier; this seat will be released.</span>
                        </div>
                    </div>

                    <div id="refundReviewStage" class="d-none">
                        <p class="refund-review-intro">Review the refund details before confirming.</p>
                        <div class="refund-review-card">
                            <div><span>PNR / Ticket</span><strong id="refundReviewTicket"></strong></div>
                            <div><span>Passenger / Seat</span><strong id="refundReviewPassenger"></strong></div>
                            <div><span>Reason</span><strong id="refundReviewReason"></strong></div>
                            <div><span>Remarks</span><strong id="refundReviewRemarks"></strong></div>
                            <div><span>Original Fare</span><strong id="refundReviewFare"></strong></div>
                            <div class="refund-review-total"><span>Refund Amount</span><strong id="refundReviewAmount"></strong></div>
                        </div>
                        <div class="refund-audit-note mt-3">
                            <i class="las la-exclamation-circle"></i>
                            <span>Confirming releases the seat immediately and transfers this ticket to Refunded Tickets.</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer refund-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" id="refundCancelBtn" data-bs-dismiss="modal">Keep Booking</button>
                    <button type="button" class="btn btn--primary" id="refundReviewBtn" disabled>Review Refund</button>
                    <button type="button" class="btn btn-light d-none" id="refundBackBtn">Back</button>
                    <button type="button" class="btn btn--primary d-none" id="refundConfirmBtn">
                        <i class="las la-check-circle me-1"></i> Confirm Refund
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="cancelTicketModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered cancel-dialog">
            <div class="modal-content cancel-modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title">Cancel Ticket</h5>
                        <p class="cancel-subtitle mb-0"><span id="cancelPnr"></span> - select the ticket to cancel. Paid
                            tickets are forfeited - no money returned. Seats are released immediately.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body cancel-body">
                    <div id="cancelLoading" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </div>

                    <div id="cancelFormStage" class="d-none">
                        <div class="cancel-ticket-card">
                            <div>
                                <strong id="cancelPassenger"></strong>
                                <span id="cancelTicketMeta"></span>
                            </div>
                            <strong id="cancelFare"></strong>
                        </div>

                        <label class="cancel-label mt-3" for="cancelReason">Reason for Cancellation</label>
                        <div id="cancelReasonChips" class="cancel-reason-chips"></div>
                        <textarea id="cancelReason" class="form-control cancel-textarea mt-2" rows="3"
                            placeholder="Select a reason above or enter a custom reason..." maxlength="1000"></textarea>

                        <div class="cancel-info-note mt-3">
                            <i class="las la-info-circle"></i>
                            <span>1 paid ticket will be cancelled with no money returned. Use Void to return the full fare, or Refund for a partial amount. Seat released.</span>
                        </div>

                        <div class="cancel-auth-card mt-3">
                            <div class="d-flex align-items-start gap-2">
                                <i class="las la-shield-alt"></i>
                                <div>
                                    <strong>Authorization Required</strong>
                                    <p>An authorized personnel must enter their code to confirm this cancel.</p>
                                </div>
                            </div>
                            <label class="cancel-label" for="cancelAuthorizationCode">Authorization Code</label>
                            <div class="cancel-auth-input">
                                <input type="password" id="cancelAuthorizationCode" class="form-control"
                                    placeholder="Enter staff code" autocomplete="off">
                                <button type="button" id="toggleCancelCode" aria-label="Show authorization code">
                                    <i class="las la-eye"></i>
                                </button>
                            </div>
                            <div id="cancelAuthorizationStatus" class="cancel-auth-status" role="status" aria-live="polite"></div>
                        </div>
                    </div>

                    <div id="cancelReviewStage" class="d-none">
                        <p class="cancel-review-intro">Review the cancellation details before confirming.</p>
                        <div class="cancel-review-card">
                            <div><span>PNR / Ticket</span><strong id="cancelReviewTicket"></strong></div>
                            <div><span>Passenger / Seat</span><strong id="cancelReviewPassenger"></strong></div>
                            <div><span>Reason</span><strong id="cancelReviewReason"></strong></div>
                            <div><span>Authorized By</span><strong id="cancelReviewAuthorizedBy"></strong></div>
                            <div class="cancel-review-total"><span>Fare Forfeited</span><strong id="cancelReviewFare"></strong></div>
                        </div>
                        <div class="cancel-info-note mt-3">
                            <i class="las la-exclamation-circle"></i>
                            <span>Confirming releases the seat immediately and transfers this ticket to Cancelled Tickets.</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer cancel-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" id="cancelKeepBtn" data-bs-dismiss="modal">Keep Booking</button>
                    <button type="button" class="btn btn--danger" id="cancelReviewBtn" disabled>
                        <i class="las la-times-circle me-1"></i> Review Cancel
                    </button>
                    <button type="button" class="btn btn-light d-none" id="cancelBackBtn">Back</button>
                    <button type="button" class="btn btn--danger d-none" id="cancelConfirmBtn">
                        <i class="las la-times-circle me-1"></i> Confirm Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="voidTicketModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered cancel-dialog">
            <div class="modal-content cancel-modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title">Void Ticket</h5>
                        <p class="cancel-subtitle mb-0"><span id="voidPnr"></span> - select the ticket to void. The full fare is returned and the seat is released immediately.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body cancel-body">
                    <div id="voidLoading" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </div>

                    <div id="voidFormStage" class="d-none">
                        <div class="cancel-ticket-card">
                            <div>
                                <strong id="voidPassenger"></strong>
                                <span id="voidTicketMeta"></span>
                            </div>
                            <strong id="voidFare"></strong>
                        </div>

                        <label class="cancel-label mt-3">Reason for Voiding</label>
                        <div id="voidReasonChips" class="cancel-reason-chips"></div>

                        <label class="cancel-label mt-3" for="voidRemarks">Void remarks / explanation</label>
                        <textarea id="voidRemarks" class="form-control cancel-textarea" rows="3"
                            placeholder="Reason for voiding..." maxlength="1000"></textarea>

                        <div class="void-info-note mt-3">
                            <i class="las la-exclamation-circle"></i>
                            <span>1 paid ticket (<strong id="voidReturnAmount"></strong>) will be voided and the full fare recorded as returned. Seat released.</span>
                        </div>

                        <div class="cancel-auth-card mt-3">
                            <div class="d-flex align-items-start gap-2">
                                <i class="las la-shield-alt"></i>
                                <div>
                                    <strong>Authorization Required</strong>
                                    <p>An authorized personnel must enter their code to confirm this void.</p>
                                </div>
                            </div>
                            <label class="cancel-label" for="voidAuthorizationCode">Authorization Code</label>
                            <div class="cancel-auth-input">
                                <input type="password" id="voidAuthorizationCode" class="form-control"
                                    placeholder="Enter staff code" autocomplete="off">
                                <button type="button" id="toggleVoidCode" aria-label="Show authorization code">
                                    <i class="las la-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer cancel-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep Booking</button>
                    <button type="button" class="btn btn--danger" id="voidConfirmBtn" disabled>
                        <i class="las la-ban me-1"></i> Void (1)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .rebook-dialog { max-width: 760px; }
        .rebook-modal-content { border: 0; border-radius: 14px; box-shadow: 0 22px 60px rgba(0, 0, 0, .28); overflow: hidden; }
        .rebook-header { align-items: flex-start; border-color: #e6e7eb; padding: 20px 24px; }
        .rebook-header .modal-title { color: #16181d; font-size: 19px; font-weight: 700; }
        .rebook-header .modal-title small { color: #686d78; font-size: 12px; font-weight: 600; }
        .rebook-progress { display: flex; gap: 8px; }
        .rebook-progress span { align-items: center; background: #d8dbe1; border-radius: 50%; color: #fff; display: inline-flex; font-size: 10px; font-weight: 700; height: 20px; justify-content: center; width: 20px; }
        .rebook-progress span.active { background: #12a451; }
        .rebook-progress span.current { background: #e3196b; }
        .rebook-body { max-height: 70vh; overflow-y: auto; padding: 22px 24px; }
        .rebook-instruction { color: #555b66; font-size: 13px; margin-bottom: 14px; }
        .rebook-type-grid { display: grid; gap: 14px; grid-template-columns: repeat(3, 1fr); }
        .rebook-type-card { background: #fff; border: 1px solid #dedfe3; border-radius: 10px; color: #30343b; min-height: 132px; padding: 18px 12px; text-align: center; transition: .18s ease; }
        .rebook-type-card i { color: #555b66; display: block; font-size: 25px; margin-bottom: 10px; }
        .rebook-type-card strong { display: block; font-size: 14px; }
        .rebook-type-card span { color: #858a94; display: block; font-size: 10px; line-height: 1.35; margin-top: 6px; }
        .rebook-type-card:hover, .rebook-type-card.selected { background: #fff5f9; border-color: #e3196b; box-shadow: 0 0 0 1px #e3196b; }
        .rebook-type-card.selected i, .rebook-type-card.selected strong { color: #e3196b; }
        .rebook-label { color: #555b66; font-size: 11px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; }
        .rebook-context { color: #6f7480; font-size: 12px; line-height: 1.45; }
        .rebook-seat-heading { align-items: center; display: flex; font-size: 12px; justify-content: space-between; margin: 18px 0 10px; text-transform: uppercase; }
        .rebook-assignment-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .rebook-assignment { background: #fff5f9; border: 1px solid #e3196b; border-radius: 8px; color: #e3196b; font-size: 11px; font-weight: 700; padding: 8px 12px; }
        .rebook-legend { color: #747983; display: flex; flex-wrap: wrap; font-size: 10px; gap: 14px; margin: 12px 0; }
        .rebook-legend i { border: 1px solid #cfd2d8; border-radius: 3px; display: inline-block; height: 10px; margin-right: 4px; width: 10px; }
        .rebook-legend i.selected { background: #e3196b; border-color: #e3196b; }
        .rebook-legend i.taken { background: #535862; border-color: #535862; }
        .rebook-seat-map { background: #f6f6f8; border: 1px solid #dfe1e5; border-radius: 14px; margin: 0 auto; max-width: 430px; overflow: hidden; }
        .rebook-seat-map .container { padding: 18px !important; }
        .rebook-seat-map .container > h4 { display: none; }
        .rebook-seat-map .seat { cursor: pointer; }
        .rebook-seat-map .seat.booked-seat, .rebook-seat-map .seat.disabled-seat, .rebook-seat-map .seat del { background: #555a64 !important; border-color: #555a64 !important; color: #fff !important; cursor: not-allowed; opacity: .78; text-decoration: none; }
        .rebook-seat-map .seat.selected { background: #e3196b !important; border-color: #e3196b !important; color: #fff !important; }
        .rebook-seat-map .seat.comfort-room { cursor: default; }
        .rebook-review-card { background: #f6f6f8; border: 1px solid #dedfe3; border-radius: 11px; overflow: hidden; }
        .rebook-review-title { background: #ececef; color: #606570; display: flex; font-size: 11px; justify-content: space-between; padding: 9px 14px; }
        .rebook-review-grid { display: grid; font-size: 12px; gap: 9px 15px; grid-template-columns: 70px 1fr 1fr; padding: 16px; }
        .rebook-review-grid > span:first-child, .rebook-review-grid > strong { font-size: 11px; }
        .rebook-success-alert { align-items: flex-start; background: #eefbf3; border: 1px solid #a7e4bf; border-radius: 9px; color: #16753d; display: flex; font-size: 12px; gap: 10px; line-height: 1.45; padding: 12px 14px; }
        .rebook-success-alert i { background: #13a451; border-radius: 50%; color: #fff; flex: 0 0 auto; padding: 2px; }
        .rebook-footer { border-color: #e6e7eb; justify-content: space-between; padding: 14px 24px; }
        .rebook-footer .btn { border-radius: 7px; font-weight: 600; min-width: 92px; }
        .refund-dialog { max-width: 570px; }
        .refund-modal-content { border: 0; border-radius: 15px; box-shadow: 0 22px 60px rgba(0, 0, 0, .28); }
        .refund-modal-content .modal-header { padding: 24px 26px 8px; }
        .refund-modal-content .modal-title { color: #1d2025; font-size: 20px; font-weight: 700; }
        .refund-subtitle { color: #6f737c; font-size: 13px; line-height: 1.45; margin-top: 6px; }
        .refund-body { padding: 18px 26px; }
        .refund-quick-row { align-items: center; color: #6f737c; display: flex; flex-wrap: wrap; font-size: 11px; gap: 8px; }
        .refund-chip, .refund-reason-chip { background: #f1f2f4; border: 1px solid #d2d5da; border-radius: 20px; color: #565b65; font-size: 11px; padding: 6px 12px; }
        .refund-chip.active, .refund-reason-chip.active { background: #fff0f6; border-color: #ed5c98; color: #df1465; }
        .refund-ticket-card { align-items: center; background: #fff5f9; border: 1px solid #ed72a7; border-radius: 10px; display: flex; justify-content: space-between; padding: 12px 14px; }
        .refund-ticket-card > div:first-child strong { display: block; font-size: 14px; }
        .refund-ticket-card > div:first-child span { color: #727781; display: block; font-size: 11px; margin-top: 3px; }
        .refund-amount-input { align-items: center; display: grid; gap: 2px 6px; grid-template-columns: auto 105px; }
        .refund-amount-input input { background: #ececef; border: 1px solid #d2d5da; border-radius: 8px; font-size: 17px; font-weight: 700; height: 43px; padding: 8px 10px; text-align: right; width: 105px; }
        .refund-amount-input small { color: #858a93; font-size: 9px; grid-column: 2; text-align: right; }
        .refund-label { color: #5e636d; display: block; font-size: 11px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; }
        .refund-reason-chips { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 7px; }
        .refund-textarea { background: #f1f2f4; border-color: #d7d9de; border-radius: 9px; resize: none; }
        .refund-total-card { align-items: center; background: #f5f5f7; border: 1px solid #dedfe3; border-radius: 10px; display: flex; justify-content: space-between; padding: 17px; }
        .refund-total-card span { color: #747984; font-size: 12px; }
        .refund-total-card > strong { color: #bd570b; font-size: 23px; }
        .refund-audit-note { align-items: flex-start; background: #fff9e9; border: 1px solid #f0d994; border-radius: 9px; color: #a45b15; display: flex; font-size: 11px; gap: 8px; line-height: 1.45; padding: 12px; }
        .refund-audit-note i { font-size: 17px; }
        .refund-review-intro { color: #686d77; font-size: 13px; }
        .refund-review-card { background: #f5f5f7; border: 1px solid #dedfe3; border-radius: 11px; overflow: hidden; }
        .refund-review-card > div { align-items: flex-start; display: flex; gap: 18px; justify-content: space-between; padding: 11px 14px; }
        .refund-review-card > div + div { border-top: 1px solid #dedfe3; }
        .refund-review-card span { color: #747984; font-size: 12px; }
        .refund-review-card strong { font-size: 13px; max-width: 65%; text-align: right; }
        .refund-review-total strong { color: #bd570b; font-size: 20px; }
        .refund-footer { justify-content: space-between; padding: 8px 26px 24px; }
        .refund-footer .btn { border-radius: 8px; font-weight: 600; min-height: 42px; padding-left: 18px; padding-right: 18px; }
        .cancel-dialog { max-width: 620px; }
        .cancel-modal-content { border: 0; border-radius: 17px; box-shadow: 0 22px 60px rgba(0, 0, 0, .3); }
        .cancel-modal-content .modal-header { padding: 28px 30px 8px; }
        .cancel-modal-content .modal-title { color: #1d2025; font-size: 21px; font-weight: 700; }
        .cancel-subtitle { color: #5f646e; font-size: 14px; line-height: 1.45; margin-top: 6px; }
        .cancel-body { padding: 18px 30px; }
        .cancel-ticket-card { align-items: center; background: #fff2f2; border: 1px solid #f2b6b6; border-radius: 9px; display: flex; justify-content: space-between; padding: 14px 16px; }
        .cancel-ticket-card strong { color: #1f2228; font-size: 14px; }
        .cancel-ticket-card span { color: #6d737e; display: block; font-size: 12px; margin-top: 4px; }
        .cancel-label { color: #565c66; display: block; font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; }
        .cancel-reason-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .cancel-reason-chip { background: #f1f2f4; border: 1px solid #d2d5da; border-radius: 20px; color: #565b65; font-size: 12px; padding: 7px 13px; }
        .cancel-reason-chip.active { background: #fff0f0; border-color: #ee8585; color: #d83535; }
        .cancel-textarea { background: #f1f2f4; border-color: #d7d9de; border-radius: 9px; min-height: 74px; resize: none; }
        .cancel-info-note { align-items: flex-start; background: #f5f5f6; border: 1px solid #dcdfe4; border-radius: 9px; color: #595f69; display: flex; font-size: 12px; gap: 10px; line-height: 1.45; padding: 14px; }
        .cancel-info-note i { color: #6d737e; font-size: 18px; }
        .cancel-auth-card { background: #fafafa; border: 1px solid #dcdfe4; border-radius: 12px; padding: 18px; }
        .cancel-auth-card i { color: #e3196b; font-size: 18px; }
        .cancel-auth-card strong { color: #2c3037; display: block; font-size: 15px; margin-bottom: 4px; }
        .cancel-auth-card p { color: #747984; font-size: 12px; margin-bottom: 14px; }
        .cancel-auth-input { max-width: 390px; position: relative; }
        .cancel-auth-input input { background: #f1f2f4; border-color: #d2d5da; border-radius: 8px; height: 45px; padding-right: 42px; }
        .cancel-auth-input button { background: transparent; border: 0; color: #6f7480; position: absolute; right: 10px; top: 10px; }
        .cancel-auth-status { display: none; font-size: 12px; font-weight: 600; margin-top: 9px; }
        .cancel-auth-status.is-success { color: #17834c; display: block; }
        .cancel-auth-status.is-error { color: #c93636; display: block; }
        .cancel-auth-status i { color: inherit; font-size: 14px; margin-right: 4px; }
        .void-info-note { align-items: flex-start; background: #fff9e8; border: 1px solid #f3cf74; border-radius: 9px; color: #b65c0d; display: flex; font-size: 12px; gap: 9px; line-height: 1.45; padding: 12px 14px; }
        .void-info-note i { flex: 0 0 auto; font-size: 17px; margin-top: 1px; }
        .cancel-review-intro { color: #686d77; font-size: 13px; }
        .cancel-review-card { background: #f5f5f7; border: 1px solid #dedfe3; border-radius: 11px; overflow: hidden; }
        .cancel-review-card > div { align-items: flex-start; display: flex; gap: 18px; justify-content: space-between; padding: 12px 15px; }
        .cancel-review-card > div + div { border-top: 1px solid #dedfe3; }
        .cancel-review-card span { color: #747984; font-size: 12px; }
        .cancel-review-card strong { font-size: 13px; max-width: 65%; text-align: right; }
        .cancel-review-total strong { color: #d83535; font-size: 20px; }
        .cancel-footer { justify-content: flex-end; gap: 12px; padding: 8px 30px 28px; }
        .cancel-footer .btn { border-radius: 9px; font-weight: 600; min-height: 43px; padding-left: 22px; padding-right: 22px; }
        @media (max-width: 575.98px) { .rebook-type-grid { grid-template-columns: 1fr; } .rebook-body { max-height: 68vh; padding: 18px 16px; } .rebook-review-grid { grid-template-columns: 58px 1fr 1fr; } }
    </style>

    <x-confirmation-modal />
@endsection
@push('breadcrumb-plugins')
    <form
        action="{{ route('admin.vehicle.ticket.search', $scope ?? str_replace('admin.vehicle.ticket.', '', request()->route()->getName())) }}"
        method="GET" class="form-inline float-sm-right bg--white">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="@lang('PNR, reference no., or passenger')"
                aria-label="@lang('Search PNR, reference no., or passenger name')"
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
    {{-- <script>
        'use strict';

        if (false) {

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
        }
    </script> --}}
    <script>
        'use strict';

        const formatSeatLabel = value => (Array.isArray(value) ? value : String(value ?? '').split(','))
            .map(seat => String(seat).trim().replace(/^\d+-/, ''))
            .filter(Boolean)
            .join(', ');

        (function($) {
            const rebookModal = new bootstrap.Modal(document.getElementById('rebookModal'));
            const csrfToken = "{{ csrf_token() }}";
            let rebookData = null;
            let rebookType = null;
            let rebookStage = 'type';
            let rebookAvailability = null;
            let rebookSeats = [];

            const escapeHtml = value => $('<div>').text(value ?? '').html();

            function validationMessage(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) return Object.values(errors).flat()[0];
                return xhr.responseJSON?.message || 'Unable to process the rebooking request.';
            }

            function showStage(stage) {
                rebookStage = stage;
                $('.rebook-stage').addClass('d-none');
                $(`.rebook-stage[data-stage="${stage}"]`).removeClass('d-none');

                const step = stage === 'type' ? 1 : (stage === 'selection' ? 2 : 3);
                $('[data-progress]').each(function() {
                    const number = Number($(this).data('progress'));
                    $(this).toggleClass('active', number < step).toggleClass('current', number === step);
                    $(this).html(number < step ? '<i class="las la-check"></i>' : number);
                });

                $('#rebookBackBtn').text(stage === 'type' ? 'Cancel' : '← Back');
                $('#rebookContinueBtn').toggleClass('d-none', stage === 'review');
                $('#rebookConfirmBtn').toggleClass('d-none', stage !== 'review');
                $('#rebookContinueBtn').prop('disabled', stage === 'type' ? !rebookType : true);
            }

            $(document).on('click', '.update-booking-date-btn', function(event) {
                event.preventDefault();
                rebookData = null;
                rebookType = null;
                rebookAvailability = null;
                rebookSeats = [];
                $('.rebook-type-card').removeClass('selected');
                $('.rebook-stage').addClass('d-none');
                $('.rebook-stage[data-stage="loading"]').removeClass('d-none');
                $('#rebookPnr, #rebookReference').text('…');
                $('#rebookContinueBtn').prop('disabled', true).removeClass('d-none');
                $('#rebookConfirmBtn').addClass('d-none');
                rebookModal.show();

                $.getJSON($(this).data('options-url')).done(function(data) {
                    rebookData = data;
                    $('#rebookPnr').text(data.booking.pnr);
                    $('#rebookReference').text(data.booking.reference);
                    $('#rebookDate').attr({
                        min: '{{ now()->format('Y-m-d') }}',
                        max: data.max_date
                    }).val(data.booking.date);
                    $('#rebookTrip').html(data.trips.map(trip =>
                        `<option value="${trip.id}">${escapeHtml(trip.label)} · ${escapeHtml(trip.route)}</option>`
                    ).join(''));
                    showStage('type');
                }).fail(function(xhr) {
                    notify('error', validationMessage(xhr));
                    rebookModal.hide();
                });
            });

            $('.rebook-type-card').on('click', function() {
                rebookType = $(this).data('type');
                $('.rebook-type-card').removeClass('selected');
                $(this).addClass('selected');
                $('#rebookContinueBtn').prop('disabled', false);
            });

            function configureSelectionStage() {
                const isNewTrip = rebookType === 'new_trip';
                const needsDate = rebookType !== 'change_seat';
                const original = rebookData.booking;

                $('#rebookTripField').toggleClass('d-none', !isNewTrip);
                $('#rebookDateField').toggleClass('d-none', !needsDate);
                $('#rebookAvailability').addClass('d-none');
                $('#rebookContinueBtn').prop('disabled', true);
                $('#rebookDate').val(original.date);

                if (rebookType === 'change_date') {
                    $('#rebookContext').text(`Same trip (${original.bus_type} · ${original.time}). Seats are rechecked for the selected date.`);
                } else if (rebookType === 'new_trip') {
                    $('#rebookContext').text('Available trips already match the original fare and pickup/drop-off stoppages.');
                } else {
                    $('#rebookContext').text(`Same trip and travel date (${original.date_display} · ${original.time}). Choose a replacement seat.`);
                }

                if (isNewTrip && !rebookData.trips.length) {
                    notify('error', 'No alternate trips currently match the original fare and stoppages.');
                    return;
                }

                loadAvailability();
            }

            function loadAvailability() {
                const date = rebookType === 'change_seat' ? rebookData.booking.date : $('#rebookDate').val();
                const tripId = rebookType === 'new_trip' ? $('#rebookTrip').val() : rebookData.booking.trip_id;
                if (!date || (rebookType === 'new_trip' && !tripId)) return;

                $('#rebookAvailability').addClass('d-none');
                $('#rebookAvailabilityLoader').removeClass('d-none');
                $('#rebookContinueBtn').prop('disabled', true);

                $.getJSON(rebookData.availability_url, {
                    type: rebookType,
                    date,
                    trip_id: tripId
                }).done(function(data) {
                    rebookAvailability = data;
                    rebookSeats = rebookType === 'change_seat' ? [] : [...data.selected_seats];
                    $('#rebookAvailabilityDate').text(data.after.date_display);
                    $('#rebookSeatMap').html(data.html);
                    applySeatAvailability(data);
                    updateSeatAssignment();
                    $('#rebookAvailability').removeClass('d-none');
                }).fail(function(xhr) {
                    rebookAvailability = null;
                    rebookSeats = [];
                    notify('error', validationMessage(xhr));
                }).always(function() {
                    $('#rebookAvailabilityLoader').addClass('d-none');
                });
            }

            function seatId(element) {
                const seat = $(element).text().trim();
                const deck = $(element).closest('.seat-plan-inner').data('deck');
                return `${deck}-${seat}`;
            }

            function applySeatAvailability(data) {
                const booked = data.booked_seats || [];
                const disabled = data.disabled_seats || [];
                const availableIds = [];

                $('#rebookSeatMap .seat').each(function() {
                    if ($(this).hasClass('comfort-room')) return;
                    const seat = $(this).text().trim();
                    const id = seatId(this);
                    const unavailable = booked.includes(id) || disabled.includes(seat) || $(this).find('del').length;

                    $(this).removeClass('selected booked-seat disabled-seat');
                    if (disabled.includes(seat) || $(this).find('del').length) {
                        $(this).addClass('disabled-seat');
                    } else if (booked.includes(id)) {
                        $(this).addClass('booked-seat');
                    } else {
                        availableIds.push(id);
                    }
                });

                rebookSeats = rebookSeats.filter(id => availableIds.includes(id)).slice(0, data.required_seats);
                rebookSeats.forEach(id => {
                    $('#rebookSeatMap .seat').filter(function() {
                        return !$(this).hasClass('comfort-room') && seatId(this) === id;
                    }).addClass('selected');
                });
            }

            function updateSeatAssignment() {
                const required = Number(rebookAvailability?.required_seats || 0);
                const originalSeats = [...(rebookData?.booking?.seats || [])].sort().join('|');
                const selectedSeats = [...rebookSeats].sort().join('|');
                const hasRequiredSeats = rebookSeats.length === required;
                const hasChanged = rebookType === 'change_seat' ? selectedSeats !== originalSeats :
                    (rebookType === 'change_date' ? $('#rebookDate').val() !== rebookData.booking.date : true);
                $('#rebookAssignmentList').html(rebookSeats.map((seat, index) =>
                    `<span class="rebook-assignment">Ticket ${index + 1}<br>${escapeHtml(formatSeatLabel(seat))}</span>`
                ).join(''));
                const pendingText = hasRequiredSeats && !hasChanged
                    ? (rebookType === 'change_date' ? 'Choose a different date' : 'Choose a different seat')
                    : `${rebookSeats.length} of ${required} assigned`;
                $('#rebookSeatStatus').text(hasRequiredSeats && hasChanged ? '✓ All assigned' : pendingText);
                $('#rebookContinueBtn').prop('disabled', !hasRequiredSeats || !hasChanged);
            }

            $('#rebookDate, #rebookTrip').on('change', loadAvailability);

            $(document).on('click.rebook', '#rebookSeatMap .seat:not(.disabled-seat):not(.booked-seat):not(.comfort-room)', function() {
                const id = seatId(this);
                const required = Number(rebookAvailability?.required_seats || 0);

                if ($(this).hasClass('selected')) {
                    $(this).removeClass('selected');
                    rebookSeats = rebookSeats.filter(seat => seat !== id);
                } else if (rebookSeats.length < required) {
                    $(this).addClass('selected');
                    rebookSeats.push(id);
                } else {
                    notify('error', `Only ${required} seat(s) may be selected.`);
                }

                updateSeatAssignment();
            });

            function fillReview() {
                const before = rebookAvailability.before;
                const after = rebookAvailability.after;
                $('#reviewPassengerType').text(`${before.passenger_name} · ${before.passenger_type}`);
                $('#reviewReference').text(before.reference);
                $('#reviewBeforeDate').text(before.date_display);
                $('#reviewAfterDate').text(after.date_display);
                $('#reviewBeforeTime').text(before.time);
                $('#reviewAfterTime').text(after.time);
                $('#reviewBeforeBus').text(before.bus_type);
                $('#reviewAfterBus').text(after.bus_type);
                $('#reviewBeforeSeat').text(formatSeatLabel(before.seats));
                $('#reviewAfterSeat').text(formatSeatLabel(rebookSeats));
            }

            $('#rebookContinueBtn').on('click', function() {
                if (rebookStage === 'type') {
                    showStage('selection');
                    configureSelectionStage();
                    return;
                }

                if (rebookStage === 'selection' && rebookAvailability &&
                    rebookSeats.length === Number(rebookAvailability.required_seats)) {
                    fillReview();
                    showStage('review');
                }
            });

            $('#rebookBackBtn').on('click', function() {
                if (rebookStage === 'type') {
                    rebookModal.hide();
                } else if (rebookStage === 'selection') {
                    showStage('type');
                } else {
                    showStage('selection');
                    updateSeatAssignment();
                }
            });

            $('#rebookConfirmBtn').on('click', function() {
                const button = $(this);
                const originalLabel = button.html();
                const printWindow = window.open('', '_blank');
                const payload = {
                    _token: csrfToken,
                    type: rebookType,
                    date: rebookType === 'change_seat' ? rebookData.booking.date : $('#rebookDate').val(),
                    trip_id: rebookType === 'new_trip' ? $('#rebookTrip').val() : rebookData.booking.trip_id,
                    seats: rebookSeats
                };

                button.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Confirming…');
                $.ajax({
                    url: rebookData.confirm_url,
                    method: 'POST',
                    data: payload,
                    dataType: 'json'
                }).done(function(result) {
                    notify('success', result.message);
                    if (printWindow) {
                        printWindow.location = result.print_url;
                    } else {
                        window.open(result.print_url, '_blank');
                    }
                    rebookModal.hide();
                    setTimeout(() => window.location.reload(), 1200);
                }).fail(function(xhr) {
                    if (printWindow) printWindow.close();
                    notify('error', validationMessage(xhr));
                    button.prop('disabled', false).html(originalLabel);
                    showStage('selection');
                    loadAvailability();
                });
            });
        })(jQuery);

        (function($) {
            const refundModal = new bootstrap.Modal(document.getElementById('refundTicketModal'));
            const currency = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
            let refundData = null;
            let refundReason = '';

            const formatMoney = value => currency.format(Number(value) || 0);

            function refundError(xhr) {
                const errors = xhr.responseJSON?.errors;
                return errors ? Object.values(errors).flat()[0] :
                    (xhr.responseJSON?.message || 'Unable to process this refund.');
            }

            function updateRefundAmount() {
                if (!refundData) return false;
                const amount = Number($('#refundAmount').val()) || 0;
                const valid = amount > 0 && amount <= Number(refundData.fare);
                const percent = Number(refundData.fare) > 0 ? Math.round(amount / Number(refundData.fare) * 100) : 0;
                $('#refundPercentLabel').text(`${percent}% of fare`);
                $('#refundTotal').text(formatMoney(amount));
                $('#refundAmountError').text(amount > Number(refundData.fare) ?
                    `Refund cannot exceed ${formatMoney(refundData.fare)}.` : '');
                validateRefundForm();
                return valid;
            }

            function validateRefundForm() {
                if (!refundData) return false;
                const amount = Number($('#refundAmount').val()) || 0;
                const valid = refundReason && amount > 0 && amount <= Number(refundData.fare) &&
                    $('#refundRemarks').val().trim() && $('#refundAuthorizationCode').val().trim();
                $('#refundReviewBtn').prop('disabled', !valid);
                return Boolean(valid);
            }

            $(document).on('click', '.refund-ticket-btn', function(event) {
                event.preventDefault();
                refundData = null;
                refundReason = '';
                $('#refundLoading').removeClass('d-none');
                $('#refundFormStage, #refundReviewStage').addClass('d-none');
                $('#refundReviewBtn, #refundCancelBtn').removeClass('d-none');
                $('#refundBackBtn, #refundConfirmBtn').addClass('d-none');
                $('#refundReviewBtn').prop('disabled', true);
                refundModal.show();

                $.getJSON($(this).data('refund-url')).done(function(data) {
                    refundData = data;
                    $('#refundPnr').text(data.pnr);
                    $('#refundPassenger').text(data.passenger_name);
                    $('#refundTicketMeta').text(`${data.passenger_type} · Seat ${formatSeatLabel(data.seat)} · Fare ${formatMoney(data.fare)}`);
                    $('#refundFareLabel').text(formatMoney(data.fare));
                    $('#refundCashier').text(data.processed_by);
                    $('#refundRemarks, #refundAuthorizationCode').val('');
                    $('#refundReasonChips').html(data.reasons.map(reason =>
                        `<button type="button" class="refund-reason-chip" data-reason="${$('<div>').text(reason).html()}">${$('<div>').text(reason).html()}</button>`
                    ).join(''));
                    $('#refundAmount').attr('max', data.fare).val(data.default_refund);
                    $('.refund-chip').removeClass('active').filter('[data-refund-percent="50"]').addClass('active');
                    $('#refundLoading').addClass('d-none');
                    $('#refundFormStage').removeClass('d-none');
                    updateRefundAmount();
                }).fail(function(xhr) {
                    notify('error', refundError(xhr));
                    refundModal.hide();
                });
            });

            $(document).on('click', '.refund-reason-chip', function() {
                refundReason = $(this).data('reason');
                $('.refund-reason-chip').removeClass('active');
                $(this).addClass('active');
                validateRefundForm();
            });

            $('.refund-chip').on('click', function() {
                if (!refundData) return;
                $('.refund-chip').removeClass('active');
                $(this).addClass('active');
                $('#refundAmount').val((Number(refundData.fare) * Number($(this).data('refund-percent')) / 100).toFixed(2));
                updateRefundAmount();
            });

            $('#refundAmount').on('input', function() {
                $('.refund-chip').removeClass('active');
                updateRefundAmount();
            });
            $('#refundRemarks, #refundAuthorizationCode').on('input', validateRefundForm);

            $('#refundReviewBtn').on('click', function() {
                if (!validateRefundForm()) return;
                $('#refundReviewTicket').text(`${refundData.pnr} / ${refundData.reference}`);
                $('#refundReviewPassenger').text(`${refundData.passenger_name} / ${formatSeatLabel(refundData.seat)}`);
                $('#refundReviewReason').text(refundReason);
                $('#refundReviewRemarks').text($('#refundRemarks').val().trim());
                $('#refundReviewFare').text(formatMoney(refundData.fare));
                $('#refundReviewAmount').text(formatMoney($('#refundAmount').val()));
                $('#refundFormStage, #refundReviewBtn, #refundCancelBtn').addClass('d-none');
                $('#refundReviewStage, #refundBackBtn, #refundConfirmBtn').removeClass('d-none');
            });

            $('#refundBackBtn').on('click', function() {
                $('#refundReviewStage, #refundBackBtn, #refundConfirmBtn').addClass('d-none');
                $('#refundFormStage, #refundReviewBtn, #refundCancelBtn').removeClass('d-none');
            });

            $('#refundConfirmBtn').on('click', function() {
                if (!refundData) return;
                const button = $(this);
                const originalLabel = button.html();
                button.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Confirming...');

                $.ajax({
                    url: refundData.confirm_url,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        _token: "{{ csrf_token() }}",
                        reason: refundReason,
                        refund_amount: $('#refundAmount').val(),
                        remarks: $('#refundRemarks').val().trim(),
                        authorization_code: $('#refundAuthorizationCode').val()
                    }
                }).done(function(result) {
                    notify('success', result.message);
                    refundModal.hide();
                    setTimeout(() => window.location.href = result.redirect_url, 900);
                }).fail(function(xhr) {
                    notify('error', refundError(xhr));
                    button.prop('disabled', false).html(originalLabel);
                    $('#refundBackBtn').trigger('click');
                });
            });
        })(jQuery);

        (function($) {
            const cancelModal = new bootstrap.Modal(document.getElementById('cancelTicketModal'));
            const currency = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
            let cancelData = null;
            let cancelAuthorizationInFlight = false;

            const formatMoney = value => currency.format(Number(value) || 0);
            const escapeHtml = value => $('<div>').text(value ?? '').html();

            function cancelError(xhr) {
                const errors = xhr.responseJSON?.errors;
                return errors ? Object.values(errors).flat()[0] :
                    (xhr.responseJSON?.message || 'Unable to process this cancellation.');
            }

            function validateCancelForm() {
                const valid = cancelData && $('#cancelReason').val().trim() &&
                    $('#cancelAuthorizationCode').val().trim();
                $('#cancelReviewBtn').prop('disabled', !valid);
                return Boolean(valid);
            }

            function showCancelForm() {
                $('#cancelReviewStage, #cancelBackBtn, #cancelConfirmBtn').addClass('d-none');
                $('#cancelFormStage, #cancelKeepBtn, #cancelReviewBtn').removeClass('d-none');
            }

            function resetCancelAuthorization() {
                $('#cancelAuthorizationStatus').removeClass('is-success is-error').empty();
            }

            $(document).on('click', '.cancel-ticket-btn', function(event) {
                event.preventDefault();
                cancelData = null;
                $('#cancelLoading').removeClass('d-none');
                $('#cancelFormStage, #cancelReviewStage').addClass('d-none');
                $('#cancelKeepBtn, #cancelReviewBtn').removeClass('d-none');
                $('#cancelBackBtn, #cancelConfirmBtn').addClass('d-none');
                $('#cancelReviewBtn').prop('disabled', true);
                $('#cancelAuthorizationCode').attr('type', 'password');
                resetCancelAuthorization();
                cancelModal.show();

                $.getJSON($(this).data('cancel-url')).done(function(data) {
                    cancelData = data;
                    $('#cancelPnr').text(data.pnr);
                    $('#cancelPassenger').text(data.passenger_name);
                    $('#cancelTicketMeta').text(`${data.passenger_type} - Seat ${formatSeatLabel(data.seat)} - Ref. ${data.reference}`);
                    $('#cancelFare').text(formatMoney(data.fare));
                    $('#cancelReason, #cancelAuthorizationCode').val('');
                    $('#cancelReasonChips').html(data.reasons.map(reason =>
                        `<button type="button" class="cancel-reason-chip" data-reason="${escapeHtml(reason)}">${escapeHtml(reason)}</button>`
                    ).join(''));
                    $('#cancelLoading').addClass('d-none');
                    $('#cancelFormStage').removeClass('d-none');
                }).fail(function(xhr) {
                    notify('error', cancelError(xhr));
                    cancelModal.hide();
                });
            });

            $(document).on('click', '.cancel-reason-chip', function() {
                $('#cancelReason').val($(this).data('reason')).trigger('input').focus();
                $('.cancel-reason-chip').removeClass('active');
                $(this).addClass('active');
            });

            $('#cancelReason').on('input', function() {
                const reason = $(this).val().trim();
                $('.cancel-reason-chip').each(function() {
                    $(this).toggleClass('active', $(this).data('reason') === reason);
                });
                validateCancelForm();
            });

            $('#cancelAuthorizationCode').on('input', function() {
                resetCancelAuthorization();
                validateCancelForm();
            });

            $('#toggleCancelCode').on('click', function() {
                const input = $('#cancelAuthorizationCode');
                input.attr('type', input.attr('type') === 'password' ? 'text' : 'password');
            });

            $('#cancelReviewBtn').on('click', function() {
                if (!validateCancelForm() || cancelAuthorizationInFlight) return;

                const button = $(this);
                const originalLabel = button.html();
                cancelAuthorizationInFlight = true;
                button.prop('disabled', true).html('<i class="las la-spinner la-spin me-1"></i> Validating...');
                resetCancelAuthorization();

                $.ajax({
                    url: cancelData.authorization_url,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        _token: "{{ csrf_token() }}",
                        authorization_code: $('#cancelAuthorizationCode').val()
                    }
                }).done(function(result) {
                    const authorizedName = result.authorized_by?.name || 'Authorized personnel';
                    $('#cancelAuthorizationStatus')
                        .addClass('is-success')
                        .html(`<i class="las la-check"></i> Authorized by ${escapeHtml(authorizedName)}`);
                    $('#cancelReviewTicket').text(`${cancelData.pnr} / ${cancelData.reference}`);
                    $('#cancelReviewPassenger').text(`${cancelData.passenger_name} / ${formatSeatLabel(cancelData.seat)}`);
                    $('#cancelReviewReason').text($('#cancelReason').val().trim());
                    $('#cancelReviewAuthorizedBy').text(authorizedName);
                    $('#cancelReviewFare').text(formatMoney(cancelData.fare));
                    $('#cancelFormStage, #cancelKeepBtn, #cancelReviewBtn').addClass('d-none');
                    $('#cancelReviewStage, #cancelBackBtn, #cancelConfirmBtn').removeClass('d-none');
                }).fail(function(xhr) {
                    $('#cancelAuthorizationStatus')
                        .addClass('is-error')
                        .html(`<i class="las la-times-circle"></i> ${escapeHtml(cancelError(xhr))}`);
                    $('#cancelAuthorizationCode').trigger('focus').select();
                }).always(function() {
                    cancelAuthorizationInFlight = false;
                    button.html(originalLabel);
                    validateCancelForm();
                });
            });

            $('#cancelBackBtn').on('click', showCancelForm);

            $('#cancelConfirmBtn').on('click', function() {
                if (!cancelData) return;
                const button = $(this);
                const originalLabel = button.html();
                const acknowledgmentWindow = window.open('', '_blank');
                button.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Confirming...');

                $.ajax({
                    url: cancelData.confirm_url,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        _token: "{{ csrf_token() }}",
                        reason: $('#cancelReason').val().trim(),
                        authorization_code: $('#cancelAuthorizationCode').val()
                    }
                }).done(function(result) {
                    notify('success', result.message);
                    if (acknowledgmentWindow) {
                        acknowledgmentWindow.location = result.acknowledgment_url;
                    } else {
                        window.open(result.acknowledgment_url, '_blank');
                    }
                    cancelModal.hide();
                    setTimeout(() => window.location.href = result.redirect_url, 900);
                }).fail(function(xhr) {
                    if (acknowledgmentWindow) acknowledgmentWindow.close();
                    notify('error', cancelError(xhr));
                    button.prop('disabled', false).html(originalLabel);
                    showCancelForm();
                });
            });
        })(jQuery);

        (function($) {
            const voidModal = new bootstrap.Modal(document.getElementById('voidTicketModal'));
            const currency = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
            let voidData = null;
            let voidReason = '';

            const formatMoney = value => currency.format(Number(value) || 0);
            const escapeHtml = value => $('<div>').text(value ?? '').html();

            function voidError(xhr) {
                const errors = xhr.responseJSON?.errors;
                return errors ? Object.values(errors).flat()[0] :
                    (xhr.responseJSON?.message || 'Unable to void this ticket.');
            }

            function validateVoidForm() {
                const valid = voidData && voidReason && $('#voidRemarks').val().trim() &&
                    $('#voidAuthorizationCode').val().trim();
                $('#voidConfirmBtn').prop('disabled', !valid);
                return Boolean(valid);
            }

            $(document).on('click', '.void-ticket-btn', function(event) {
                event.preventDefault();
                voidData = null;
                voidReason = '';
                $('#voidLoading').removeClass('d-none');
                $('#voidFormStage').addClass('d-none');
                $('#voidConfirmBtn').prop('disabled', true).html('<i class="las la-ban me-1"></i> Void (1)');
                $('#voidAuthorizationCode').attr('type', 'password');
                voidModal.show();

                $.getJSON($(this).data('void-url')).done(function(data) {
                    voidData = data;
                    $('#voidPnr').text(data.pnr);
                    $('#voidPassenger').text(data.passenger_name);
                    $('#voidTicketMeta').text(`${data.passenger_type} - Seat ${formatSeatLabel(data.seat)} - Ref. ${data.reference}`);
                    $('#voidFare, #voidReturnAmount').text(formatMoney(data.fare));
                    $('#voidRemarks, #voidAuthorizationCode').val('');
                    $('#voidReasonChips').html(data.reasons.map(reason =>
                        `<button type="button" class="cancel-reason-chip void-reason-chip" data-reason="${escapeHtml(reason)}">${escapeHtml(reason)}</button>`
                    ).join(''));
                    $('#voidLoading').addClass('d-none');
                    $('#voidFormStage').removeClass('d-none');
                }).fail(function(xhr) {
                    notify('error', voidError(xhr));
                    voidModal.hide();
                });
            });

            $(document).on('click', '.void-reason-chip', function() {
                voidReason = $(this).data('reason');
                $('.void-reason-chip').removeClass('active');
                $(this).addClass('active');
                validateVoidForm();
            });

            $('#voidRemarks, #voidAuthorizationCode').on('input', validateVoidForm);

            $('#toggleVoidCode').on('click', function() {
                const input = $('#voidAuthorizationCode');
                input.attr('type', input.attr('type') === 'password' ? 'text' : 'password');
            });

            $('#voidConfirmBtn').on('click', function() {
                if (!validateVoidForm()) return;
                const button = $(this);
                const originalLabel = button.html();
                button.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Voiding...');

                $.ajax({
                    url: voidData.confirm_url,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        _token: "{{ csrf_token() }}",
                        reason: voidReason,
                        remarks: $('#voidRemarks').val().trim(),
                        authorization_code: $('#voidAuthorizationCode').val()
                    }
                }).done(function(result) {
                    notify('success', result.message);
                    voidModal.hide();
                    setTimeout(() => window.location.href = result.redirect_url, 700);
                }).fail(function(xhr) {
                    notify('error', voidError(xhr));
                    button.prop('disabled', false).html(originalLabel);
                });
            });
        })(jQuery);
    </script>
@endpush
