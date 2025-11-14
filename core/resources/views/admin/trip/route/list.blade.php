@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-12 mb-2">
            <form action="{{ url('/admin/manage/route') }}">
                <div class="d-flex flex-wrap gap-4">
                    <div style="width: 250px;">
                        <label for="">Status</label>
                        <select name="status" class="select2" required>
                            <option value="all">@lang('All status')</option>
                            <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>@lang('Enabled')</option>
                            <option value="0" {{ request('status') == 0 ? 'selected' : '' }}>@lang('Disabled')</option>
                        </select>
                    </div>
                    <div class="align-self-end">
                        <button class="btn btn--primary w-100 h-45"><i class="fas fa-filter"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Starting Point')</th>
                                    <th>@lang('Ending Point')</th>
                                    <th>@lang('Distance')</th>
                                    <th>@lang('Time')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($routes as $item)
                                    <tr>
                                        <td>{{ __($item->name) }}</td>
                                        <td>{{ __($item->startFrom->name) }}</td>
                                        <td>{{ __($item->endTo->name) }}</td>
                                        <td>{{ __($item->distance) }}</td>
                                        <td>{{ __($item->time) }}</td>
                                        <td>@php echo $item->statusBadge; @endphp</td>
                                        <td>
                                            <div class="button--group">
                                                <a href="{{ route('admin.trip.route.edit', $item->id) }}"
                                                    class="btn btn-sm btn-outline--primary">
                                                    <i class="la la-pencil"></i>@lang('Edit')
                                                </a>

                                                @if (!$item->status)
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--success confirmationBtn"
                                                        data-action="{{ route('admin.trip.route.status', $item->id) }}"
                                                        data-question="@lang('Are you sure to enable this route?')">
                                                        <i class="la la-eye"></i>@lang('Enable')
                                                    </button>
                                                @else
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--danger  confirmationBtn"
                                                        data-action="{{ route('admin.trip.route.status', $item->id) }}"
                                                        data-question="@lang('Are you sure to disable this route?')">
                                                        <i class="la la-eye-slash"></i>@lang('Disable')
                                                    </button>
                                                @endif
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

                @if ($routes->hasPages())
                    <div class="card-footer py-4">
                        @php echo paginateLinks($routes) @endphp
                    </div>
                @endif
            </div>
        </div>
    </div>
    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder="Search by name..." />
    <a href="{{ route('admin.trip.route.create') }}" class="btn btn-sm btn-outline--primary h-45">
        <i class="las la-plus"></i> @lang('Add New')
    </a>
@endpush
