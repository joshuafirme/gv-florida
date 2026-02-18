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
                                    <th>@lang('UID')</th>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Counter')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kiosks as $item)
                                    <tr>
                                        <td>{{ __($item->uid) }}</td>
                                        <td>{{ __($item->name) }}</td>
                                        <td>{{ __($item->counter ? $item->counter->name : '') }}</td>
                                        <td>
                                            @php echo $item->statusBadge; @endphp
                                        </td>
                                        <td>
                                            <div class="button--group">

                                                <a target="_blank" href="{{ route('ticket', ['kiosk_id' => $item->id, 'counter_id' => $item->counter->id]) }}"
                                                    class="btn btn-sm btn-outline--primary">
                                                    <i class="la la-ticket"></i>@lang('Open Kiosk')
                                                </a>

                                                <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn"
                                                    data-resource="{{ $item }}"
                                                    data-modal_title="@lang('Edit')">
                                                    <i class="la la-pencil"></i>@lang('Edit')
                                                </button>

                                                @if (!$item->status)
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--success confirmationBtn"
                                                        data-action="{{ route('admin.kiosk.status', $item->id) }}"
                                                        data-question="@lang('Are you sure to enable this kiosk?')">
                                                        <i class="la la-eye"></i>@lang('Enable')
                                                    </button>
                                                @else
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--danger  confirmationBtn"
                                                        data-action="{{ route('admin.kiosk.status', $item->id) }}"
                                                        data-question="@lang('Are you sure to disable this kiosk?')">
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
                    </div>
                </div>

                @if ($kiosks->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($kiosks) }}
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
                <form action="{{ route('admin.kiosk.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label> @lang('UID')</label>
                            <input type="text" class="form-control" name="uid" required>
                        </div>
                        <div class="form-group">
                            <label> @lang('Name')</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>

                        <div class="form-group">
                            <label> @lang('Counter')</label>
                            <select name="counter_id" class="select2" required>
                                <option value="">@lang('Select an option')</option>
                                @foreach ($counters as $item)
                                    <option value="{{ $item->id }}" data-name="{{ $item->name }}">
                                        {{ __($item->name) }}</option>
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
    <x-search-form placeholder="Search..." />
    <button type="button" class="btn btn-sm btn-outline--primary h-45 cuModalBtn" data-modal_title="@lang('Add New kiosk')">
        <i class="las la-plus"></i> @lang('Add New')
    </button>
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/cu-modal.js?v=' . buildVer()) }}"></script>
@endpush
