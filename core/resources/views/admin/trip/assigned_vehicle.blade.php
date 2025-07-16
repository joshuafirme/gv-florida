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
                                    <th>@lang('Trip')</th>
                                    <th>@lang('Vehicle\'s Nick Name')</th>
                                    <th>@lang('Reg. No.')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignedVehicles as $item)
                                    <tr>
                                        <td>
                                            {{ __($item->trip->title) }}
                                        </td>
                                        <td>
                                            {{ __($item->vehicle->nick_name) }}
                                        </td>
                                        <td>
                                            {{ __($item->vehicle->register_no) }}
                                        </td>
                                        <td>@php echo $item->statusBadge; @endphp </td>
                                        <td>
                                            <div class="button--group">
                                                <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn"
                                                    data-resource="{{ $item }}" data-modal_title="@lang('Update Trip Assigned Vehicle')">
                                                    <i class="la la-pencil"></i>@lang('Edit')
                                                </button>

                                                @if (!$item->status)
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--success confirmationBtn"
                                                        data-action="{{ route('admin.trip.vehicle.assign.status', $item->id) }}"
                                                        data-question="@lang('Are you sure to enable this assigned vehicle?')">
                                                        <i class="la la-eye"></i>@lang('Enable')
                                                    </button>
                                                @else
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline--danger  confirmationBtn"
                                                        data-action="{{ route('admin.trip.vehicle.assign.status', $item->id) }}"
                                                        data-question="@lang('Are you sure to disable this assigned vehicle?')">
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


                @if ($assignedVehicles->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($assignedVehicles) }}
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
                <form action="{{ route('admin.trip.vehicle.assign.store') }}" method="POST">
                    @csrf

                    <div class="modal-body">
                        <div class="form-group">
                            <label> @lang('Trip')</label>
                            <select class="select2 form-control" name="trip_id">
                                <option value="">@lang('Select an option')</option>
                                @foreach ($trips as $item)
                                    <option value="{{ $item->id }}"
                                        data-vehicles="{{ $item->fleetType->activeVehicles }}">{{ __($item->title) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label> @lang('Vehicle')</label>
                            <select class="select2 form-control" name="vehicle_id">
                                <option value="">@lang('Select an option')</option>
                            </select>
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
    <button type="button" class="btn btn-sm btn-outline--primary h-45 cuModalBtn" data-modal_title="@lang('Assign Trip Vehicle')">
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

            $('.cuModalBtn').on('click', function() {
                let vehicles = @json(@$item->fleetType->activeVehicles);

                let data = $(this).data();

                if (data.resource) {
                    vehicles.forEach((vehicle) => {
                        $('#cuModal').find('select[name=vehicle_id]').append(
                            `<option value="${vehicle.id}" data-name="${vehicle.register_no}"> ${vehicle.nick_name} (${vehicle.register_no}) </option>`
                        )
                    });
                }else{
                    $("#cuModal").find("select[name=vehicle_id]").val(null).trigger('change');
                    $("#cuModal").find("select[name=trip_id]").val(null).trigger('change');

                }

            });

            $(document).on('change', 'select[name="trip_id"]', function() {
                var vehicles = $(this).parents('.modal-body').find('select[name="trip_id"]').find(
                    "option:selected").data('vehicles');


                var options = `<option selected value="">@lang('Select an option')</option>`

                $.each(vehicles, function(i, v) {
                    options +=
                        `<option value="${v.id}" data-name="${v.register_no}"> ${v.nick_name} (${v.register_no}) </option>`
                });

                $(this).parents('.modal-body').find('select[name=vehicle_id]').html(options);

            });
        })(jQuery);
    </script>
@endpush
