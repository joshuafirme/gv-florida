@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Email')</th>
                                    <th>@lang('Username')</th>
                                    <th>@lang('Role')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data as $item)
                                    <tr>
                                        <td>{{ __($item->name) }}</td>
                                        <td>{{ __($item->email) }}</td>
                                        <td>{{ __($item->username) }}</td>
                                        <td>{{ __($item->role) }}</td>
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
                                                        <i class="la la-trash"></i>@lang('Delete')</button>
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
    <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn" data-modal_title="@lang('Add Admin')">
        <i class="las la-plus"></i> @lang('Add New')
    </button>
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/cu-modal.js?v=' . env('APP_VERSION')) }}"></script>
@endpush

@push('script')
    <script>
        (function($) {

            "use strict";

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

        })(jQuery);
    </script>
@endpush
