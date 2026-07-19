@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-12">
            <div class="mb-3">
                <h4 class="mb-1">Voided Ticket</h4>
                <span class="text-muted">{{ $voids->total() }} {{ \Illuminate\Support\Str::plural('record', $voids->total()) }}</span>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two voided-ticket-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>PNR</th>
                                    <th>Reference No.</th>
                                    <th>Journey</th>
                                    <th>Trip</th>
                                    <th>Seat No.</th>
                                    <th>Fare</th>
                                    <th>Passenger</th>
                                    <th>Booking Source</th>
                                    <th>Payment Method</th>
                                    <th>Processed By</th>
                                    <th>Authorized By</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($voids as $ticketVoid)
                                    @php
                                        $ticket = $ticketVoid->bookedTicket;
                                        $slip = $ticketVoid->slipSeriesNumber;
                                        $snapshot = $ticketVoid->transaction_snapshot ?: [];
                                        $manifest = collect($ticket?->passenger_manifest ?: ($ticket?->deposit?->userDiscount?->passenger_manifest ?: []));
                                        $passenger = $manifest->first(fn ($item) => (string) ($item['seat'] ?? '') === (string) $slip?->seat) ?: [];
                                        $discounted = ($passenger['passenger_type'] ?? 'regular') === 'discounted';
                                        $passengerName = ($passenger['name'] ?? null) ?: ($ticket?->user?->fullname ?: 'Guest');
                                        $passengerType = $passenger
                                            ? ($discounted ? ($passenger['discount_name'] ?? 'Discounted') : 'Regular')
                                            : getPassengerType($ticket?->deposit);
                                        $paymentMethod = '-';
                                        if ($ticket?->deposit?->pchannel) {
                                            $paymentMethod = readPaymentChannel($ticket->deposit->pchannel);
                                        } elseif ($ticket?->deposit) {
                                            $paymentMethod = $ticket->deposit->gatewayCurrency()?->name ?: '-';
                                        }
                                    @endphp
                                    <tr>
                                        <td data-label="User">
                                            <strong>{{ $snapshot['booking_source'] ?? ($ticket?->kiosk_id ? ($ticket?->kiosk?->name ?: 'Kiosk') : ($ticket?->user?->fullname ?: 'Online')) }}</strong>
                                            <span class="voided-meta">{{ $snapshot['booking_source_reference'] ?? ($ticket?->kiosk_id ? $ticket?->kiosk?->uid : $ticket?->user?->username) }}</span>
                                        </td>
                                        <td data-label="PNR"><span class="text--primary fw-bold">{{ $snapshot['pnr'] ?? $ticket?->pnr_number }}</span></td>
                                        <td data-label="Reference No.">{{ $snapshot['reference'] ?? $ticketVoid->slip_series_number_id }}</td>
                                        <td data-label="Journey">
                                            {{ $snapshot['date_of_journey'] ?? showDateTime($ticket?->date_of_journey, 'M d, Y') }}
                                            <span class="voided-meta">{{ $snapshot['departure_time'] ?? ($ticket?->trip?->schedule?->start_from ? date('g:i A', strtotime($ticket->trip->schedule->start_from)) : '-') }}</span>
                                        </td>
                                        <td data-label="Trip">
                                            <strong>{{ $snapshot['bus_type'] ?? ($ticket?->trip?->fleetType?->name ?: '-') }}</strong>
                                            <span class="voided-meta">{{ $snapshot['trip_route'] ?? ($ticket?->pickup?->name . ' - ' . $ticket?->drop?->name) }}</span>
                                        </td>
                                        <td data-label="Seat No."><strong>{{ formatSeatLabel($snapshot['seat'] ?? $slip?->seat) }}</strong></td>
                                        <td data-label="Fare"><strong>{{ showAmount($ticketVoid->original_fare) }}</strong></td>
                                        <td data-label="Passenger">
                                            <strong>{{ $snapshot['passenger_name'] ?? $passengerName }}</strong>
                                            <span class="voided-meta">
                                                {{ $snapshot['passenger_type'] ?? $passengerType }}
                                                @if (($snapshot['passenger_id'] ?? ($passenger['id_number'] ?? null)) && ($snapshot['passenger_id'] ?? ($passenger['id_number'] ?? null)) !== '-')
                                                    - ID {{ $snapshot['passenger_id'] ?? $passenger['id_number'] }}
                                                @endif
                                            </span>
                                        </td>
                                        <td data-label="Booking Source">{{ $ticket?->kiosk_id ? 'Kiosk' : 'Online' }}</td>
                                        <td data-label="Payment Method">{{ $snapshot['payment_method'] ?? $paymentMethod }}</td>
                                        <td data-label="Processed By">{{ $snapshot['processed_by'] ?? ($ticketVoid->processedBy?->name ?: '-') }}</td>
                                        <td data-label="Authorized By">{{ $snapshot['authorized_by'] ?? ($ticketVoid->authorizedBy?->name ?: '-') }}</td>
                                        <td data-label="Status"><span class="voided-status">Voided</span></td>
                                        <td data-label="Action">
                                            <button type="button" class="btn btn-sm btn-outline--primary void-details-btn"
                                                data-details-url="{{ route('admin.vehicle.ticket.void.details', $ticketVoid->id) }}"
                                                title="View transaction details">
                                                <i class="las la-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center text-muted">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($voids->hasPages())
                    <div class="card-footer py-4">{{ paginateLinks($voids) }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="voidDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered void-details-dialog">
            <div class="modal-content void-details-content">
                <div class="modal-header">
                    <h5 class="modal-title">Voided Ticket - Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="voidDetailsLoading" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </div>
                    <div id="voidDetailsRows" class="d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <form action="{{ route('admin.vehicle.ticket.voided') }}" method="GET" class="voided-search-form">
        <i class="las la-search"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="Search PNR, passenger, or ref. no.">
    </form>
@endpush

@push('style')
    <style>
        .voided-ticket-table { min-width: 1550px; }
        .voided-ticket-table th { white-space: nowrap; }
        .voided-ticket-table td { font-size: 12px; vertical-align: top; }
        .voided-meta { color: #7d8490; display: block; font-size: 11px; margin-top: 2px; }
        .voided-status { background: #f5ecff; border: 1px solid #d5b4fa; border-radius: 999px; color: #7b35d2; display: inline-flex; font-size: 10px; font-weight: 700; padding: 4px 10px; }
        .voided-search-form { max-width: 330px; position: relative; width: 330px; }
        .voided-search-form i { color: #888f9a; font-size: 17px; left: 12px; position: absolute; top: 12px; }
        .voided-search-form input { background: #f6f7f9; border: 1px solid #d4d7dd; border-radius: 7px; height: 42px; padding: 0 12px 0 38px; width: 100%; }
        .void-details-dialog { max-width: 560px; }
        .void-details-content { border: 0; border-radius: 15px; box-shadow: 0 24px 65px rgba(0, 0, 0, .3); overflow: hidden; }
        .void-details-content .modal-header { border-color: #e5e7eb; padding: 20px 22px; }
        .void-details-content .modal-title { color: #1f2937; font-size: 18px; font-weight: 800; }
        .void-details-content .modal-body { max-height: 68vh; overflow-y: auto; padding: 0 22px; }
        .void-detail-row { align-items: flex-start; border-bottom: 1px solid #eceef1; display: flex; gap: 20px; justify-content: space-between; padding: 12px 0; }
        .void-detail-row span { color: #788191; font-size: 12px; }
        .void-detail-row strong { color: #273142; font-size: 12px; max-width: 65%; text-align: right; word-break: break-word; }
        .void-detail-row.is-total strong { color: #df257b; font-size: 14px; }
        .void-detail-row .voided-status { color: #7b35d2; }
        @media (max-width: 575px) { .voided-search-form { width: 100%; } .void-details-dialog { margin: 10px; } }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            const detailsModal = new bootstrap.Modal(document.getElementById('voidDetailsModal'));
            const currency = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
            const escapeHtml = value => $('<div>').text(value ?? '-').html();
            const formatSeatLabel = value => String(value ?? '')
                .split(',')
                .map(seat => seat.trim().replace(/^\d+-/, ''))
                .filter(Boolean)
                .join(', ');

            function detailRow(label, value, className = '') {
                return `<div class="void-detail-row ${className}"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value)}</strong></div>`;
            }

            $(document).on('click', '.void-details-btn', function() {
                $('#voidDetailsLoading').removeClass('d-none');
                $('#voidDetailsRows').addClass('d-none').empty();
                detailsModal.show();

                $.getJSON($(this).data('details-url')).done(function(data) {
                    const rows = [
                        detailRow('PNR', data.pnr),
                        detailRow('Reference No.', data.reference),
                        detailRow('Gateway Transaction', data.transaction),
                        detailRow('Trip / Route', `${data.route_name} (${data.trip_route})`),
                        detailRow('Bus Type', data.bus_type),
                        detailRow('Date of Journey', data.date_of_journey),
                        detailRow('Departure Time', data.departure_time),
                        detailRow('Seat', formatSeatLabel(data.seat)),
                        detailRow('Passenger', data.passenger_name),
                        detailRow('Passenger Type', data.passenger_type),
                        detailRow('Passenger ID No.', data.passenger_id),
                        detailRow('Booking Source', data.booking_source),
                        detailRow('Payment Method', data.payment_method),
                        detailRow('Original Fare', currency.format(Number(data.fare) || 0)),
                        detailRow('Full Fare Returned', currency.format(Number(data.returned_amount) || 0), 'is-total'),
                        detailRow('Ticket Count', data.ticket_count),
                        detailRow('Processed By', data.processed_by),
                        detailRow('Authorized By', data.authorized_by),
                        detailRow('Reason', data.reason),
                        detailRow('Remarks', data.remarks),
                        detailRow('Voided At', data.voided_at),
                        detailRow('Status', `${data.status} - ${data.voided_ago}`)
                    ];
                    $('#voidDetailsRows').html(rows.join('')).removeClass('d-none');
                    $('#voidDetailsLoading').addClass('d-none');
                }).fail(function(xhr) {
                    notify('error', xhr.responseJSON?.message || 'Unable to load the void transaction.');
                    detailsModal.hide();
                });
            });
        })(jQuery);
    </script>
@endpush
