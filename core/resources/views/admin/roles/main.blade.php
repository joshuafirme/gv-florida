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
                                    {{-- <th>@lang('Permissions')</th> --}}
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data as $item)
                                    <tr>
                                        <td>{{ __($item->name) }}</td>
                                        {{-- <td>
                                            <div style="white-space: unset !important; overflow: auto;">
                                                @php
                                                    if ($item->permissions != 'null' && $item->permissions) {
                                                        $permissions = json_decode($item->permissions);
                                                        foreach ($permissions as $value) {
                                                            echo '<span class="badge m-1 rounded-pill bg-success">' .
                                                                decodeSlug(str_replace('admin.', '', $value), '.') .
                                                                '</span>';
                                                        }
                                                    }
                                                @endphp
                                            </div>
                                        </td> --}}
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
                                                        data-action="{{ route('admin.roles.remove', $item->id) }}">
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
                                            <label><strong>{{ $item->title }}
                                                </strong></label>
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
    <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn" data-modal_title="@lang('Add Role')">
        <i class="las la-plus"></i> @lang('Add New')
    </button>
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/cu-modal.js') }}"></script>
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

                    let permissions = data.permissions ? JSON.parse(data.permissions) : [];

                    console.log(permissions)
                    
                    $('*[id^="chkbx-"]').prop('checked', false);
                    for (let i = 0; i < permissions.length; i++) {
                        $(`input[id='chkbx-${permissions[i]}']`).prop('checked', true);
                    }
                    let parent_checkboxes = JSON.parse($('#parent_permissions').val());
                    let checked_counter = 0;

                    for (let el of parent_checkboxes) {
                        let parent_module = $(`input[id="chkbx-all-${el}"]`);
                        console.log(parent_module.val())
                        if (parent_module && !parent_module.val().includes('*')) {
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

        })(jQuery);
    </script>
@endpush
