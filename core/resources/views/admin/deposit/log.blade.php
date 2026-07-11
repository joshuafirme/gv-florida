@extends('admin.layouts.app')
@section('panel')
    @php
        $search = request('search');
        $date = request('date');
        $status = isset($status) ? $status : 'all';
        $method_code = request('method_code') ?: 'all';
    @endphp
    <div class="row justify-content-center">
        @if (request()->routeIs('admin.deposit.list') || request()->routeIs('admin.deposit.method'))
            <div class="col-12">
                @include('admin.deposit.widget')
            </div>
        @endif

        @if ($status == 'pending')
            <div class="col-md-12 mb-3">
                <form action="{{ route('admin.deposit.pending.scan') }}" method="GET" id="qrScanForm">
                    <div class="row">
                        <div class="col-xl-5 col-lg-7">
                            <label for="qrScanInput" class="form-label">@lang('Scan QR')</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="las la-qrcode"></i></span>
                                <input type="text" name="scan" id="qrScanInput" class="form-control"
                                    value="{{ old('scan') }}" placeholder="@lang('Scan QR, PNR, reference no., or ticket no.')"
                                    autocomplete="off" autofocus required>
                                <button class="btn btn--primary" type="submit">@lang('Open POS')</button>
                            </div>
                            <small class="text-muted">@lang('A successful scan opens the pending payment in the POS automatically.')</small>
                        </div>
                    </div>
                </form>
            </div>
        @endif

        <div class="col-md-12 mb-3">
            <form action="#">
                <div class="d-flex flex-wrap gap-4">
                    <div style="width: 250px;">
                        <label for="">Date</label>
                        <input name="date" type="search" class="datepicker-here form-control bg--white pe-2 date-range"
                            placeholder="@lang('Start Date - End Date')" autocomplete="off" value="{{ request()->date }}">
                    </div>
                    <div style="width: 250px;">
                        <label for="">Payment Method</label>
                        @php
                            $gateways = App\Models\GatewayCurrency::get();
                        @endphp
                        <select name="method_code" class="select2" required>
                            <option value="all">@lang('All')</option>
                            @foreach ($gateways as $gateway)
                                <option value="{{ $gateway->method_code }}"
                                    {{ $gateway->method_code == $method_code ? 'selected' : '' }}>{{ $gateway->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="align-self-end">
                        <button class="btn btn--primary w-100 h-45"><i class="fas fa-filter"></i> Filter</button>
                    </div>
                    <div class="align-self-end">
                        <a class="btn btn--success w-100 h-45"
                            href="{{ url("/admin/deposit/export?status=$status&date=$date&search=$search&method_code=$method_code") }}"><i
                                class="fa-solid fa-file-export"></i> Export</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Gateway | Transaction')</th>
                                    <th>@lang('Initiated')</th>
                                    <th>@lang('PNR')</th>
                                    <th>Reference #</th>
                                    <th>@lang('User')</th>
                                    <th>Trip</th>
                                    <th>Seats</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Passenger type')</th>
                                    <th>@lang('Status')</th>
                                    @if ($status == 'approved')
                                        <th>@lang('Processed By')</th>
                                    @endif
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deposits as $deposit)
                                    @php
                                        $details = $deposit->detail ? json_encode($deposit->detail) : null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-bold">
                                                <a
                                                    href="{{ appendQuery('method', $deposit->method_code < 5000 ? @$deposit->gateway->alias : $deposit->method_code) }}">
                                                    @if ($deposit->method_code < 5000)
                                                        @if (@$deposit->gateway->name == 'Paynamics')
                                                            {{ getPaynamicsPChannel($deposit->pchannel, true) }}
                                                        @else
                                                            {{ __(@$deposit->gateway->name) }}
                                                        @endif
                                                    @else
                                                        @lang('Google Pay')
                                                    @endif
                                                </a>
                                            </span>
                                            <br>
                                            <small> {{ $deposit->trx }} </small>
                                        </td>

                                        <td>
                                            {{ showDateTime($deposit->created_at) }}<br>{{ diffForHumans($deposit->created_at) }}
                                        </td>
                                        <td>{{ $deposit->bookedTicket->pnr_number }}</td>
                                        <td>{{ implodeSeriesNo($deposit) }}</td>
                                        <td>
                                            @if ($deposit->user)
                                                <span class="fw-bold">{{ $deposit->user->fullname }}</span>
                                                <br>
                                                <span class="small">
                                                    <a
                                                        href="{{ appendQuery('search', @$deposit->user->username) }}"><span>@</span>{{ $deposit->user->username }}</a>
                                                </span>
                                            @else
                                                {{ $deposit->bookedTicket->kiosk->name }}
                                                <div>{{ $deposit->bookedTicket->kiosk->uid }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark text-end">
                                                {{ $deposit->bookedTicket->pickup->name }}
                                                <i class="las la-long-arrow-alt-right mx-1 text-muted"></i>
                                                {{ $deposit->bookedTicket->drop->name }}
                                            </span>
                                        </td>
                                        <td>{{ $deposit->bookedTicket->seats ? implode(',', $deposit->bookedTicket->seats) : '' }}
                                        </td>
                                        <td>
                                            Fare: {{ showAmount($deposit->amount) }}
                                            <div>Discount: {{ $deposit?->userDiscount?->amount ?: '-' }}</div>
                                            <div>Final Amount:
                                                {{ showAmount($deposit->final_amount) }}</div>
                                        </td>
                                        <td>{{ getPassengerType($deposit) }}</td>
                                        <td>
                                            @php echo $deposit->statusBadge @endphp
                                        </td>
                                        @if ($status == 'approved')
                                            <td>
                                                {{ $deposit->processedBy ? $deposit->processedBy->name : '-' }}
                                            </td>
                                        @endif
                                        <td>
                                            @if ($status == 'pending')
                                                <button type="button" class="btn btn-sm btn-outline--primary ms-1 open-pos-btn"
                                                    data-scan="{{ $deposit->trx }}">
                                                    <i class="la la-desktop"></i> @lang('Process')
                                                </button>
                                            @else
                                                <a href="{{ route('admin.deposit.details', $deposit->id) }}"
                                                    class="btn btn-sm btn-outline--primary ms-1">
                                                    <i class="la la-desktop"></i> @lang('Details')
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                @if ($deposits->hasPages())
                    <div class="card-footer py-4">
                        @php echo paginateLinks($deposits) @endphp
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>

    @if ($status == 'pending')
        <div class="modal fade payment-flow-modal" id="processPaymentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Process Payment — <span data-pos="pnr"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="payment-summary-grid mb-3">
                            <div>
                                <span class="payment-label">Trip</span>
                                <strong data-pos="trip"></strong>
                            </div>
                            <div>
                                <span class="payment-label">Departure</span>
                                <strong data-pos="departure_time"></strong>
                            </div>
                        </div>

                        <div class="payment-ticket-box mb-3">
                            <div class="payment-section-title">Ticket</div>
                            <div id="processTicketRows"></div>
                        </div>

                        <div class="payment-amount-box">
                            <div class="payment-total-row">
                                <span>Amount due</span>
                                <strong class="payment-total" data-pos-currency="amount"></strong>
                            </div>
                            <label for="posCashReceived" class="payment-label mt-3 mb-1">Cash received</label>
                            <input type="number" min="0" step="0.01" inputmode="decimal" id="posCashReceived"
                                class="form-control payment-cash-input" placeholder="0.00">
                            <small class="text-danger d-block mt-1" id="posCashError"></small>
                            <div class="payment-change-row mt-3">
                                <span>Change</span>
                                <strong class="payment-change" id="posChange">₱0.00</strong>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer payment-modal-footer">
                        <button type="button" class="btn btn-link text-danger px-0" id="posRejectBtn">Reject</button>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light payment-secondary-btn"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn--primary payment-primary-btn" id="posProceedBtn"
                                disabled>Proceed <i class="las la-arrow-right ms-1"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade payment-flow-modal" id="confirmPaymentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Payment — <span data-confirm="pnr"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="payment-review-alert">
                            <i class="las la-exclamation-circle"></i>
                            <span>Please review the details below before settling. This will mark the booking paid and print
                                the reservation slip.</span>
                        </div>

                        <div class="payment-review-box payment-review-grid mt-3">
                            <div><span class="payment-label">Trip / Route</span><strong data-confirm="route"></strong></div>
                            <div><span class="payment-label">Bus Type</span><strong data-confirm="bus_type"></strong></div>
                            <div><span class="payment-label">Departure Time</span><strong data-confirm="departure_time"></strong></div>
                            <div><span class="payment-label">Travel Date</span><strong data-confirm="travel_date"></strong></div>
                            <div><span class="payment-label">PNR</span><strong data-confirm="pnr"></strong></div>
                            <div><span class="payment-label">Seat(s)</span><strong data-confirm="seats"></strong></div>
                        </div>

                        <div class="payment-ticket-box mt-3">
                            <div class="payment-section-title">Ticket</div>
                            <div id="confirmTicketRows"></div>
                        </div>

                        <div class="payment-review-box mt-3">
                            <div class="payment-detail-row"><span>Processed by</span><strong data-confirm="processed_by"></strong></div>
                            <div class="payment-detail-row"><span>Amount to be paid</span><strong data-confirm-currency="amount"></strong></div>
                            <div class="payment-detail-row"><span>Amount received</span><strong id="confirmCashReceived"></strong></div>
                            <div class="payment-detail-row payment-change-total"><span>Change to give</span><strong
                                    id="confirmChange"></strong></div>
                        </div>
                    </div>
                    <div class="modal-footer payment-modal-footer">
                        <button type="button" class="btn btn-light payment-secondary-btn" id="posBackBtn">
                            <i class="las la-arrow-left me-1"></i> Back
                        </button>
                        <button type="button" class="btn btn--primary payment-primary-btn" id="posConfirmPrintBtn">
                            <i class="las la-print me-1"></i> Confirm & Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="posRejectModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" id="posRejectForm">
                        @csrf
                        <input type="hidden" name="id" id="posRejectDepositId">
                        <div class="modal-body">
                            <label class="form-label" for="posRejectMessage">Reason for rejection</label>
                            <textarea class="form-control" name="message" id="posRejectMessage" rows="4" maxlength="255" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn--danger">Reject Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder='PNR / Username / TRX' />
@endpush
@push('style-lib')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/global/css/daterangepicker.css') }}">
@endpush
@push('style')
    <style>
        .payment-flow-modal .modal-dialog {
            max-width: 680px;
        }

        .payment-flow-modal .modal-content {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 20px 55px rgba(0, 0, 0, .25);
            overflow: hidden;
        }

        .payment-flow-modal .modal-header,
        .payment-flow-modal .modal-footer {
            padding: 20px 24px;
            border-color: #e5e7eb;
        }

        .payment-flow-modal .modal-title {
            color: #111827;
            font-size: 17px;
            font-weight: 700;
        }

        .payment-flow-modal .modal-body {
            padding: 22px 24px;
            color: #20242b;
        }

        .payment-summary-grid,
        .payment-review-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 42px;
        }

        .payment-label {
            display: block;
            color: #6b7280;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .payment-summary-grid strong,
        .payment-review-grid strong {
            display: block;
            font-size: 14px;
            margin-top: 3px;
        }

        .payment-ticket-box,
        .payment-amount-box,
        .payment-review-box {
            background: #f5f5f7;
            border: 1px solid #dedfe3;
            border-radius: 12px;
            overflow: hidden;
        }

        .payment-section-title {
            background: #ececef;
            color: #626772;
            font-size: 11px;
            letter-spacing: .04em;
            padding: 8px 18px;
            text-transform: uppercase;
        }

        .payment-ticket-row,
        .payment-detail-row {
            align-items: center;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 12px 18px;
        }

        .payment-ticket-row + .payment-ticket-row,
        .payment-detail-row + .payment-detail-row {
            border-top: 1px solid #dedfe3;
        }

        .payment-ticket-name {
            font-size: 14px;
            font-weight: 600;
        }

        .payment-ticket-meta {
            color: #737783;
            font-size: 12px;
            margin-left: 7px;
        }

        .payment-ticket-fare,
        .payment-detail-row strong {
            color: #101318;
            font-size: 15px;
            white-space: nowrap;
        }

        .payment-amount-box {
            padding: 18px;
        }

        .payment-total-row,
        .payment-change-row {
            align-items: center;
            display: flex;
            justify-content: space-between;
        }

        .payment-total {
            color: #df1465;
            font-size: 25px;
        }

        .payment-cash-input {
            background: #ececef;
            border-color: #d2d4d9;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 700;
            height: 52px;
        }

        .payment-change,
        .payment-change-total strong {
            color: #07852d !important;
            font-size: 21px !important;
        }

        .payment-review-alert {
            align-items: flex-start;
            background: #fff6fa;
            border: 1px solid #f29abd;
            border-radius: 10px;
            color: #34363b;
            display: flex;
            font-size: 13px;
            gap: 10px;
            line-height: 1.45;
            padding: 13px 16px;
        }

        .payment-review-alert i {
            color: #e30d64;
            font-size: 19px;
        }

        .payment-review-box.payment-review-grid {
            padding: 16px 18px;
        }

        .payment-detail-row span {
            font-size: 13px;
        }

        .payment-change-total {
            font-size: 16px;
            font-weight: 700;
        }

        .payment-modal-footer {
            justify-content: space-between;
        }

        .payment-primary-btn,
        .payment-secondary-btn {
            border-radius: 8px;
            font-weight: 600;
            min-height: 42px;
            padding-left: 18px;
            padding-right: 18px;
        }

        @media (max-width: 575.98px) {
            .payment-summary-grid,
            .payment-review-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .payment-flow-modal .modal-header,
            .payment-flow-modal .modal-footer,
            .payment-flow-modal .modal-body {
                padding-left: 16px;
                padding-right: 16px;
            }
        }
    </style>
@endpush

@push('script')
    <script src="{{ asset('assets/global/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/daterangepicker.min.js') }}"></script>
    @if ($status == 'pending')
        <script src="{{ asset('assets/admin/js/vendor/qz-tray.min.js') }}"></script>
        <script src="{{ asset('assets/admin/js/qz-printer.js') }}"></script>
    @endif
    <script>
        (function($) {
            "use strict"

            @if ($status == 'pending')
                const currencyFormatter = new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP'
                });
                const processPaymentModal = new bootstrap.Modal(document.getElementById('processPaymentModal'));
                const confirmPaymentModal = new bootstrap.Modal(document.getElementById('confirmPaymentModal'));
                const rejectPaymentModal = new bootstrap.Modal(document.getElementById('posRejectModal'));
                let activePayment = null;
                const appBaseUrl = @json(url('/'));
                const params = new URLSearchParams(window.location.search);

                if (params.get('newly_approved')) {
                    const ticketUrl = localStorage.getItem('to_print_ticket');
                    if (ticketUrl) {
                        setTimeout(() => {
                            window.open(ticketUrl, '_blank');
                            localStorage.removeItem('to_print_ticket');
                        }, 1000);
                    }
                    params.delete('newly_approved');
                    const cleanUrl = `${window.location.pathname}${params.toString() ? `?${params.toString()}` : ''}`;
                    window.history.replaceState({}, document.title, cleanUrl);
                }

                const formatCurrency = value => currencyFormatter.format(Number(value) || 0);
                const escapeHtml = value => $('<div>').text(value ?? '').html();
                const absoluteUrl = value => {
                    if (!value) return value;
                    if (/^https?:\/\//i.test(value)) return value;
                    return `${appBaseUrl.replace(/\/$/, '')}/${String(value).replace(/^\/+/, '')}`;
                };

                function ticketRowsHtml(payment) {
                    return payment.tickets.map(ticket => `
                        <div class="payment-ticket-row">
                            <div>
                                <span class="payment-ticket-name">${escapeHtml(payment.passenger_name)}</span>
                                <span class="payment-ticket-meta">${escapeHtml(payment.passenger_type)} · Seat ${escapeHtml(ticket.seat)}</span>
                            </div>
                            <strong class="payment-ticket-fare">${formatCurrency(ticket.fare)}</strong>
                        </div>
                    `).join('');
                }

                function fillProcessModal(payment) {
                    $('[data-pos="pnr"]').text(payment.pnr);
                    $('[data-pos="trip"]').text(payment.trip);
                    $('[data-pos="departure_time"]').text(payment.departure_time);
                    $('[data-pos-currency="amount"]').text(formatCurrency(payment.amount));
                    $('#processTicketRows').html(ticketRowsHtml(payment));
                    $('#posCashReceived').val('');
                    $('#posCashError').text('');
                    $('#posChange').text(formatCurrency(0));
                    $('#posProceedBtn').prop('disabled', true);
                }

                function updateTenderedAmount() {
                    const cash = Number($('#posCashReceived').val()) || 0;
                    const change = cash - Number(activePayment?.amount || 0);
                    const valid = cash >= Number(activePayment?.amount || 0);

                    $('#posChange').text(formatCurrency(Math.max(change, 0)));
                    $('#posCashError').text(cash > 0 && !valid ? 'Cash received is less than the amount due.' : '');
                    $('#posProceedBtn').prop('disabled', !valid);

                    return valid;
                }

                function fillConfirmModal() {
                    const cash = Number($('#posCashReceived').val()) || 0;
                    const change = cash - Number(activePayment.amount);
                    const seats = activePayment.tickets.map(ticket => ticket.seat).join(', ');

                    Object.entries({
                        pnr: activePayment.pnr,
                        route: activePayment.route,
                        bus_type: activePayment.bus_type,
                        departure_time: activePayment.departure_time,
                        travel_date: activePayment.travel_date,
                        seats,
                        processed_by: activePayment.processed_by
                    }).forEach(([key, value]) => $(`[data-confirm="${key}"]`).text(value));

                    $('[data-confirm-currency="amount"]').text(formatCurrency(activePayment.amount));
                    $('#confirmCashReceived').text(formatCurrency(cash));
                    $('#confirmChange').text(formatCurrency(change));
                    $('#confirmTicketRows').html(ticketRowsHtml(activePayment));
                }

                function openPendingPayment(scanValue, trigger, afterOpen = null) {
                    const button = $(trigger);
                    const originalLabel = button.length ? button.html() : '';

                    if (!String(scanValue || '').trim()) {
                        notify('error', 'Enter or scan a PNR, reference no., transaction no., or ticket no.');
                        return;
                    }

                    if (button.length) {
                        button.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Loading');
                    }

                    $.ajax({
                        url: @json(route('admin.deposit.pending.scan')),
                        method: 'GET',
                        data: {
                            scan: scanValue
                        },
                        dataType: 'json',
                        headers: {
                            Accept: 'application/json'
                        }
                    }).done(function(payment) {
                        activePayment = payment;
                        fillProcessModal(payment);

                        if (typeof afterOpen === 'function') {
                            afterOpen(payment);
                        }

                        processPaymentModal.show();
                    }).fail(function(xhr) {
                        notify('error', xhr.responseJSON?.message || 'Unable to find this pending payment.');
                    }).always(function() {
                        if (button.length) {
                            button.prop('disabled', false).html(originalLabel);
                        }
                    });
                }

                $('#qrScanForm').on('submit', function(event) {
                    event.preventDefault();
                    const form = this;
                    const scanInput = $('#qrScanInput');
                    const submitButton = $(form).find('[type="submit"]');

                    openPendingPayment(scanInput.val(), submitButton, function() {
                        $('#qrScanInput').val('');
                    });
                });

                $('.open-pos-btn').on('click', function() {
                    openPendingPayment($(this).data('scan'), this);
                });

                $('#qrScanForm').on('change', '#qrScanInput', function() {
                    if (this.value.trim()) {
                        this.form.requestSubmit();
                    }
                });

                $('#processPaymentModal').on('shown.bs.modal', function() {
                    $('#posCashReceived').trigger('focus');
                });

                $('#posCashReceived').on('input', updateTenderedAmount).on('keydown', function(event) {
                    if (event.key === 'Enter' && updateTenderedAmount()) {
                        event.preventDefault();
                        $('#posProceedBtn').trigger('click');
                    }
                });

                $('#posProceedBtn').on('click', function() {
                    if (!activePayment || !updateTenderedAmount()) return;

                    fillConfirmModal();
                    $('#processPaymentModal').one('hidden.bs.modal', () => confirmPaymentModal.show());
                    processPaymentModal.hide();
                });

                $('#posBackBtn').on('click', function() {
                    $('#confirmPaymentModal').one('hidden.bs.modal', () => processPaymentModal.show());
                    confirmPaymentModal.hide();
                });

                $('#posRejectBtn').on('click', function() {
                    if (!activePayment) return;

                    $('#posRejectForm').attr('action', activePayment.reject_url);
                    $('#posRejectDepositId').val(activePayment.deposit_id);
                    $('#posRejectMessage').val('');
                    $('#processPaymentModal').one('hidden.bs.modal', () => rejectPaymentModal.show());
                    processPaymentModal.hide();
                });

                async function printReservation(fileUrl) {
                    if (typeof qz === 'undefined' || typeof connectQZ !== 'function' || typeof getPrinter !== 'function') {
                        throw new Error('QZ Tray is not available. Please start QZ Tray and try again.');
                    }

                    await connectQZ();
                    const printer = await getPrinter();
                    const config = qz.configs.create(printer, {
                        scaleContent: true,
                        colorType: 'color'
                    });

                    return qz.print(config, [{
                        type: 'pdf',
                        format: 'file',
                        data: fileUrl,
                        options: {
                            autoRotate: true
                        }
                    }]);
                }

                $('#posConfirmPrintBtn').on('click', function() {
                    if (!activePayment) return;

                    const button = $(this);
                    const originalLabel = button.html();
                    button.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Processing...');

                    $.getJSON(activePayment.validate_url).then(function(validation) {
                        if (!validation.success) {
                            return $.Deferred().reject({
                                responseJSON: validation
                            }).promise();
                        }

                        return $.getJSON(activePayment.print_url, {
                            admin_request: true,
                            admin_id: {{ auth('admin')->id() }}
                        });
                    }).then(async function(result) {
                        if (!result.success || !result.file_url) {
                            throw new Error(result.message || 'Unable to create the reservation slip.');
                        }

                        const printUrl = absoluteUrl(result.file_url);
                        const openUrl = absoluteUrl(result.reservation_slip_url || result.file_url);

                        await printReservation(printUrl);
                        localStorage.setItem('to_print_ticket', openUrl);
                        notify('success', 'Payment confirmed and reservation slip prepared.');
                        confirmPaymentModal.hide();
                        const reloadParams = new URLSearchParams(window.location.search);
                        reloadParams.set('newly_approved', true);
                        setTimeout(() => {
                            window.location.search = reloadParams.toString();
                        }, 900);
                    }).catch(function(error) {
                        const message = error.responseJSON?.message || error.message ||
                            'Unable to confirm this payment.';
                        notify('error', message);
                        button.prop('disabled', false).html(originalLabel);
                    });
                });
            @endif

            const datePicker = $('.date-range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                },
                showDropdowns: true,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 15 Days': [moment().subtract(14, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(30, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month')
                        .endOf('month')
                    ],
                    'Last 6 Months': [moment().subtract(6, 'months').startOf('month'), moment().endOf('month')],
                    'This Year': [moment().startOf('year'), moment().endOf('year')],
                },
                maxDate: moment()
            });
            const changeDatePickerText = (event, startDate, endDate) => {
                $(event.target).val(startDate.format('MMMM DD, YYYY') + ' - ' + endDate.format('MMMM DD, YYYY'));
            }


            $('.date-range').on('apply.daterangepicker', (event, picker) => changeDatePickerText(event, picker
                .startDate, picker.endDate));


            if ($('.date-range').val()) {
                let dateRange = $('.date-range').val().split(' - ');
                $('.date-range').data('daterangepicker').setStartDate(new Date(dateRange[0]));
                $('.date-range').data('daterangepicker').setEndDate(new Date(dateRange[1]));
            }

        })(jQuery)
    </script>
@endpush
