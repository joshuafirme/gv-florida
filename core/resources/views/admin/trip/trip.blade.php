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
                                    <th>@lang('Title')</th>
                                    <th>@lang('AC / Non-AC')</th>
                                    <th>@lang('Day Off')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($trips as $item)
                                    <tr>
                                        <td>{{ __($item->title) }}</td>

                                        <td>{{ __($item->fleetType->has_ac == Status::ENABLE ? 'AC' : 'Non-Ac') }}</td>

                                        <td>
                                            @if ($item->day_off)
                                                @foreach ($item->day_off as $day)
                                                    {{ __(showDayOff($day)) }}@if (!$loop->last),
                                                    @endif
                                                @endforeach
                                            @else
                                                @lang('No Off Day')
                                            @endif
                                        </td>

                                        <td>@php echo $item->statusBadge; @endphp </td>
                                        <td>
                                            <div class="button--group">
                                                <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn" data-resource="{{ $item }}" data-modal_title="@lang('Edit Trip')">
                                                    <i class="la la-pencil"></i>@lang('Edit')
                                                </button>

                                                @if (!$item->status)
                                                    <button type="button" class="btn btn-sm btn-outline--success confirmationBtn" data-action="{{ route('admin.trip.status', $item->id) }}" data-question="@lang('Are you sure to enable this trip?')">
                                                        <i class="la la-eye"></i>@lang('Enable')
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline--danger  confirmationBtn" data-action="{{ route('admin.trip.status', $item->id) }}" data-question="@lang('Are you sure to disable this trip?')">
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

                @if ($trips->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($trips) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-confirmation-modal />

    <div id="cuModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.trip.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label> @lang('Title')</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label> @lang('Fleet Type')</label>
                                    <select name="fleet_type_id" class="select2" required>
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($fleetTypes as $item)
                                            <option value="{{ $item->id }}" data-name="{{ $item->name }}">
                                                {{ __($item->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label> @lang('Route')</label>
                                    <select name="vehicle_route_id" class="select2" required>
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($routes as $item)
                                            <option value="{{ $item->id }}" data-name="{{ $item->name }}">
                                                {{ __($item->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label> @lang('Schedule')</label>
                                    <select name="schedule_id" class="select2" required>
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($schedules as $item)
                                            <option value="{{ $item->id }}" data-name="{{ showDateTime($item->start_from, 'h:i a') . ' - ' . showDateTime($item->end_at, 'h:i a') }}">
                                                {{ __(showDateTime($item->start_from, 'h:i a') . ' - ' . showDateTime($item->end_at, 'h:i a')) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label> @lang('Start From')</label>
                                    <select name="start_from" class="select2" required>
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($stoppages as $item)
                                            <option value="{{ $item->id }}" data-name="{{ $item->name }}">
                                                {{ __($item->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label> @lang('End To')</label>
                                    <select name="end_to" class="select2" required>
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($stoppages as $item)
                                            <option value="{{ $item->id }}" data-name="{{ $item->name }}">
                                                {{ __($item->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label for="day_off">@lang('Day Off')</label>
                                    <select class="select2-auto-tokenize" name="day_off[]" id="day_off" multiple="multiple">
                                        <option value="0">@lang('Sunday')</option>
                                        <option value="1">@lang('Monday')</option>
                                        <option value="2">@lang('Tuesday')</option>
                                        <option value="3">@lang('Wednesday')</option>
                                        <option value="4">@lang('Thursday')</option>
                                        <option value="5">@lang('Friday')</option>
                                        <option value="6">@lang('Saturday')</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn h-45 w-100 btn--primary">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder="Search by name..." />
    <button type="button" class="btn btn-outline--primary cuModalBtn" data-modal_title="@lang('Add New Trip')">
        <i class="las la-plus"></i> @lang('Add New')
    </button>
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/cu-modal.js') }}"></script>
@endpush
