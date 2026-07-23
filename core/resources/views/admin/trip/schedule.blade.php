@extends('admin.layouts.app')
@php
    use App\Constants\Status;
    $status = request('status');

    $sortUrl = function ($field) {
        $currentOrder = request('sort_order', 'desc');
        $newOrder = (request('sort_field') == $field && $currentOrder == 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort_field' => $field, 'sort_order' => $newOrder]);
    };
    $sortIcon = function ($field) {
        if (request('sort_field') == $field) {
            return request('sort_order', 'desc') == 'asc' ? '<i class="las la-sort-up"></i>' : '<i class="las la-sort-down"></i>';
        }
        return '<i class="las la-sort"></i>';
    };
@endphp
@section('panel')
    <div class="row">
        <div class="col-md-12">
            <!-- Filter Form -->
            <div class="col-12 mb-3">
                <form action="{{ url()->current() }}" method="GET">
                    <div class="d-flex flex-wrap gap-3 justify-content-end align-items-end">
                        <div style="width: 200px;">
                            <label>@lang('Status')</label>
                            <select name="status" class="form-control select2">
                                <option value="all">@lang('All status')</option>
                                <option value="1" {{ $status == '1' ? 'selected' : '' }}>@lang('Enabled')</option>
                                <option value="0" {{ request()->has('status') && $status == '0' ? 'selected' : '' }}>@lang('Disabled')</option>
                            </select>
                        </div>
                        <div>
                            <label>@lang('Time Start')</label>
                            <input type="time" name="start_at" class="form-control" value="{{ request('start_at') }}">
                        </div>
                        <div>
                            <label>@lang('Time End')</label>
                            <input type="time" name="end_at" class="form-control" value="{{ request('end_at') }}">
                        </div>
                        <div>
                            <button class="btn btn--primary h-45"><i class="fas fa-filter"></i> @lang('Filter')</button>
                            <a href="{{ url('/admin/manage/schedule') }}" class="btn btn--dark h-45"><i class="fas fa-sync"></i> @lang('Clear')</a>
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
                        <form method="POST" action="{{ route('admin.trip.schedule.bulk') }}" id="bulkForm">
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
                                            <a href="{{ $sortUrl('start_from') }}" class="text--dark">@lang('Start From') {!! $sortIcon('start_from') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ $sortUrl('end_at') }}" class="text--dark">@lang('End At') {!! $sortIcon('end_at') !!}</a>
                                        </th>
                                        <th>@lang('Duration')</th>
                                        <th>
                                            <a href="{{ $sortUrl('status') }}" class="text--dark">@lang('Status') {!! $sortIcon('status') !!}</a>
                                        </th>
                                        <th>@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($schedules as $item)
                                        @php
                                            $start = \Carbon\Carbon::parse($item->start_from);
                                            $end = \Carbon\Carbon::parse($item->end_at);

                                            if ($end->lt($start)) {
                                                $end->addDay();
                                            }
                                            $diff = $start->diff($end);
                                        @endphp
                                        <tr>
                                            <!-- Row Checkbox -->
                                            <td>
                                                <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="row-checkbox">
                                            </td>
                                            <td>{{ showDateTime($item->start_from, 'h:i A') }}</td>
                                            <td>{{ showDateTime($item->end_at, 'h:i A') }}</td>
                                            <td>{{ __($diff->format('%h hours %i minutes')) }}</td>
                                            <td>@php echo $item->statusBadge; @endphp</td>
                                            <td>
                                                <div class="button--group">
                                                    <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn"
                                                        data-resource="{{ $item }}"
                                                        data-modal_title="@lang('Edit Schedule')">
                                                        <i class="la la-pencil"></i>@lang('Edit')
                                                    </button>

                                                    @if (!$item->status)
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline--success confirmationBtn"
                                                            data-action="{{ route('admin.trip.schedule.status', $item->id) }}"
                                                            data-question="@lang('Are you sure to enable this schedule?')">
                                                            <i class="la la-eye"></i>@lang('Enable')
                                                        </button>
                                                    @else
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline--danger confirmationBtn"
                                                            data-action="{{ route('admin.trip.schedule.status', $item->id) }}"
                                                            data-question="@lang('Are you sure to disable this schedule?')">
                                                            <i class="la la-eye-slash"></i>@lang('Disable')
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage ?? 'No Data Found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>

                @if ($schedules->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($schedules) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    <x-confirmation-modal />

    <!-- Create/Update Modal -->
    <div id="cuModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.trip.schedule.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label> @lang('Start From')</label>
                            <input type="time" class="form-control" name="start_from" required>
                        </div>
                        <div class="form-group">
                            <label> @lang('End At')</label>
                            <input type="time" name="end_at" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn h-45" data-modal_title="@lang('Add New Schedule')">
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
                alert('Please select at least one schedule to perform this action.');
                return;
            }
            
            var actionType = $(this).data('type');
            var actionText = actionType === 'enable' ? 'enable' : 'disable';

            if(confirm('Are you sure you want to ' + actionText + ' the selected schedules?')) {
                $('#bulkActionType').val(actionType);
                $('#bulkForm').submit();
            }
        });
    })(jQuery);
</script>
@endpush
