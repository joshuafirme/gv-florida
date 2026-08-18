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
                                            <a href="{{ $sortUrl('name') }}" class="text--dark">@lang('Name') {!! $sortIcon('name') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ $sortUrl('email') }}" class="text--dark">@lang('Email') {!! $sortIcon('email') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ $sortUrl('username') }}" class="text--dark">@lang('Username') {!! $sortIcon('username') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ $sortUrl('role') }}" class="text--dark">@lang('Role') {!! $sortIcon('role') !!}</a>
                                        </th>
                                        <th>
                                            <a href="{{ $sortUrl('status') }}" class="text--dark">@lang('Status') {!! $sortIcon('status') !!}</a>
                                        </th>
                                        <th>@lang('Authorization')</th>
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
                                                @if ($item->has_authorization_code)
                                                    <span class="badge badge--success">
                                                        <i class="las la-shield-alt"></i> @lang('Assigned')
                                                    </span>
                                                @else
                                                    <span class="badge badge--dark">
                                                        <i class="las la-minus-circle"></i> @lang('Not Assigned')
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="button--group">
                                                    <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn"
                                                        data-resource="{{ $item }}"
                                                        data-authorization-url="{{ route('admin.users.authorization.code', $item->id) }}"
                                                        data-modal_title="@lang('Edit User')">
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
                        <div class="form-group">
                            <label for="adminAuthorizationCode">@lang('Authorization Code')</label>
                            <div class="authorization-code-input">
                                <input type="password" class="form-control" id="adminAuthorizationCode"
                                    name="authorization_code" minlength="6" maxlength="100"
                                    placeholder="Optional Authorization Code"
                                    autocomplete="new-password" autocapitalize="none" autocorrect="off"
                                    spellcheck="false" data-lpignore="true" data-1p-ignore>
                                <button type="button" class="authorization-code-toggle" id="authorizationCodeToggle"
                                    title="Show authorization code" aria-label="Show authorization code">
                                    <i class="las la-eye"></i>
                                </button>
                            </div>
                            <div class="authorization-code-status mt-2" id="authorizationCodeStatus"></div>
                            <small class="form-text text-muted authorization-code-help">
                                @lang('Optional. Enter a code to assign or replace it; leave blank to keep the current setting.')
                            </small>
                            <label class="authorization-code-remove mt-2 d-none" id="authorizationCodeRemove">
                                <input type="checkbox" name="remove_authorization_code" value="1">
                                <span>@lang('Remove the assigned Authorization Code')</span>
                            </label>
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

@push('style')
    <style>
        .authorization-code-input { position: relative; }
        .authorization-code-input .form-control { padding-right: 44px; }
        .authorization-code-toggle { align-items: center; background: transparent; border: 0; color: #667085; display: flex; font-size: 19px; height: 100%; justify-content: center; padding: 0; position: absolute; right: 0; top: 0; width: 42px; }
        .authorization-code-toggle:focus { color: var(--base-color); outline: 0; }
        .authorization-code-status { align-items: center; display: flex; font-size: 12px; font-weight: 600; gap: 5px; }
        .authorization-code-status.is-assigned { color: #198754; }
        .authorization-code-status.is-unassigned { color: #667085; }
        .authorization-code-status.is-pending { color: #b54708; }
        .authorization-code-status.is-removal { color: #b42318; }
        .authorization-code-remove { align-items: center; color: #b42318; cursor: pointer; display: flex; font-size: 12px; gap: 7px; }
        .authorization-code-remove input { margin: 0; }
    </style>
@endpush

@push('script')
    <script>
        (function($) {

            "use strict";

            function setAuthorizationStatus(state) {
                const states = {
                    assigned: ['is-assigned', 'las la-check-circle', 'Authorization Code assigned'],
                    legacy: ['is-pending', 'las la-lock', 'Assigned code must be replaced once before it can be securely displayed'],
                    unassigned: ['is-unassigned', 'las la-info-circle', 'No Authorization Code assigned'],
                    replacement: ['is-pending', 'las la-sync', 'Authorization Code will be replaced when saved'],
                    assignment: ['is-pending', 'las la-plus-circle', 'Authorization Code will be assigned when saved'],
                    removal: ['is-removal', 'las la-exclamation-circle', 'Authorization Code will be removed when saved']
                };
                const status = states[state];

                $('#authorizationCodeStatus')
                    .removeClass('is-assigned is-unassigned is-pending is-removal')
                    .addClass(status[0])
                    .html(`<i class="${status[1]}"></i> ${status[2]}`);
            }

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
                const hasAuthorizationCode = Boolean(data && data.has_authorization_code);
                const canRevealAuthorizationCode = Boolean(data && data.has_viewable_authorization_code);
                const authorizationInput = modal.find('[name="authorization_code"]');
                const removeAuthorizationCode = modal.find('[name="remove_authorization_code"]');
                const authorizationUrl = $(this).data('authorization-url') || null;

                authorizationInput
                    .val('')
                    .attr('type', 'password')
                    .attr('placeholder', hasAuthorizationCode ? '********' : 'Optional Authorization Code')
                    .data('reveal-url', canRevealAuthorizationCode ? authorizationUrl : null)
                    .data('loaded', false)
                    .prop('required', false);
                removeAuthorizationCode.prop('checked', false);
                $('#authorizationCodeToggle')
                    .prop('disabled', hasAuthorizationCode && !canRevealAuthorizationCode)
                    .attr({ title: 'Show authorization code', 'aria-label': 'Show authorization code' })
                    .find('i').attr('class', 'las la-eye');
                $('#authorizationCodeStatus')
                    .data('assigned', hasAuthorizationCode)
                    .data('viewable', canRevealAuthorizationCode);
                setAuthorizationStatus(
                    hasAuthorizationCode
                        ? (canRevealAuthorizationCode ? 'assigned' : 'legacy')
                        : 'unassigned'
                );
                $('#authorizationCodeRemove').toggleClass('d-none', !hasAuthorizationCode);
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

            $('#authorizationCodeToggle').on('click', function() {
                const input = $('#adminAuthorizationCode');
                const showCode = input.attr('type') === 'password';
                const toggle = $(this);

                if (showCode && !input.val() && input.data('reveal-url')) {
                    toggle.prop('disabled', true).find('i').attr('class', 'las la-spinner la-spin');
                    $.getJSON(input.data('reveal-url')).done(function(response) {
                        input.val(response.authorization_code).attr('type', 'text').data('loaded', true);
                        toggle
                            .attr({ title: 'Hide authorization code', 'aria-label': 'Hide authorization code' })
                            .find('i').attr('class', 'las la-eye-slash');
                    }).fail(function(xhr) {
                        notify('error', xhr.responseJSON?.message || 'Unable to display the Authorization Code.');
                        input.attr('type', 'password');
                        toggle
                            .attr({ title: 'Show authorization code', 'aria-label': 'Show authorization code' })
                            .find('i').attr('class', 'las la-eye');
                    }).always(function() {
                        toggle.prop('disabled', false);
                    });
                    return;
                }

                input.attr('type', showCode ? 'text' : 'password');
                toggle
                    .attr({
                        title: showCode ? 'Hide authorization code' : 'Show authorization code',
                        'aria-label': showCode ? 'Hide authorization code' : 'Show authorization code'
                    })
                    .find('i').attr('class', showCode ? 'las la-eye-slash' : 'las la-eye');
            });

            $('#adminAuthorizationCode').on('input', function() {
                if ($(this).val()) {
                    $('[name="remove_authorization_code"]').prop('checked', false);
                    $('#authorizationCodeToggle').prop('disabled', false);
                    setAuthorizationStatus(
                        $('#authorizationCodeStatus').data('assigned') ? 'replacement' : 'assignment'
                    );
                } else {
                    const assigned = $('#authorizationCodeStatus').data('assigned');
                    const viewable = $('#authorizationCodeStatus').data('viewable');
                    $('#authorizationCodeToggle').prop('disabled', assigned && !viewable);
                    setAuthorizationStatus(assigned ? (viewable ? 'assigned' : 'legacy') : 'unassigned');
                }
            });

            $('[name="remove_authorization_code"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#adminAuthorizationCode').val('').attr('type', 'password');
                    $('#authorizationCodeToggle')
                        .attr({ title: 'Show authorization code', 'aria-label': 'Show authorization code' })
                        .find('i').attr('class', 'las la-eye');
                    setAuthorizationStatus('removal');
                } else {
                    const assigned = $('#authorizationCodeStatus').data('assigned');
                    const viewable = $('#authorizationCodeStatus').data('viewable');
                    $('#authorizationCodeToggle').prop('disabled', assigned && !viewable);
                    setAuthorizationStatus(assigned ? (viewable ? 'assigned' : 'legacy') : 'unassigned');
                }
            });

            $('#cuModal').on('hidden.bs.modal', function() {
                $('#adminAuthorizationCode')
                    .val('')
                    .attr('type', 'password')
                    .data('reveal-url', null)
                    .data('loaded', false);
                $('#authorizationCodeToggle')
                    .prop('disabled', false)
                    .attr({ title: 'Show authorization code', 'aria-label': 'Show authorization code' })
                    .find('i').attr('class', 'las la-eye');
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
