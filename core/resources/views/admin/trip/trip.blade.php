@extends('admin.layouts.app')
@section('panel')
    @php
        use App\Constants\Status;
        $status = request('status');
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="col-12 mb-2">
                <form action="{{ url('/admin/manage/trip') }}">
                    <div class="d-flex flex-wrap gap-4">
                        <div style="width: 250px;">
                            <label for="">Status</label>
                            <select name="status" class="select2" required>
                                <option value="all">@lang('All status')</option>
                                <option value="1" {{ $status == 1 ? 'selected' : '' }}>@lang('Enabled')
                                </option>
                                <option value="0" {{ request()->has('status') && $status == 0 ? 'selected' : '' }}>
                                    @lang('Disabled')
                                </option>
                            </select>
                        </div>
                        <div class="align-self-end">
                            <button class="btn btn--primary w-100 h-45"><i class="fas fa-filter"></i> Filter</button>
                        </div>

                        <div class="align-self-end">
                            <a class="btn btn--success w-100 h-45" id="btn-enable-all">Enable All</a>
                        </div>
                        <div class="align-self-end">
                            <a class="btn btn--danger w-100 h-45" id="btn-disable-all">Disable All</a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Title')</th>
                                    <th>@lang('Destination')</th>
                                    <th>@lang('AC / Non-AC')</th>
                                    <th>@lang('Fleet Type')</th>
                                    <th>@lang('Day Off')</th>
                                    <th>@lang('Trip Status')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($trips as $item)
                                    <tr>
                                        <td>{{ __($item->title) }}</td>
                                        <td>{{ $item->route->startFrom->city }} → {{ $item->route->endTo->city }}</td>
                                        <td>{{ __($item->fleetType->has_ac == Status::ENABLE ? 'AC' : 'Non-Ac') }}</td>

                                        <td>{{ __($item->fleetType->name) }}</td>
                                        <td>
                                            @if ($item->day_off)
                                                @foreach ($item->day_off as $day)
                                                    {{ __(showDayOff($day)) }}@if (!$loop->last)
                                                        ,
                                                    @endif
                                                @endforeach
                                            @else
                                                @lang('No Off Day')
                                            @endif
                                        </td>

                                        <td>@php echo decodeSlug($item->trip_status); @endphp </td>
                                        <td>@php echo $item->statusBadge; @endphp </td>
                                        <td>
                                            <div class="button--group">
                                                <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn"
                                                    data-resource="{{ $item }}"
                                                    data-modal_title="@lang('Edit Trip')">
                                                    <i class="la la-pencil"></i>@lang('Edit')
                                                </button>
                                                <a href="{{ url("/admin/manage/trip/manifest-seat-layout/$item->id") }}"
                                                    target="_blank" class="btn btn-sm btn-outline--primary">
                                                    Manifest
                                                </a>
                                                @if (!$item->status)
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--success confirmationBtn"
                                                        data-action="{{ route('admin.trip.status', $item->id) }}"
                                                        data-question="@lang('Are you sure to enable this trip?')">
                                                        <i class="la la-eye"></i>@lang('Enable')
                                                    </button>
                                                @else
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--danger  confirmationBtn"
                                                        data-action="{{ route('admin.trip.status', $item->id) }}"
                                                        data-question="@lang('Are you sure to disable this trip?')">
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
                                                <option value="{{ $item->id }}"
                                                    data-name="{{ showDateTime($item->start_from, 'h:i a') . ' - ' . showDateTime($item->end_at, 'h:i a') }}">
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
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="trip_status">@lang('Trip Status')</label>
                                        <select class="select2" name="trip_status" id="trip_status">
                                            <option value="{{ Status::TRIP_ON_TIME }}">@lang(decodeSlug(Status::TRIP_ON_TIME))</option>
                                            <option value="{{ Status::TRIP_BOARDING }}">@lang(decodeSlug(Status::TRIP_BOARDING))</option>
                                            <option value="{{ Status::TRIP_DEPARTED }}">@lang(decodeSlug(Status::TRIP_DEPARTED))</option>
                                            <option value="{{ Status::TRIP_DELAYED }}">@lang(decodeSlug(Status::TRIP_DELAYED))</option>
                                            <option value="{{ Status::TRIP_CANCELLED }}">@lang(decodeSlug(Status::TRIP_CANCELLED))</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="day_off">@lang('Day Off')</label>
                                        <select class="select2-auto-tokenize" name="day_off[]" id="day_off"
                                            multiple="multiple">
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
        <script src="{{ asset('assets/admin/js/cu-modal.js?v=' . buildVer()) }}"></script>
        <script src="{{ asset('assets/global/js/sweetalert2.min.js') }}"></script>

        <script>
            $(function() {
                $(document).on('click', '#btn-disable-all', function() {
                    changeAllStatus(0)
                });


                $(document).on('click', '#btn-enable-all', function() {
                    changeAllStatus(1)
                });

                function changeAllStatus(status) {
                    let status_txt = 'enable';
                    status_txt = !status ? 'disable' : status_txt;
                    Swal.fire({
                        title: 'Please confirm',
                        html: `Are you sure you want to ${status_txt} all the trips?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes'
                    }).then((result) => {
                        if (result.isConfirmed) {

                            Swal.fire({
                                title: 'Please wait...',
                                html: '',
                                timerProgressBar: true,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            })
                            $.ajax({
                                    type: 'POST',
                                    url: `{{ route('admin.trip.changeAllStatus') }}`,
                                    data: {
                                        '_token': "{{ csrf_token() }}",
                                        'status': status
                                    },
                                })
                                .done(function(data) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '',
                                        html: data.message,
                                    })
                                    setTimeout(() => {
                                        location.reload()
                                    }, 2500);
                                })
                                .fail(function() {
                                    alert("Error occured. Please try again.", 'error');
                                });
                        }
                    })
                }
            })
        </script>
    @endpush
