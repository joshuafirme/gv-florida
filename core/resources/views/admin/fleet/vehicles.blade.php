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
                                    <th>@lang('Nick Name')</th>
                                    <th>@lang('Reg. No.')</th>
                                    <th>@lang('Engine No.')</th>
                                    <th>@lang('Chassis No.')</th>
                                    <th>@lang('Model No.')</th>
                                    <th>@lang('Bus No.')</th>
                                    <th>@lang('Fleet Type')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vehicles as $item)
                                    <tr>
                                        <td>{{ __($item->nick_name) }}</td>
                                        <td>{{ __($item->register_no) }}</td>
                                        <td>{{ __($item->engine_no) }}</td>
                                        <td>{{ __($item->chasis_no) }}</td>
                                        <td>{{ __($item->model_no) }}</td>
                                        <td>{{ __($item->bus_no) }}</td>
                                        <td>{{ __($item->fleetType->name) }}</td>
                                        <td>
                                            @php echo $item->statusBadge; @endphp
                                        </td>
                                        <td>
                                            <div class="button--group">
                                                <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn"
                                                    data-resource="{{ $item }}" data-modal_title="@lang('Edit Vehicle')">
                                                    <i class="la la-pencil"></i>@lang('Edit')
                                                </button>

                                                @if (!$item->status)
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--success confirmationBtn"
                                                        data-action="{{ route('admin.fleet.vehicles.status', $item->id) }}"
                                                        data-question="@lang('Are you sure to enable this vehicle?')">
                                                        <i class="la la-eye"></i>@lang('Enable')
                                                    </button>
                                                @else
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--danger  confirmationBtn"
                                                        data-action="{{ route('admin.fleet.vehicles.status', $item->id) }}"
                                                        data-question="@lang('Are you sure to disable this vehicle?')">
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

                @if ($vehicles->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($vehicles) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="cuModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.fleet.vehicles.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label> @lang('Nick Name')</label>
                            <input type="text" class="form-control" name="nick_name" required>
                        </div>
                        <div class="form-group">
                            <label> @lang('Fleet Type')</label>
                            <select name="fleet_type_id" class="form-control select2"  data-minimum-results-for-search="-1">
                                <option value="">@lang('Select an option')</option>
                                @foreach ($fleetType as $item)
                                    <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label> @lang('Register No.')</label>
                            <input type="text" class="form-control" name="register_no" required>
                        </div>
                        <div class="form-group">
                            <label> @lang('Engine No.')</label>
                            <input type="text" class="form-control" name="engine_no" required>
                        </div>
                        <div class="form-chassis">
                            <label> @lang('Chassis No.')</label>
                            <input type="text" class="form-control" name="chasis_no" required>
                        </div>
                        <div class="form-group">
                            <label> @lang('Model No.')</label>
                            <input type="text" class="form-control" name="model_no" required>
                        </div>
                        <div class="form-group">
                            <label> @lang('Bus No.')</label>
                            <input type="text" class="form-control" name="bus_no">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder="Search by name..." />
    <button type="button" class="btn btn-outline--primary cuModalBtn" data-modal_title="@lang('Add New Vehicle')">
        <i class="las la-plus"></i> @lang('Add New')
    </button>
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/cu-modal.js?v=' . buildVer()) }}"></script>
@endpush

