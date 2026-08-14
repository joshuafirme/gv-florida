@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two rebooked-ticket-table">
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
                                @forelse ($events as $event)
                                    <tr>
                                        <td data-label="User">
                                            <strong>{{ $event['device_name'] }}</strong>
                                            <span class="rebooked-meta">{{ $event['device_id'] }}</span>
                                        </td>
                                        <td data-label="PNR">
                                            <strong class="text--primary">{{ $event['pnr'] }}</strong>
                                        </td>
                                        <td data-label="Reference No.">{{ $event['reference'] }}</td>
                                        <td data-label="Journey">
                                            <strong>{{ $event['journey_date'] }}</strong>
                                            <span class="rebooked-meta">{{ $event['departure_time'] }}</span>
                                        </td>
                                        <td data-label="Trip">
                                            <strong>{{ $event['trip_class'] }}</strong>
                                            <span class="rebooked-meta">{{ $event['trip_route'] }}</span>
                                        </td>
                                        <td data-label="Seat No.">
                                            <strong>{{ formatSeatLabel($event['seat']) }}</strong>
                                        </td>
                                        <td data-label="Fare">
                                            <strong>{{ showAmount($event['fare']) }}</strong>
                                            <span class="rebooked-meta">1 ticket</span>
                                        </td>
                                        <td data-label="Passenger">
                                            <strong>{{ $event['passenger_name'] }}</strong>
                                            <span class="rebooked-meta">
                                                {{ $event['passenger_type'] }}
                                                @if ($event['passenger_id'])
                                                    &middot; ID {{ $event['passenger_id'] }}
                                                @endif
                                            </span>
                                        </td>
                                        <td data-label="Booking Source">{{ $event['booking_source'] }}</td>
                                        <td data-label="Payment Method">{{ $event['payment_method'] }}</td>
                                        <td data-label="Processed By">{{ $event['processed_by'] }}</td>
                                        <td data-label="Authorized By">
                                            <strong>{{ $event['authorized_by'] ?: '-' }}</strong>
                                            @if ($event['authorized_at'])
                                                <span class="rebooked-meta">{{ $event['authorized_at'] }}</span>
                                            @endif
                                        </td>
                                        <td data-label="Status">
                                            <span class="rebooked-status">Rebooked</span>
                                        </td>
                                        <td data-label="Action">
                                            <div class="rebooked-actions">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline--primary rebooked-details-btn"
                                                    data-details-url="{{ $event['details_url'] }}"
                                                    title="View detailed transaction receipt">
                                                    <i class="las la-eye"></i>
                                                </button>
                                                @if ($event['print_url'])
                                                    <a href="{{ $event['print_url'] }}" target="_blank" rel="noopener"
                                                        class="btn btn-sm btn-outline--primary"
                                                        title="Print new ticket">
                                                        <i class="las la-print"></i>
                                                    </a>
                                                @endif
                                                @if ($event['rebook_url'])
                                                    <a href="{{ $event['rebook_url'] }}"
                                                        class="btn btn-sm btn-outline--primary"
                                                        data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                        title="Rebook this ticket again">
                                                        <i class="las la-exchange-alt"></i>
                                                    </a>
                                                @endif
                                            </div>
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
                @if ($events->hasPages())
                    <div class="card-footer py-4">{{ paginateLinks($events) }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="rebookedDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered rebooked-details-dialog">
            <div class="modal-content rebooked-details-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rebooked Ticket - Transaction Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="rebookedDetailsLoading" class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </div>
                    <div id="rebookedDetailsRows" class="d-none"></div>
                </div>
                <div class="modal-footer">
                    <a href="#" target="_blank" class="btn btn--primary d-none" id="rebookedDetailsPrint">
                        <i class="las la-print"></i> Print New Ticket
                    </a>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <form action="{{ route('admin.vehicle.ticket.rebooked') }}" method="GET" class="rebooked-search-form">
        <i class="las la-search"></i>
        <input type="search" name="search" value="{{ $search }}"
            placeholder="Search PNR, passenger, or ref. no.">
    </form>
@endpush

@push('style')
    <style>
        .rebooked-ticket-table { min-width: 1650px; }
        .rebooked-ticket-table th { white-space: nowrap; }
        .rebooked-ticket-table td { font-size: 12px; vertical-align: top; }
        .rebooked-meta { color: #7d8490; display: block; font-size: 11px; margin-top: 2px; }
        .rebooked-status { background: #eaf8ff; border: 1px solid #a9dff3; border-radius: 999px; color: #13749a; display: inline-flex; font-size: 10px; font-weight: 700; padding: 4px 10px; }
        .rebooked-actions { display: flex; gap: 5px; }
        .rebooked-search-form { max-width: 330px; position: relative; width: 330px; }
        .rebooked-search-form i { color: #888f9a; font-size: 17px; left: 12px; position: absolute; top: 12px; }
        .rebooked-search-form input { background: #f6f7f9; border: 1px solid #d4d7dd; border-radius: 7px; height: 42px; padding: 0 12px 0 38px; width: 100%; }
        .rebooked-details-dialog { max-width: 560px; }
        .rebooked-details-content { border: 0; border-radius: 12px; box-shadow: 0 24px 65px rgba(0, 0, 0, .3); overflow: hidden; }
        .rebooked-details-content .modal-header { border-color: #e5e7eb; padding: 20px 22px; }
        .rebooked-details-content .modal-title { color: #1f2937; font-size: 18px; font-weight: 800; }
        .rebooked-details-content .modal-body { max-height: 68vh; overflow-y: auto; padding: 0 22px; }
        .rebooked-detail-row { align-items: flex-start; border-bottom: 1px solid #eceef1; display: flex; gap: 20px; justify-content: space-between; padding: 12px 0; }
        .rebooked-detail-row span { color: #788191; font-size: 12px; }
        .rebooked-detail-row strong { color: #273142; font-size: 12px; max-width: 65%; text-align: right; word-break: break-word; }
        @media (max-width: 575px) { .rebooked-search-form { width: 100%; } .rebooked-details-dialog { margin: 10px; } }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            const detailsModal = new bootstrap.Modal(document.getElementById('rebookedDetailsModal'));
            const currency = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
            const escapeHtml = value => $('<div>').text(value ?? '-').html();
            const formatSeat = value => String(value ?? '').replace(/^\d+-/, '');

            function detailRow(label, value) {
                return `<div class="rebooked-detail-row"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value)}</strong></div>`;
            }

            $(document).on('click', '.rebooked-details-btn', function() {
                $('#rebookedDetailsLoading').removeClass('d-none');
                $('#rebookedDetailsRows').addClass('d-none').empty();
                $('#rebookedDetailsPrint').addClass('d-none').attr('href', '#');
                detailsModal.show();

                $.getJSON($(this).data('details-url')).done(function(data) {
                    const passenger = data.passenger_id
                        ? `${data.passenger_name} - ${data.passenger_type} - ID ${data.passenger_id}`
                        : `${data.passenger_name} - ${data.passenger_type}`;
                    const rows = [
                        detailRow('User / Device', `${data.device_name} - ${data.device_id}`),
                        detailRow('PNR', data.pnr),
                        detailRow('Reference No.', data.reference),
                        detailRow('Journey', `${data.journey_date} - ${data.departure_time}`),
                        detailRow('Trip', `${data.trip_class} - ${data.trip_route}`),
                        detailRow('Seat No.', formatSeat(data.seat)),
                        detailRow('Fare', currency.format(Number(data.fare) || 0)),
                        detailRow('Passenger', passenger),
                        detailRow('Booking Source', data.booking_source),
                        detailRow('Payment Method', data.payment_method),
                        detailRow('Rebooking Sequence', data.sequence),
                        detailRow('Original Departure', data.original_departure || '-'),
                        detailRow('Admin Grace Deadline', data.grace_ends_at || '-'),
                        detailRow('Previous Trip', data.previous_trip),
                        detailRow('New Trip', data.new_trip),
                        detailRow('Previous Departure', data.previous_departure || '-'),
                        detailRow('New Departure', data.new_departure || '-'),
                        detailRow('Previous Seat', formatSeat(data.previous_seat) || '-'),
                        detailRow('New Seat', formatSeat(data.new_seat) || '-'),
                        detailRow('Timing', data.after_departure ? 'After original departure (within grace period)' : 'Before original departure'),
                        detailRow('Processed By', data.processed_by),
                        detailRow('Authorized By', data.authorized_by || '-'),
                        detailRow('Authorization Date & Time', data.authorized_at || '-'),
                        detailRow('Approval Remarks', data.approval_remarks || '-'),
                        detailRow('Reason', data.reason),
                        detailRow('Rebooked At', data.processed_at),
                        detailRow('Status', data.status)
                    ];
                    $('#rebookedDetailsRows').html(rows.join('')).removeClass('d-none');
                    $('#rebookedDetailsLoading').addClass('d-none');
                    if (data.print_url) {
                        $('#rebookedDetailsPrint').removeClass('d-none').attr('href', data.print_url);
                    }
                }).fail(function(xhr) {
                    notify('error', xhr.responseJSON?.message || 'Unable to load the rebooking transaction.');
                    detailsModal.hide();
                });
            });
        })(jQuery);
    </script>
@endpush
