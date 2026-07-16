@extends('admin.layouts.app')

@section('panel')
    @php
        use App\Constants\Status;

        $assignmentData = $trips->getCollection()->mapWithKeys(function ($trip) use ($dispatchStatuses) {
            $status = array_key_exists($trip->trip_status, $dispatchStatuses)
                ? $trip->trip_status
                : Status::TRIP_ON_TIME;

            return [
                $trip->id => [
                    'id' => $trip->id,
                    'title' => $trip->route?->name ?: $trip->title,
                    'time' => $trip->schedule?->start_from
                        ? date('h:i A', strtotime($trip->schedule->start_from))
                        : '-',
                    'fleet_type' => $trip->fleetType?->name ?: '-',
                    'vehicle_id' => $trip->assignedVehicle?->vehicle_id,
                    'dispatch_status' => $status,
                    'remarks' => $trip->assignedVehicle?->remarks,
                    'vehicles' => $trip->fleetType?->activeVehicles?->map(fn ($vehicle) => [
                        'id' => $vehicle->id,
                        'label' => collect([$vehicle->bus_no, $vehicle->register_no, $vehicle->nick_name])
                            ->filter()
                            ->unique()
                            ->implode(' - '),
                    ])->values()->all() ?? [],
                ],
            ];
        });
    @endphp

    <div class="vehicle-assignment-page">
        <form action="{{ url()->current() }}" method="GET" class="assignment-search-form">
            <i class="las la-search" aria-hidden="true"></i>
            <input type="search" name="search" value="{{ $search }}" placeholder="Search trips..."
                aria-label="Search trips">
        </form>

        <div class="card assignment-table-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table assignment-table mb-0">
                        <thead>
                            <tr>
                                <th>@lang('Departure')</th>
                                <th>@lang('Route')</th>
                                <th>@lang('Bus Type')</th>
                                <th>@lang('Vehicle')</th>
                                <th>@lang('Dispatch')</th>
                                <th class="text-end">@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trips as $trip)
                                @php
                                    $vehicle = $trip->assignedVehicle?->vehicle;
                                    $dispatchStatus = $trip->trip_status ?: Status::TRIP_ON_TIME;
                                    $dispatchLabel = $dispatchStatuses[$dispatchStatus] ?? __(ucwords(str_replace('_', ' ', $dispatchStatus)));
                                    $dispatchClass = match ($dispatchStatus) {
                                        Status::TRIP_BOARDING => 'boarding',
                                        Status::TRIP_DEPARTED => 'departed',
                                        Status::TRIP_DELAYED => 'delayed',
                                        Status::TRIP_ARRIVED => 'arrived',
                                        Status::TRIP_CANCELLED => 'cancelled',
                                        default => 'scheduled',
                                    };
                                    $canAssign = (bool) $trip->status && $dispatchStatus !== Status::TRIP_CANCELLED;
                                @endphp
                                <tr>
                                    <td data-label="@lang('Departure')">
                                        <strong class="departure-time">
                                            {{ $trip->schedule?->start_from ? date('h:i', strtotime($trip->schedule->start_from)) : '--:--' }}
                                            <small>{{ $trip->schedule?->start_from ? date('A', strtotime($trip->schedule->start_from)) : '' }}</small>
                                        </strong>
                                    </td>
                                    <td data-label="@lang('Route')">
                                        <strong>{{ __($trip->route?->name ?: $trip->title) }}</strong>
                                        <small class="row-supporting-text">
                                            {{ $trip->route?->startFrom?->name ?: $trip->startFrom?->name }}
                                            <i class="las la-long-arrow-alt-right"></i>
                                            {{ $trip->route?->endTo?->name ?: $trip->endTo?->name }}
                                        </small>
                                    </td>
                                    <td data-label="@lang('Bus Type')">
                                        <span class="bus-type-badge">{{ __($trip->fleetType?->name ?: '-') }}</span>
                                    </td>
                                    <td data-label="@lang('Vehicle')">
                                        @if ($vehicle)
                                            <strong>{{ $vehicle->bus_no ?: $vehicle->nick_name }}</strong>
                                            @if ($vehicle->register_no)
                                                <span class="vehicle-separator">&middot;</span>
                                                <span class="row-supporting-text d-inline">{{ $vehicle->register_no }}</span>
                                            @endif
                                        @else
                                            <span class="unassigned-label">Not assigned</span>
                                        @endif
                                    </td>
                                    <td data-label="@lang('Dispatch')">
                                        <span class="dispatch-badge dispatch-badge--{{ $dispatchClass }}">
                                            <i class="fas fa-circle"></i>{{ $dispatchLabel }}
                                        </span>
                                        @if (!$trip->status)
                                            <small class="row-supporting-text">Trip disabled</small>
                                        @endif
                                    </td>
                                    <td data-label="@lang('Action')" class="text-end">
                                        <button type="button" class="btn assignment-action-btn js-assignment-btn"
                                            data-trip-id="{{ $trip->id }}" @disabled(!$canAssign)
                                            title="{{ !$canAssign ? 'Enable or reactivate this trip from Trip Management first.' : ($vehicle ? 'Update assignment' : 'Assign vehicle') }}">
                                            <i class="las la-shuttle-van"></i>
                                            {{ $vehicle ? 'Update' : 'Assign' }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">No trips found in Trip Management.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($trips->hasPages())
                <div class="card-footer py-3">
                    {{ paginateLinks($trips) }}
                </div>
            @endif
        </div>
    </div>

    <div id="assignmentModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content assignment-modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Vehicle Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="assignmentForm" method="POST">
                    @csrf
                    <input type="hidden" name="trip_id" id="assignmentTripId" value="{{ old('trip_id') }}">

                    <div class="modal-body">
                        <div class="assignment-trip-summary">
                            <strong id="assignmentTripTitle">-</strong>
                            <span><span id="assignmentTripTime">-</span> &middot; <span id="assignmentFleetType">-</span></span>
                        </div>

                        <div class="form-group">
                            <label for="assignmentVehicle">Assigned Vehicle</label>
                            <select class="form-control" name="vehicle_id" id="assignmentVehicle" required></select>
                        </div>

                        <div class="form-group mb-3">
                            <label>Dispatch Status</label>
                            <div class="dispatch-options">
                                @foreach ($dispatchStatuses as $value => $label)
                                    <input type="radio" name="dispatch_status" id="dispatch_{{ $value }}"
                                        value="{{ $value }}" required>
                                    <label for="dispatch_{{ $value }}">{{ $label }}</label>
                                @endforeach
                            </div>
                            <small class="dispatch-note">Trip cancellation remains managed from Trip Management.</small>
                        </div>

                        <div class="form-group mb-0">
                            <label for="assignmentRemarks">Remarks</label>
                            <textarea class="form-control" name="remarks" id="assignmentRemarks" rows="3"
                                placeholder="e.g. Delayed 30 mins - heavy traffic"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn--primary">
                            <i class="las la-save"></i> Save Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .vehicle-assignment-page {
            color: #252936;
        }

        .assignment-search-form {
            position: relative;
            width: min(100%, 385px);
            margin-bottom: 14px;
        }

        .assignment-search-form i {
            position: absolute;
            top: 50%;
            left: 13px;
            color: #858b98;
            font-size: 17px;
            transform: translateY(-50%);
        }

        .assignment-search-form input {
            width: 100%;
            height: 40px;
            padding: 8px 14px 8px 39px;
            background: #f7f7f9;
            border: 1px solid #d8dbe2;
            border-radius: 7px;
            outline: 0;
        }

        .assignment-search-form input:focus {
            border-color: #df2278;
            box-shadow: 0 0 0 3px rgba(223, 34, 120, .1);
        }

        .assignment-table-card {
            overflow: hidden;
            border: 1px solid #dfe2e8;
            border-radius: 8px;
            box-shadow: none;
        }

        .assignment-table th {
            padding: 14px 16px;
            color: #666b78;
            background: #f5f5f7;
            border-bottom: 1px solid #dfe2e8;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .assignment-table td {
            padding: 13px 16px;
            border-bottom: 1px solid #eceef2;
            vertical-align: middle;
        }

        .assignment-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .departure-time {
            white-space: nowrap;
            font-family: monospace;
            font-size: 14px;
        }

        .departure-time small {
            color: #717684;
            font-family: inherit;
            font-size: 11px;
        }

        .row-supporting-text {
            display: block;
            margin-top: 3px;
            color: #818794;
            font-size: 11px;
            font-weight: 400;
        }

        .bus-type-badge {
            display: inline-flex;
            padding: 4px 10px;
            color: #8d28b6;
            background: #f3e6fa;
            border: 1px solid #ead3f5;
            border-radius: 999px;
            font-size: 11px;
            white-space: nowrap;
        }

        .vehicle-separator {
            margin: 0 5px;
            color: #a2a6af;
        }

        .unassigned-label {
            color: #8a8f9b;
            font-style: italic;
        }

        .dispatch-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 9px;
            border: 1px solid #d7d9df;
            border-radius: 999px;
            font-size: 11px;
            white-space: nowrap;
        }

        .dispatch-badge i {
            font-size: 6px;
        }

        .dispatch-badge--scheduled { color: #545965; background: #f1f2f4; }
        .dispatch-badge--boarding { color: #1264a3; background: #e8f4fd; border-color: #bfdef4; }
        .dispatch-badge--departed { color: #a15c00; background: #fff4df; border-color: #f0d59d; }
        .dispatch-badge--delayed { color: #b42318; background: #fff0ef; border-color: #f4c7c3; }
        .dispatch-badge--arrived { color: #087a4b; background: #e9f8f0; border-color: #bce5cf; }
        .dispatch-badge--cancelled { color: #7a263a; background: #f8e9ed; border-color: #e9c0ca; }

        .assignment-action-btn {
            min-width: 82px;
            padding: 7px 11px;
            color: #353945;
            background: #f7f7f8;
            border: 1px solid #dcdfe5;
            border-radius: 7px;
            font-size: 12px;
        }

        .assignment-action-btn:hover:not(:disabled) {
            color: #df2278;
            border-color: #df2278;
        }

        .assignment-action-btn:disabled {
            cursor: not-allowed;
            opacity: .5;
        }

        .assignment-modal-content {
            border: 0;
            border-radius: 8px;
        }

        .assignment-trip-summary {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 18px;
            padding: 14px 16px;
            background: #f6f6f8;
            border: 1px solid #dfe2e8;
            border-radius: 7px;
        }

        .assignment-trip-summary span {
            color: #707583;
            font-size: 12px;
        }

        .dispatch-options {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .dispatch-options input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .dispatch-options label {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            margin: 0;
            padding: 7px;
            background: #f4f4f6;
            border: 1px solid #d6d9df;
            border-radius: 7px;
            cursor: pointer;
            font-size: 12px;
        }

        .dispatch-options input:checked + label {
            color: #fff;
            background: #df2278;
            border-color: #df2278;
        }

        .dispatch-options input:focus-visible + label {
            box-shadow: 0 0 0 3px rgba(223, 34, 120, .18);
        }

        .dispatch-note {
            display: block;
            margin-top: 8px;
            color: #747a87;
            font-size: 11px;
        }

        #assignmentRemarks::placeholder {
            color: #9297a2;
            font-style: italic;
            opacity: .8;
        }

        @media (max-width: 767px) {
            .assignment-table thead {
                display: none;
            }

            .assignment-table tr {
                display: block;
                padding: 10px 0;
                border-bottom: 1px solid #e5e7ec;
            }

            .assignment-table td {
                display: grid;
                grid-template-columns: 105px minmax(0, 1fr);
                padding: 7px 14px;
                border: 0;
                text-align: left !important;
            }

            .assignment-table td::before {
                content: attr(data-label);
                color: #7b808d;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }

            .dispatch-options {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';

            const trips = {{ Illuminate\Support\Js::from($assignmentData) }};
            const actionTemplate = @json(route('admin.trip.vehicle.assign.store', '__TRIP_ID__'));
            const modalElement = document.getElementById('assignmentModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

            function openAssignment(tripId, useOldInput = false) {
                const trip = trips[tripId];
                if (!trip) return;

                $('#assignmentForm').attr('action', actionTemplate.replace('__TRIP_ID__', trip.id));
                $('#assignmentTripId').val(trip.id);
                $('#assignmentTripTitle').text(trip.title);
                $('#assignmentTripTime').text(trip.time);
                $('#assignmentFleetType').text(trip.fleet_type);

                const options = ['<option value="">Select a vehicle</option>'];
                trip.vehicles.forEach(vehicle => {
                    options.push(`<option value="${vehicle.id}">${$('<div>').text(vehicle.label).html()}</option>`);
                });
                $('#assignmentVehicle').html(options.join(''));

                const vehicleId = useOldInput ? @json(old('vehicle_id')) : trip.vehicle_id;
                const dispatchStatus = useOldInput ? @json(old('dispatch_status')) : trip.dispatch_status;
                const remarks = useOldInput ? @json(old('remarks')) : trip.remarks;

                $('#assignmentVehicle').val(vehicleId || '');
                $(`input[name="dispatch_status"][value="${dispatchStatus}"]`).prop('checked', true);
                $('#assignmentRemarks').val(remarks || '');
                modal.show();
            }

            $(document).on('click', '.js-assignment-btn', function() {
                openAssignment(String($(this).data('trip-id')));
            });

            @if (isset($errors) && $errors->any() && old('trip_id'))
                openAssignment(@json((string) old('trip_id')), true);
            @endif
        })(jQuery);
    </script>
@endpush
