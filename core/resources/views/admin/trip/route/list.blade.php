@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <!-- Filter Form -->
        <div class="col-12 mb-3">
            <form action="{{ url()->current() }}" method="GET">
                <div class="d-flex flex-wrap gap-3 justify-content-end">
                    <div style="width: 250px;">
                        <input type="text" name="search" class="form-control" placeholder="Search by route name, etc." value="{{ request('search') }}">
                    </div>
                    <div style="width: 250px;">
                        <select name="status" class="form-control select2">
                            <option value="all">@lang('All Status')</option>
                            <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>@lang('Enabled')</option>
                            <option value="0" {{ request()->has('status') && request('status') == '0' ? 'selected' : '' }}>@lang('Disabled')</option>
                        </select>
                    </div>
                    <div class="align-self-end">
                        <button class="btn btn--primary h-45"><i class="fas fa-filter"></i> @lang('Filter')</button>
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

        <!-- Sort Helper Logic -->
        @php
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

        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        
                        <!-- Form wrapping the table for bulk updates -->
                        <form method="POST" action="{{ route('admin.trip.route.bulk') }}" id="bulkForm">
                            @csrf
                            <input type="hidden" name="action_type" id="bulkActionType">

                            <table class="table table--light style--two">
                                <thead>
                                    <tr>
                                        <!-- Master Checkbox -->
                                        <th>
                                            <input type="checkbox" id="checkAll">
                                        </th>
                                        <!-- Sortable Headers -->
                                        <th>
                                            <a href="{{ sortUrl('name') }}" class="text--dark">@lang('Name') {!! sortIcon('name') !!}</a>
                                        </th>
                                        <th>@lang('Starting Point')</th>
                                        <th>@lang('Ending Point')</th>
                                        <th>
                                            <a href="{{ sortUrl('distance') }}" class="text--dark">@lang('Distance') {!! sortIcon('distance') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ sortUrl('time') }}" class="text--dark">@lang('Time') {!! sortIcon('time') !!}</a>
                                        </th>
                                        <th>
                                            Stops
                                        </th>
                                        <th>
                                            <a href="{{ sortUrl('status') }}" class="text--dark">@lang('Status') {!! sortIcon('status') !!}</a>
                                        </th>
                                        <th>@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($routes as $item)
                                        <tr>
                                            <!-- Row Checkbox -->
                                            <td>
                                                <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="row-checkbox">
                                            </td>
                                            <td>{{ __($item->name) }}</td>
                                            <td>{{ __($item->startFrom?->name) }}</td>
                                            <td>{{ __($item->endTo?->name) }}</td>
                                            <td>{{ __($item->distance) }}</td>
                                            <td>{{ __($item->time) }}</td>
                                            <td>{{ count(getIntermediateStoppages($item->stoppages)) }}</td>
                                            <td>@php echo $item->statusBadge; @endphp</td>
                                            <td>
                                                <div class="button--group">
                                                    <a href="{{ route('admin.trip.route.form', $item->id) }}"
                                                        class="btn btn-sm btn-outline--primary">
                                                        <i class="la la-pencil"></i>@lang('Edit')
                                                    </a>

                                                    @if (!$item->status)
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline--success confirmationBtn"
                                                            data-action="{{ route('admin.trip.route.status', $item->id) }}"
                                                            data-question="@lang('Are you sure to enable this route?')">
                                                            <i class="la la-eye"></i>@lang('Enable')
                                                        </button>
                                                    @else
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline--danger confirmationBtn"
                                                            data-action="{{ route('admin.trip.route.status', $item->id) }}"
                                                            data-question="@lang('Are you sure to disable this route?')">
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

                @if ($routes->hasPages())
                    <div class="card-footer py-4">
                        @php echo paginateLinks($routes) @endphp
                    </div>
                @endif
            </div>
        </div>
    </div>
    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.trip.route.form') }}" class="btn btn-sm btn-outline--primary h-45">
        <i class="las la-plus"></i> @lang('Add New')
    </a>
@endpush

@push('script')
<script>
    (function ($) {
        "use strict";

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
            if($('.row-checkbox:checked').length === 0) {
                alert('Please select at least one route to perform this action.');
                return;
            }
            
            var actionType = $(this).data('type');
            var actionText = actionType === 'enable' ? 'enable' : 'disable';

            if(confirm('Are you sure you want to ' + actionText + ' the selected routes?')) {
                $('#bulkActionType').val(actionType);
                $('#bulkForm').submit();
            }
        });
    })(jQuery);
</script>
@endpush