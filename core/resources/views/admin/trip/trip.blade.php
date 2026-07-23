@extends('admin.layouts.app')
@section('panel')
    @php
        use App\Constants\Status;
        $status = request('status');

        $sortUrl = function ($field) {
            $currentOrder = request('sort_order', 'desc');
            $newOrder = request('sort_field') == $field && $currentOrder == 'asc' ? 'desc' : 'asc';
            return request()->fullUrlWithQuery(['sort_field' => $field, 'sort_order' => $newOrder]);
        };
        $sortIcon = function ($field) {
            if (request('sort_field') == $field) {
                return request('sort_order', 'desc') == 'asc'
                    ? '<i class="las la-sort-up"></i>'
                    : '<i class="las la-sort-down"></i>';
            }
            return '<i class="las la-sort"></i>';
        };

        // Preload Master Data for Fare Preview Engine
        $allRoutes = App\Models\VehicleRoute::active()->get()->keyBy('id');
        $allCounters = App\Models\Counter::active()->get()->keyBy('id');
        $allTicketPrices = App\Models\TicketPrice::with('prices')->get();
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="col-12 mb-3">
                <form action="{{ url()->current() }}" method="GET">
                    <div class="d-flex flex-wrap gap-3 justify-content-end align-items-end">
                        <div style="width: 250px;">
                            <label>@lang('Search Title')</label>
                            <input type="text" name="search" class="form-control" placeholder="Search by title..."
                                value="{{ request('search') }}">
                        </div>
                        <div style="width: 200px;">
                            <label>@lang('Status')</label>
                            <select name="status" class="form-control select2">
                                <option value="all">@lang('All status')</option>
                                <option value="1" {{ $status == '1' ? 'selected' : '' }}>@lang('Enabled')</option>
                                <option value="0" {{ request()->has('status') && $status == '0' ? 'selected' : '' }}>
                                    @lang('Disabled')</option>
                            </select>
                        </div>
                        <div>
                            <button class="btn btn--primary h-45"><i class="fas fa-filter"></i> @lang('Filter')</button>
                            <a href="{{ url('/admin/manage/trip') }}" class="btn btn--dark h-45"><i class="fas fa-sync"></i>
                                @lang('Clear')</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-12 mb-2 d-flex justify-content-start gap-2">
                <button type="button" class="btn btn-sm btn-outline--success bulk-action-btn" data-type="enable">
                    <i class="las la-check"></i> @lang('Enable Selected')
                </button>
                <button type="button" class="btn btn-sm btn-outline--danger bulk-action-btn" data-type="disable">
                    <i class="las la-ban"></i> @lang('Disable Selected')
                </button>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <form method="POST" action="{{ route('admin.trip.bulk') }}" id="bulkForm">
                            @csrf
                            <input type="hidden" name="action_type" id="bulkActionType">

                            <table class="table table--light style--two align-middle">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="checkAll"></th>
                                        <th>
                                            <a href="{{ $sortUrl('title') }}" class="text--dark">@lang('Trip')
                                                {!! $sortIcon('title') !!}</a>
                                        </th>
                                        <th>@lang('Route Details')</th>
                                        <th>
                                            <a href="{{ $sortUrl('fleet_type_id') }}" class="text--dark">@lang('Fleet Info')
                                                {!! $sortIcon('fleet_type_id') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ $sortUrl('schedule_id') }}" class="text--dark">@lang('Schedule')
                                                {!! $sortIcon('schedule_id') !!}</a>
                                        </th>
                                        <th>@lang('Status')</th>
                                        <th>@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($trips as $item)
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="ids[]" value="{{ $item->id }}"
                                                    class="row-checkbox">
                                            </td>

                                            <td>
                                                <span class="fw-bold">{{ __($item->title) }}</span>
                                            </td>

                                            <td>
                                                <div class="fw-bold text-dark">
                                                    {{ $item->route->startFrom->city ?? 'N/A' }} &rarr;
                                                    {{ $item->route->endTo->city ?? 'N/A' }}
                                                </div>
                                                @php
                                                    $stoppageArr = $item->route->stoppages ?? [];
                                                    $stoppagesList = getIntermediateStoppages($stoppageArr);
                                                @endphp
                                                @if ($stoppagesList && $stoppagesList->count() > 0)
                                                    <div class="text-muted small mt-1"
                                                        style="max-width: 250px; white-space: normal;">
                                                        <i class="las la-map-marker"></i> Via:
                                                        {{ $stoppagesList->pluck('name')->implode(', ') }}
                                                    </div>
                                                @endif
                                            </td>

                                            <td>
                                                <div class="fw-bold text-dark">{{ __($item->fleetType->name) }}</div>
                                                <div class="text-muted small mt-1">
                                                    <i class="las la-bus"></i>
                                                    {{ __($item->fleetType->has_ac == App\Constants\Status::ENABLE ? 'AC' : 'Non-AC') }}
                                                </div>
                                            </td>

                                            <td>
                                                <div class="fw-bold text-dark">
                                                    {{ showDateTime($item->schedule->start_from, 'h:i A') }} -
                                                    {{ showDateTime($item->schedule->end_at, 'h:i A') }}
                                                </div>
                                                <div class="text-muted small mt-1">
                                                    @if ($item->day_off)
                                                        <i class="las la-calendar-times"></i> Off:
                                                        @foreach ($item->day_off as $day)
                                                            {{ __(showDayOff($day)) }}@if (!$loop->last)
                                                                ,
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        <span class="text-success"><i class="las la-calendar-check"></i>
                                                            @lang('No Off Day')</span>
                                                    @endif
                                                </div>
                                            </td>

                                            <td>
                                                <div>@php echo $item->statusBadge; @endphp</div>
                                                <div class="mt-2 small fw-bold">
                                                    @php echo decodeSlug($item->trip_status); @endphp
                                                </div>
                                            </td>

                                            <td>
                                                <div class="button--group">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--primary cuModalBtn"
                                                        data-resource="{{ $item }}"
                                                        data-modal_title="@lang('Edit Trip')">
                                                        <i class="la la-pencil"></i>@lang('Edit')
                                                    </button>

                                                    <button type="button" class="btn btn-sm btn-outline--info manifestDateBtn"
                                                        data-manifest-url="{{ route('admin.trip.manifestSeatLayout', $item->id) }}">
                                                        <i class="las la-clipboard-list"></i> Manifest
                                                    </button>

                                                    @if ($canManageSeatLocks)
                                                        <a href="{{ route('admin.trip.seat-locks.index', $item->id) }}"
                                                            class="btn btn-sm btn-outline--warning"
                                                            title="Manage administrative seat locks">
                                                            <i class="las la-lock"></i> Seats
                                                        </a>
                                                    @endif

                                                    @if (!$item->status)
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline--success confirmationBtn"
                                                            data-action="{{ route('admin.trip.status', $item->id) }}"
                                                            data-question="@lang('Are you sure you want to enable this trip?')">
                                                            <i class="la la-eye"></i>@lang('Enable')
                                                        </button>
                                                    @else
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline--danger confirmationBtn"
                                                            data-action="{{ route('admin.trip.status', $item->id) }}"
                                                            data-question="@lang('Are you sure you want to disable this trip?')">
                                                            <i class="la la-eye-slash"></i>@lang('Disable')
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                            <tr>
                                                <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </form>
                        </div>
                    </div>

                    @if ($trips->hasPages())
                        <div class="card-footer py-4">
                            {{ paginateLinks($trips) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <x-confirmation-modal />

        <div id="manifestDateModal" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content shadow">
                    <div class="modal-header">
                        <h6 class="modal-title"><i class="las la-calendar me-1"></i> Manifest Date</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label for="tripManifestDate" class="form-label small text-uppercase">Departure date</label>
                        <input type="date" id="tripManifestDate" class="form-control" value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-light" id="manifestTodayBtn">Today</button>
                        <button type="button" class="btn btn--primary" id="openManifestBtn">
                            <i class="las la-external-link-alt me-1"></i> Open
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Update Modal (Light Theme) -->
        <div id="cuModal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content shadow-sm">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title"></h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="las la-times"></i>
                        </button>
                    </div>

                    <form action="{{ route('admin.trip.store') }}" method="POST">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="row gy-4">
                                <!-- Departure Time -->
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="text-danger fw-bold mb-0" style="font-size: 13px;">Departure Time
                                            *</label>
                                        <a href="{{ route('admin.trip.schedule.index') }}" target="_blank"
                                            class="text-info text-decoration-none" style="font-size: 13px;">
                                            Manage schedules <i class="las la-external-link-alt"></i>
                                        </a>
                                    </div>
                                    <select name="schedule_id" class="select2-basic form-control" required>
                                        <option value="">Select departure time</option>
                                        @foreach ($schedules as $item)
                                            <option value="{{ $item->id }}"
                                                data-name="{{ showDateTime($item->start_from, 'h:i A') }}">
                                                {{ __(showDateTime($item->start_from, 'h:i A')) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Route -->
                                <div class="col-md-6">
                                    <label class="text-danger fw-bold mb-2" style="font-size: 13px;">Route *</label>
                                    <select name="vehicle_route_id" id="vehicle_route_id" class="select2-basic form-control"
                                        required>
                                        <option value="">Select route</option>
                                        @foreach ($routes as $item)
                                            <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Bus Type -->
                                <div class="col-md-6">
                                    <label class="text-danger fw-bold mb-2" style="font-size: 13px;">Bus Type *</label>
                                    <select name="fleet_type_id" id="fleet_type_id" class="select2-basic form-control"
                                        required>
                                        <option value="">Select bus type</option>
                                        @foreach ($fleetTypes as $item)
                                            <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Dynamic Fare Preview -->
                            <div class="fare-preview-wrapper mt-5 d-none border rounded bg-light">
                                <div class="p-3 border-bottom bg-white rounded-top">
                                    <h6 class="text-muted text-uppercase mb-0 fw-bold"
                                        style="font-size: 12px; letter-spacing: 1px;">Fare Preview</h6>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light text-muted"
                                            style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">
                                            <tr>
                                                <th class="ps-4">Stop</th>
                                                <th>KM</th>
                                                <th class="text-end pe-4">From Origin</th>
                                            </tr>
                                        </thead>
                                        <tbody id="fare-preview-body" class="bg-white">
                                            <!-- Injected via JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-top p-4">
                            <button type="submit" class="btn btn--primary h-45 w-100 fw-bold">@lang('Submit')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>
    @endsection

    @push('breadcrumb-plugins')
        <button type="button" class="btn btn-outline--primary cuModalBtn h-45" data-modal_title="@lang('Add New Trip')">
            <i class="las la-plus"></i> @lang('Add New')
        </button>
    @endpush

    @push('style')
        <style>
            /* Dark Theme Modal Scoped Styles */
            .dark-trip-modal {
                background-color: #1a1a1a;
            }

            .dark-trip-modal .select2-container--default .select2-selection--single {
                background-color: #242424 !important;
                border: 1px solid #333 !important;
                border-radius: 6px;
                height: 45px;
                display: flex;
                align-items: center;
            }

            .dark-trip-modal .select2-container--default .select2-selection--single .select2-selection__rendered {
                color: #e0e0e0 !important;
            }

            .dark-trip-modal .select2-dropdown {
                background-color: #242424;
                border: 1px solid #333;
                color: #e0e0e0;
            }

            .border-dark {
                border-color: #333 !important;
            }
        </style>
    @endpush

    @push('script-lib')
        <script src="{{ asset('assets/admin/js/cu-modal.js?v=' . buildVer()) }}"></script>
    @endpush

    @push('script')
        <script>
            (function($) {
                "use strict";

                // Master Data for Fare Preview 
                const ticketPrices = @json($allTicketPrices);
                const routesData = @json($allRoutes);
                const countersData = @json($allCounters);

                // Render Fare Preview Engine
                function renderFarePreview() {
                    let routeId = $('#vehicle_route_id').val();
                    let fleetId = $('#fleet_type_id').val();

                    if (!routeId || !fleetId) {
                        $('.fare-preview-wrapper').addClass('d-none');
                        return;
                    }

                    let route = routesData[routeId];
                    let ticketData = ticketPrices.find(tp => tp.vehicle_route_id == routeId && tp.fleet_type_id == fleetId);

                    if (!route || !ticketData) {
                        $('#fare-preview-body').html(`
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">No fare configuration found for this Route & Bus Type.</td>
                    </tr>
                `);
                        $('.fare-preview-wrapper').removeClass('d-none');
                        return;
                    }

                    let stoppages = route.stoppages || [];
                    if (stoppages.length === 0) return;

                    let originId = route.start_from;
                    let html = '';

                    stoppages.forEach((stopId, index) => {
                        let counter = countersData[stopId];
                        if (!counter) return;

                        let isOrigin = (index === 0);
                        let priceText = 'Origin';
                        let priceClass = 'text-muted';

                        if (!isOrigin) {
                            // Extract exact price from relationship
                            let stopPriceObj = ticketData.prices.find(sp =>
                                sp.source_destination[0] == originId && sp.source_destination[1] == stopId
                            );

                            let val = stopPriceObj ? stopPriceObj.price : 0;
                            priceText = '₱' + parseFloat(val).toLocaleString(undefined, {
                                minimumFractionDigits: 0
                            });
                            priceClass = 'text-success fw-bold';
                        }

                        html += `
                    <tr style="border-bottom: 1px solid #2a2a2a;">
                        <td class="text-light py-3 ps-0">${counter.name}</td>
                        <td class="text-muted py-3">${counter.km_post}</td>
                        <td class="text-end py-3 pe-0 ${priceClass}">${priceText}</td>
                    </tr>
                `;
                    });

                    $('#fare-preview-body').html(html);
                    $('.fare-preview-wrapper').removeClass('d-none');
                }

                // Attach listeners
                $('#vehicle_route_id, #fleet_type_id').on('change', renderFarePreview);

                // Delay to allow cuModal to auto-select fields on Edit, then fire preview
                $('.cuModalBtn').on('click', function() {
                    setTimeout(renderFarePreview, 100);
                });

                // Bulk Actions Checkbox Logic
                $('#checkAll').on('change', function() {
                    $('.row-checkbox').prop('checked', $(this).is(':checked'));
                });

                $('.row-checkbox').on('change', function() {
                    if ($('.row-checkbox:checked').length == $('.row-checkbox').length) {
                        $('#checkAll').prop('checked', true);
                    } else {
                        $('#checkAll').prop('checked', false);
                    }
                });

                $('.bulk-action-btn').on('click', function() {
                    if ($('.row-checkbox:checked').length === 0) {
                        alert('Please select at least one trip to perform this action.');
                        return;
                    }

                    var actionType = $(this).data('type');
                    var actionText = actionType === 'enable' ? 'enable' : 'disable';

                    if (confirm('Are you sure you want to ' + actionText + ' the selected trips?')) {
                        $('#bulkActionType').val(actionType);
                        $('#bulkForm').submit();
                    }
                });

                // Fix select2 inside Bootstrap Modal bug
                $('#cuModal').on('shown.bs.modal', function() {
                    $('.select2-basic').select2({
                        dropdownParent: $('#cuModal')
                    });
                });

                const manifestDateModal = new bootstrap.Modal(document.getElementById('manifestDateModal'));
                let manifestUrl = '';

                $('.manifestDateBtn').on('click', function() {
                    manifestUrl = $(this).data('manifest-url');
                    $('#tripManifestDate').val('{{ now()->format('Y-m-d') }}');
                    manifestDateModal.show();
                });

                $('#manifestTodayBtn').on('click', function() {
                    $('#tripManifestDate').val('{{ now()->format('Y-m-d') }}');
                });

                $('#openManifestBtn').on('click', function() {
                    const date = $('#tripManifestDate').val();
                    if (!manifestUrl || !date) return;

                    window.open(`${manifestUrl}?date_of_journey=${encodeURIComponent(date)}`, '_blank');
                    manifestDateModal.hide();
                });

            })(jQuery);
        </script>
    @endpush
