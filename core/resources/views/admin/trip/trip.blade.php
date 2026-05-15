@extends('admin.layouts.app')
@section('panel')
    @php
        use App\Constants\Status;
        $status = request('status');

        function sortUrl($field) {
            $currentOrder = request('sort_order', 'desc');
            $newOrder = (request('sort_field') == $field && $currentOrder == 'asc') ? 'desc' : 'asc';
            return request()->fullUrlWithQuery(['sort_field' => $field, 'sort_order' => $newOrder]);
        }
        function sortIcon($field) {
            if (request('sort_field') == $field) {
                return request('sort_order', 'desc') == 'asc' ? '<i class="las la-sort-up"></i>' : '<i class="las la-sort-down"></i>';
            }
            return '<i class="las la-sort"></i>';
        }
    @endphp

    <div class="row">
        <div class="col-md-12">
            <!-- Filter Form -->
            <div class="col-12 mb-3">
                <form action="{{ url()->current() }}" method="GET">
                    <div class="d-flex flex-wrap gap-3 justify-content-end align-items-end">
                        <div style="width: 250px;">
                            <label>@lang('Search Title')</label>
                            <input type="text" name="search" class="form-control" placeholder="Search by title..." value="{{ request('search') }}">
                        </div>
                        <div style="width: 200px;">
                            <label>@lang('Status')</label>
                            <select name="status" class="form-control select2">
                                <option value="all">@lang('All status')</option>
                                <option value="1" {{ $status == '1' ? 'selected' : '' }}>@lang('Enabled')</option>
                                <option value="0" {{ request()->has('status') && $status == '0' ? 'selected' : '' }}>@lang('Disabled')</option>
                            </select>
                        </div>
                        <div>
                            <button class="btn btn--primary h-45"><i class="fas fa-filter"></i> @lang('Filter')</button>
                            <a href="{{ url('/admin/manage/trip') }}" class="btn btn--dark h-45"><i class="fas fa-sync"></i> @lang('Clear')</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Bulk Action Buttons -->
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
                        <!-- Form wrapping the table for bulk updates -->
                        <form method="POST" action="{{ route('admin.trip.bulk') }}" id="bulkForm">
                            @csrf
                            <input type="hidden" name="action_type" id="bulkActionType">

                            <table class="table table--light style--two">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="checkAll">
                                        </th>
                                        <th>
                                            <a href="{{ sortUrl('title') }}" class="text--dark">@lang('Title') {!! sortIcon('title') !!}</a>
                                        </th>
                                        <th>@lang('Destination')</th>
                                        <th>@lang('Stoppages')</th>
                                        <th>@lang('AC / Non-AC')</th>
                                        <th>
                                            <a href="{{ sortUrl('fleet_type_id') }}" class="text--dark">@lang('Fleet Type') {!! sortIcon('fleet_type_id') !!}</a>
                                        </th>
                                        <th>@lang('Day Off')</th>
                                        <th>
                                            <a href="{{ sortUrl('schedule_id') }}" class="text--dark">@lang('Schedule') {!! sortIcon('schedule_id') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ sortUrl('trip_status') }}" class="text--dark">@lang('Trip Status') {!! sortIcon('trip_status') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ sortUrl('status') }}" class="text--dark">@lang('Status') {!! sortIcon('status') !!}</a>
                                        </th>
                                        <th>@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($trips as $item)
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="row-checkbox">
                                            </td>
                                            <td>{{ __($item->title) }}</td>
                                            <td>{{ $item->route->startFrom->city ?? 'N/A' }} → {{ $item->route->endTo->city ?? 'N/A' }}</td>
                                            <td>
                                                @php
                                                    $stoppageArr = $item->route->stoppages ?? [];
                                                    $stoppagesList = App\Models\Counter::routeStoppages($stoppageArr);
                                                    if($stoppagesList) {
                                                        $stoppagesList = $stoppagesList->slice(1, -1);
                                                    }
                                                @endphp
                                                @if($stoppagesList)
                                                    @foreach ($stoppagesList as $stoppage)
                                                        <div>{{ $stoppage->name }}</div>
                                                    @endforeach
                                                @endif
                                            </td>
                                            <td>{{ __($item->fleetType->has_ac == Status::ENABLE ? 'AC' : 'Non-Ac') }}</td>
                                            <td>{{ __($item->fleetType->name) }}</td>
                                            <td>
                                                @if ($item->day_off)
                                                    @foreach ($item->day_off as $day)
                                                        {{ __(showDayOff($day)) }}@if (!$loop->last), @endif
                                                    @endforeach
                                                @else
                                                    @lang('No Off Day')
                                                @endif
                                            </td>
                                            <td>{{ showDateTime($item->schedule->start_from, 'h:i A') }} -
                                                {{ showDateTime($item->schedule->end_at, 'h:i A') }}</td>
                                            <td>@php echo decodeSlug($item->trip_status); @endphp </td>
                                            <td>@php echo $item->statusBadge; @endphp </td>
                                            <td>
                                                <div class="button--group">
                                                    <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn"
                                                        data-resource="{{ $item }}"
                                                        data-modal_title="@lang('Edit Trip')">
                                                        <i class="la la-pencil"></i>@lang('Edit')
                                                    </button>
                                                    <a href="{{ url("/admin/manage/trip/manifest-seat-layout/$item->id") }}"
                                                        target="_blank" class="btn btn-sm btn-outline--primary">
                                                        Manifest
                                                    </a>
                                                    @if (!$item->status)
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline--success confirmationBtn"
                                                            data-action="{{ route('admin.trip.status', $item->id) }}"
                                                            data-question="@lang('Are you sure to enable this trip?')">
                                                            <i class="la la-eye"></i>@lang('Enable')
                                                        </button>
                                                    @else
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline--danger confirmationBtn"
                                                            data-action="{{ route('admin.trip.status', $item->id) }}"
                                                            data-question="@lang('Are you sure to disable this trip?')">
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

    <!-- Create/Update Modal (Unchanged from your original code) -->
    <div id="cuModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.trip.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label> @lang('Title')</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label> @lang('Fleet Type')</label>
                                    <select name="fleet_type_id" class="select2" required>
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($fleetTypes as $item)
                                            <option value="{{ $item->id }}" data-name="{{ $item->name }}">
                                                {{ __($item->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label> @lang('Route')</label>
                                    <select name="vehicle_route_id" class="select2" required>
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($routes as $item)
                                            <option value="{{ $item->id }}" data-name="{{ $item->name }}">
                                                {{ __($item->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label> @lang('Schedule')</label>
                                    <select name="schedule_id" class="select2" required>
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($schedules as $item)
                                            <option value="{{ $item->id }}"
                                                data-name="{{ showDateTime($item->start_from, 'h:i a') . ' - ' . showDateTime($item->end_at, 'h:i a') }}">
                                                {{ __(showDateTime($item->start_from, 'h:i a') . ' - ' . showDateTime($item->end_at, 'h:i a')) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="trip_status">@lang('Trip Status')</label>
                                    <select class="select2" name="trip_status" id="trip_status">
                                        <option value="{{ Status::TRIP_ON_TIME }}">@lang(decodeSlug(Status::TRIP_ON_TIME))</option>
                                        <option value="{{ Status::TRIP_BOARDING }}">@lang(decodeSlug(Status::TRIP_BOARDING))</option>
                                        <option value="{{ Status::TRIP_DEPARTED }}">@lang(decodeSlug(Status::TRIP_DEPARTED))</option>
                                        <option value="{{ Status::TRIP_DELAYED }}">@lang(decodeSlug(Status::TRIP_DELAYED))</option>
                                        <option value="{{ Status::TRIP_CANCELLED }}">@lang(decodeSlug(Status::TRIP_CANCELLED))</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="day_off">@lang('Day Off')</label>
                                    <select class="select2-auto-tokenize" name="day_off[]" id="day_off"
                                        multiple="multiple">
                                        <option value="0">@lang('Sunday')</option>
                                        <option value="1">@lang('Monday')</option>
                                        <option value="2">@lang('Tuesday')</option>
                                        <option value="3">@lang('Wednesday')</option>
                                        <option value="4">@lang('Thursday')</option>
                                        <option value="5">@lang('Friday')</option>
                                        <option value="6">@lang('Saturday')</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn h-45 w-100 btn--primary">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <button type="button" class="btn btn-outline--primary cuModalBtn h-45" data-modal_title="@lang('Add New Trip')">
        <i class="las la-plus"></i> @lang('Add New')
    </button>
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/cu-modal.js?v=' . buildVer()) }}"></script>
@endpush

@push('script')
<script>
    (function ($) {
        "use strict";

        // Check/Uncheck all rows
        $('#checkAll').on('change', function() {
            $('.row-checkbox').prop('checked', $(this).is(':checked'));
        });

        // Ensure master checkbox acts correctly if individual checkboxes are unchecked
        $('.row-checkbox').on('change', function() {
            if ($('.row-checkbox:checked').length == $('.row-checkbox').length) {
                $('#checkAll').prop('checked', true);
            } else {
                $('#checkAll').prop('checked', false);
            }
        });

        // Trigger Bulk Action
        $('.bulk-action-btn').on('click', function() {
            if($('.row-checkbox:checked').length === 0) {
                alert('Please select at least one trip to perform this action.');
                return;
            }
            
            var actionType = $(this).data('type');
            var actionText = actionType === 'enable' ? 'enable' : 'disable';

            if(confirm('Are you sure you want to ' + actionText + ' the selected trips?')) {
                $('#bulkActionType').val(actionType);
                $('#bulkForm').submit();
            }
        });
    })(jQuery);
</script>
@endpush