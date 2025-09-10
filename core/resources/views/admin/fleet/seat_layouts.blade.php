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
                                    <th>@lang('S.N.')</th>
                                    <th>@lang('Layout')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($layouts as $item)
                                    <tr>
                                        <td>{{ $item->current_page - 1 * $item->per_page + $loop->iteration }}</td>
                                        <td>{{ __($item->layout) }}</td>
                                        <td>
                                            <div class="button--group">
                                                <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn" data-resource="{{ $item }}" data-modal_title="@lang('Edit Layout')"><i class="la la-pencil"></i>@lang('Edit')</button>

                                                <button type="button" class="btn btn-sm btn-outline--danger confirmationBtn" data-question="@lang('Are you sure to remove layout?')" data-action="{{ route('admin.fleet.layouts.remove', $item->id) }}"><i class="la la-trash"></i>@lang('Remove')</button>
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

                @if ($layouts->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($layouts) }}
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
                    <h5 class="modal-title"> @lang('Add Counter')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.fleet.layouts.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('Layout')</label>
                            <input type="text" class="form-control" placeholder="@lang('Eg: 2 x 3')" name="layout" required>
                            <small class="text-primary text--small"><i class="fas fa-info-circle"></i> @lang('Just type left and right value, a separator (x) will be added automatically')</small>
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
    <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn" data-modal_title="@lang('Add New layout')">
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

            // $(document).on('keypress', 'input[name=layout]', function(e) {
            //     var layout = $(this).val();
            //     if (layout != '') {
            //         if (layout.length > 0 && layout.length <= 2)
            //             $(this).val(`${layout} x `);

            //         // if (layout.length > 4) {
            //         //     return false;
            //         // }
            //     }
            // });

            // $(document).on('keyup', 'input[name=layout]', function(e) {
            //     var key = event.keyCode || event.charCode;
            //     console.log(key)
            //     if (key == 8 || key == 46) {
            //         $(this).val($(this).val().replace(' x ', ''));
            //     }

            // });


        })(jQuery);
    </script>
@endpush
