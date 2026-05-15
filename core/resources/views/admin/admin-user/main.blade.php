@extends('admin.layouts.app')

@section('panel')
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

    <div class="row">
        <div class="col-md-12">
            
            <!-- Filter Form -->
            <div class="col-12 mb-3">
                <form action="{{ url()->current() }}" method="GET">
                    <div class="d-flex flex-wrap gap-3 justify-content-end align-items-end">
                        <div style="width: 250px;">
                            <label>@lang('Search User')</label>
                            <input type="text" name="search" class="form-control" placeholder="Name, Email, Username..." value="{{ request('search') }}">
                        </div>
                        <div style="width: 200px;">
                            <label>@lang('Role')</label>
                            <select name="role_id" class="form-control select2">
                                <option value="all">@lang('All Roles')</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
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
                        <form method="POST" action="{{ route('admin.users.bulk') }}" id="bulkForm">
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
                                            <a href="{{ sortUrl('name') }}" class="text--dark">@lang('Name') {!! sortIcon('name') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ sortUrl('email') }}" class="text--dark">@lang('Email') {!! sortIcon('email') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ sortUrl('username') }}" class="text--dark">@lang('Username') {!! sortIcon('username') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ sortUrl('role') }}" class="text--dark">@lang('Role') {!! sortIcon('role') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ sortUrl('status') }}" class="text--dark">@lang('Status') {!! sortIcon('status') !!}</a>
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
                                            <td>{{ __($item->email) }}</td>
                                            <td>{{ __($item->username) }}</td>
                                            <td>{{ __($item->role) }}</td>
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
                                                        data-resource="{{ $item }}" data-modal_title="@lang('Edit User')">
                                                        <i class="la la-pencil"></i>@lang('Edit')
                                                    </button>

                                                    @if ($item->username != 'admin')
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline--danger confirmationBtn"
                                                            data-method="delete" data-question="@lang('Are you sure to delete this user?')"
                                                            data-action="{{ route('admin.users.remove', $item->id) }}">
                                                            <i class="la la-trash"></i>@lang('Delete')
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

                @if ($data->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($data) }}
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
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label> @lang('Name')</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="form-group">
                            <label> @lang('Email')</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group">
                            <label> @lang('Username')</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="form-group">
                            <label> @lang('Role')</label>
                            <select name="role_id" class="form-control">
                                @foreach ($roles as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label> @lang('Discount Passcode')</label>
                            <input type="text" class="form-control" name="passcode">
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
    <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn h-45" data-modal_title="@lang('Add Admin')">
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
                $('#change-password').remove()
                if (data) {
                    let change_pass_html = '<div class="d-block" id="change-password">';
                    change_pass_html +=
                        '<a class="btn btn-sm btn--primary btn-change-pass">Change Password</a>';
                    change_pass_html += '</div>';

                    modal.find('.modal-body').append(change_pass_html);
                    return;
                }

                let password_container = '<div class="col-md-12 password-container">';
                password_container +=
                    '<label for="validationCustom02" class="form-label">Password</label>';
                password_container +=
                    '<input type="password" class="form-control" name="password" required  autocomplete="new-password">';
                password_container += '</div>';

                modal.find('.modal-body').append(password_container);
            });

            $(document).on('click', '#change-password .btn-change-pass', function() {
                $(this).remove();
                let change_pass_html = '<div col-md-6><label class="mt-2">New Password</label>';
                change_pass_html += '<div class="d-flex">';
                change_pass_html +=
                    '<input type="password" class="form-control" name="password" autocomplete="new-password" required>';
                change_pass_html += '<a class="btn btn-sm btn-danger" id="btn-cancel">X</a>';
                change_pass_html += '</div></div>';

                $('#change-password').append(change_pass_html);
            });

            $(document).on('click', '#btn-cancel', function(event) {
                $(this).parent().parent().remove();
                $(this).remove();
                let change_pass_html = '<div class="d-flex col-md-6">';
                change_pass_html += '<a class="btn btn-sm btn--primary btn-change-pass">Change password</a>';
                change_pass_html += '</div>';

                $('#change-password').append(change_pass_html);
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
                if($('.row-checkbox:checked').length === 0) {
                    alert('Please select at least one user to perform this action.');
                    return;
                }
                
                var actionType = $(this).data('type');
                var actionText = actionType === 'enable' ? 'enable' : 'disable';

                if(confirm('Are you sure you want to ' + actionText + ' the selected users?')) {
                    $('#bulkActionType').val(actionType);
                    $('#bulkForm').submit();
                }
            });

        })(jQuery);
    </script>
@endpush