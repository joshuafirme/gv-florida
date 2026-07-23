@extends('admin.layouts.app')

@section('panel')
    @php
        $sortUrl = function ($field)
        {
            $currentOrder = request('sort_order', 'desc');
            $newOrder = request('sort_field') == $field && $currentOrder == 'asc' ? 'desc' : 'asc';
            return request()->fullUrlWithQuery(['sort_field' => $field, 'sort_order' => $newOrder]);
        };
        $sortIcon = function ($field)
        {
            if (request('sort_field') == $field) {
                return request('sort_order', 'desc') == 'asc'
                    ? '<i class="las la-sort-up"></i>'
                    : '<i class="las la-sort-down"></i>';
            }
            return '<i class="las la-sort"></i>';
        };
    @endphp

    <div class="row">
        <div class="col-md-12">

            <!-- Filter Form -->
            <div class="col-12 mb-3">
                <form action="{{ url()->current() }}" method="GET">
                    <div class="d-flex flex-wrap gap-3 justify-content-end align-items-end">
                        <div style="width: 250px;">
                            <label>@lang('Search Counter')</label>
                            <input type="text" name="search" class="form-control" placeholder="Name, City, Mobile..."
                                value="{{ request('search') }}">
                        </div>
                        <div style="width: 200px;">
                            <label>@lang('Status')</label>
                            <select name="status" class="form-control select2">
                                <option value="all">@lang('All Status')</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>@lang('Active')
                                </option>
                                <option value="0"
                                    {{ request()->has('status') && request('status') == '0' ? 'selected' : '' }}>
                                    @lang('Disabled')</option>
                            </select>
                        </div>
                        <div>
                            <button class="btn btn--primary h-45"><i class="fas fa-filter"></i> @lang('Filter')</button>
                            <a href="{{ url()->current() }}" class="btn btn--dark h-45"><i class="fas fa-sync"></i>
                                @lang('Clear')</a>
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
                        <form method="POST" action="{{ route('admin.counter.bulk') }}" id="bulkForm">
                            @csrf
                            <input type="hidden" name="action_type" id="bulkActionType">

                            <table class="table table--light style--two">
                                <thead>
                                    <tr>
                                        <!-- Master Checkbox -->
                                        <th>
                                            <input type="checkbox" id="checkAll">
                                        </th>
                                        <th>
                                            <a href="{{ $sortUrl('id') }}" class="text--dark">@lang('ID')
                                                {!! $sortIcon('id') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ $sortUrl('name') }}" class="text--dark">@lang('Name')
                                                {!! $sortIcon('name') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ $sortUrl('mobile') }}" class="text--dark">@lang('Mobile Number')
                                                {!! $sortIcon('mobile') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ $sortUrl('city') }}" class="text--dark">@lang('City')
                                                {!! $sortIcon('city') !!}</a>
                                        </th>
                                        <th>@lang('Location')</th>
                                        <th>@lang('KM Post')</th>
                                        <th>
                                            <a href="{{ $sortUrl('status') }}" class="text--dark">@lang('Status')
                                                {!! $sortIcon('status') !!}</a>
                                        </th>
                                        <th>@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($counters as $item)
                                        <tr>
                                            <!-- Row Checkbox -->
                                            <td>
                                                <input type="checkbox" name="ids[]" value="{{ $item->id }}"
                                                    class="row-checkbox">
                                            </td>
                                            <td>{{ __($item->id) }}</td>
                                            <td>{{ __($item->name) }}</td>
                                            <td>{{ __($item->mobile) }}</td>
                                            <td>{{ __($item->city) }}</td>
                                            <td>{{ __($item->location) ?? '--' }}</td>
                                            <td>{{ __($item->km_post) }}</td>
                                            <td>
                                                @php echo $item->statusBadge; @endphp
                                            </td>
                                            <td>
                                                <div class="button--group">
                                                    <a target="_blank"
                                                        href="{{ route('admin.counter.scheduleBoard', $item->id) }}"
                                                        class="btn btn-sm btn-outline--primary">
                                                        <i class="la la-tv"></i>@lang('Schedule Board')
                                                    </a>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--primary cuModalBtn"
                                                        data-resource="{{ $item }}"
                                                        data-modal_title="@lang('Edit Counter')">
                                                        <i class="la la-pencil"></i>@lang('Edit')
                                                    </button>
                                                    <a target="_blank"
                                                        href="{{ route('admin.counter.reservation-slip', ['counter_id' => $item->id]) }}"
                                                        class="btn btn-sm btn-outline--primary">@lang('Reservation Slip')
                                                    </a>

                                                    @if (!$item->status)
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline--success confirmationBtn"
                                                            data-action="{{ route('admin.counter.status', $item->id) }}"
                                                            data-question="@lang('Are you sure to enable this counter?')">
                                                            <i class="la la-eye"></i>@lang('Enable')
                                                        </button>
                                                    @else
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline--danger  confirmationBtn"
                                                            data-action="{{ route('admin.counter.status', $item->id) }}"
                                                            data-question="@lang('Are you sure to disable this counter?')">
                                                            <i class="la la-eye-slash"></i>@lang('Disable')
                                                        </button>
                                                    @endif
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--danger delete-counter-btn"
                                                        data-action="{{ route('admin.counter.remove', $item->id) }}">
                                                        <i class="la la-trash"></i>@lang('Delete')
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="text-muted text-center" colspan="100%">
                                                {{ __($emptyMessage ?? 'No Data Found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>

                @if ($counters->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($counters) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-confirmation-modal />

    <div id="cuModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.counter.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label> @lang('Name')</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="form-group">
                            <label> @lang('City')</label>
                            <input type="text" class="form-control" name="city" required>
                        </div>
                        <div class="form-group">
                            <label> @lang('Location')</label>
                            <textarea name="location" class="form-control"></textarea>
                        </div>
                        <div class="form-group">
                            <label> @lang('KM Post')</label>
                            <input name="km_post" class="form-control" type="number">
                        </div>
                        <div class="form-group">
                            <label> @lang('Mobile')</label>
                            <input type="text" class="form-control" name="mobile" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary h-45 w-100">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <!-- Removed generic search bar since dynamic filter handles it now -->
    <button type="button" class="btn btn-sm btn-outline--primary h-45 cuModalBtn" data-modal_title="@lang('Add New Counter')">
        <i class="las la-plus"></i> @lang('Add New')
    </button>
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/cu-modal.js?v=' . buildVer()) }}"></script>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";

            $(document).on('click', '.delete-counter-btn', function() {
                let actionUrl = $(this).data('action');

                if (confirm('Are you sure you want to permanently delete this counter?')) {
                    // Dynamically create a form to submit the DELETE request securely
                    let form = document.createElement('form');
                    form.action = actionUrl;
                    form.method = 'POST';

                    // Add CSRF Token
                    let csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';

                    // Spoof DELETE Method
                    let methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';

                    form.appendChild(csrfInput);
                    form.appendChild(methodInput);
                    document.body.appendChild(form);

                    form.submit();
                }
            });

            // Bulk Checkbox Logic
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

            // Trigger Bulk Action
            $('.bulk-action-btn').on('click', function() {
                if ($('.row-checkbox:checked').length === 0) {
                    alert('Please select at least one counter to perform this action.');
                    return;
                }

                var actionType = $(this).data('type');
                var actionText = actionType === 'enable' ? 'enable' : 'disable';

                if (confirm('Are you sure you want to ' + actionText + ' the selected counters?')) {
                    $('#bulkActionType').val(actionType);
                    $('#bulkForm').submit();
                }
            });

        })(jQuery);
    </script>
@endpush
