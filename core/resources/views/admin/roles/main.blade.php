@extends('admin.layouts.app')

@section('panel')
    @php
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

    <div class="row">
        <div class="col-md-12">
            
            <!-- Filter Form -->
            <div class="col-12 mb-3">
                <form action="{{ url()->current() }}" method="GET">
                    <div class="d-flex flex-wrap gap-3 justify-content-end align-items-end">
                        <div style="width: 250px;">
                            <label>@lang('Search Role')</label>
                            <input type="text" name="search" class="form-control" placeholder="Search by name..." value="{{ request('search') }}">
                        </div>
                        <div style="width: 200px;">
                            <label>@lang('Status')</label>
                            <select name="status" class="form-control select2">
                                <option value="all">@lang('All Status')</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>@lang('Active')</option>
                                <option value="0" {{ request()->has('status') && request('status') == '0' ? 'selected' : '' }}>@lang('Disabled')</option>
                            </select>
                        </div>
                        <div>
                            <button class="btn btn--primary h-45"><i class="fas fa-filter"></i> @lang('Filter')</button>
                            <a href="{{ url()->current() }}" class="btn btn--dark h-45"><i class="fas fa-sync"></i> @lang('Clear')</a>
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
                        <form method="POST" action="{{ route('admin.roles.bulk') }}" id="bulkForm">
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
                                            <a href="{{ $sortUrl('name') }}" class="text--dark">@lang('Name') {!! $sortIcon('name') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ $sortUrl('status') }}" class="text--dark">@lang('Status') {!! $sortIcon('status') !!}</a>
                                        </th>
                                        <th>@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data as $item)
                                        <tr>
                                            <!-- Row Checkbox -->
                                            <td>
                                                <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="row-checkbox">
                                            </td>
                                            <td>{{ __($item->name) }}</td>
                                            <td>
                                                @if($item->status == 1)
                                                    <span class="badge badge--success">@lang('Active')</span>
                                                @else
                                                    <span class="badge badge--danger">@lang('Disabled')</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="button--group">
                                                    <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn"
                                                        data-resource="{{ $item }}" data-modal_title="@lang('Edit Role')">
                                                        <i class="la la-pencil"></i>@lang('Edit')
                                                    </button>

                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--danger confirmationBtn"
                                                        data-method="delete" data-question="@lang('Are you sure to delete this role?')"
                                                        data-action="{{ route('admin.roles.remove', $item->id) }}">
                                                        <i class="la la-trash"></i>@lang('Delete')
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage ?? 'No Roles Found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>

                @if ($data->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($data) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-confirmation-modal />

    <!-- Create / Edit Modal -->
    <div id="cuModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label> @lang('Name')</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>

                        <div class="row my-2">
                            <div class="col-8 pt-1">
                                <div class="custom-control pl-0">
                                    <label for="customCheck-all">All Permission</label>
                                </div>
                            </div>
                            <div class="col-4 pt-1">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" id="customCheck-all" value="all">
                                </div>
                            </div>
                        </div>
                        <hr>
                        @php
                            $parent_permissions = [];
                        @endphp
                        @foreach ($sidenav as $item)
                            @php
                                $menu_active = is_array($item->menu_active)
                                    ? $item->menu_active[0]
                                    : $item->menu_active;
                                $parent_permissions[] = $menu_active;
                            @endphp
                            <div class="ic_parent_permission">
                                <div class="row my-2">
                                    <div class="col-8 pt-1">
                                        <div class="custom-control">
                                            <label><strong>{{ $item->title }}</strong></label>
                                        </div>
                                    </div>
                                    <div class="col-4 pt-1">
                                        <div class="custom-control custom-checkbox">
                                            @if (str_contains($menu_active, '*'))
                                                <input type="checkbox" class="ic-parent-permission"
                                                    value="{{ $menu_active }}" id="chkbx-all-{{ $menu_active }}">
                                            @else
                                                <input type="checkbox" class="ic-parent-permission" name="permissions[]"
                                                    value="{{ $menu_active }}" id="chkbx-all-{{ $menu_active }}">
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if (isset($item->submenu))
                                @foreach ($item->submenu as $submenu)
                                    <div>
                                        <div class="row">
                                            <div class="col-8 pt-1">
                                                <div class="custom-control">
                                                    <label>{{ $submenu->title }}</label>
                                                </div>
                                            </div>
                                            <div class="col-4 pt-1">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" name="permissions[]"
                                                        id="chkbx-{{ $submenu->route_name }}"
                                                        value="{{ $submenu->route_name }}"
                                                        class="parent-identy-{{ $menu_active }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @foreach ($submenu->additional_permissions ?? [] as $additionalPermission)
                                        <div>
                                            <div class="row">
                                                <div class="col-8 pt-1">
                                                    <div class="custom-control ps-3">
                                                        <label>{{ $additionalPermission->title }}</label>
                                                    </div>
                                                </div>
                                                <div class="col-4 pt-1">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" name="permissions[]"
                                                            id="chkbx-{{ $additionalPermission->value }}"
                                                            value="{{ $additionalPermission->value }}"
                                                            class="parent-identy-{{ $menu_active }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            @endif
                            <hr>
                        @endforeach

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary h-45 w-100">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <input type="hidden" value="{{ json_encode($parent_permissions) }}" id="parent_permissions">
@endsection

@push('breadcrumb-plugins')
    <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn h-45" data-modal_title="@lang('Add Role')">
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

            // Existing logic
            $('input[name=deck]').on('input', function() {
                $('.showSeat').empty();
                for (var deck = 1; deck <= $(this).val(); deck++) {
                    $('.showSeat').append(`
                        <div class="form-group">
                            <label> Seats of Deck - ${deck} </label>
                            <input type="text" class="form-control hasArray" placeholder="@lang('Enter Number of Seat')" name="deck_seats[]" required>
                        </div>
                    `);
                }
            })

            $('.cuModalBtn').on('click', function() {
                let modal = $('#cuModal');
                let data = $(this).data('resource');

                if (data) {

                    let permissions = data.permissions ? JSON.parse(data.permissions) : [];

                    $('*[id^="chkbx-"]').prop('checked', false);
                    for (let i = 0; i < permissions.length; i++) {
                        $(`input[id='chkbx-${permissions[i]}']`).prop('checked', true);
                    }
                    let parent_checkboxes = JSON.parse($('#parent_permissions').val());
                    let checked_counter = 0;

                    for (let el of parent_checkboxes) {
                        let parent_module = $(`input[id="chkbx-all-${el}"]`);
                        if (parent_module && permissions.includes(parent_module.val().replace('.*', ''))) {
                            parent_module.prop('checked', true);
                        }
                        $(`input[class="parent-identy-${el}"]`).each(function() {
                            if ($(this).is(':checked')) {
                                checked_counter++;
                            }
                            if ($(`input[class="parent-identy-${el}"]`).length == checked_counter) {
                                $(`input[id="chkbx-all-${el}"]`).prop('checked', true);
                                checked_counter = 0;
                            }
                        });
                    }
                }
            });


            $("#customCheck-all").on('click', function() {
                $('input:checkbox').not(this).prop('checked', this.checked);
                $('div .ic_div-show').toggle();
            });

            $(document).on('click', '.ic-parent-permission', function() {
                let parent_id = $(this).val();

                if ($(`input[id="chkbx-all-${parent_id}"]`).is(':checked')) {
                    $(`input[class="parent-identy-${parent_id}"]`).each(function() {
                        $(this).prop('checked', true);
                    });
                } else {
                    $(`input[class="parent-identy-${parent_id}"]`).each(function() {
                        $(this).prop('checked', false);
                    });
                }
            });

            // New Bulk Checkbox Logic
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
                if($('.row-checkbox:checked').length === 0) {
                    alert('Please select at least one role to perform this action.');
                    return;
                }
                
                var actionType = $(this).data('type');
                var actionText = actionType === 'enable' ? 'enable' : 'disable';

                if(confirm('Are you sure you want to ' + actionText + ' the selected roles?')) {
                    $('#bulkActionType').val(actionType);
                    $('#bulkForm').submit();
                }
            });

        })(jQuery);
    </script>
@endpush
