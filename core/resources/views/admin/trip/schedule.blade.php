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
                                    <th>@lang('Start From')</th>
                                    <th>@lang('End At')</th>
                                    <th>@lang('Duration')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schedules as $item)
                                    @php
                                        $start = Carbon\Carbon::parse($item->start_from);
                                        $end = Carbon\Carbon::parse($item->end_at);

                                        if ($end->lt($start)) {
                                            $end->addDay();
                                        }
                                        $diff = $start->diff($end);
                                    @endphp
                                    <tr>
                                        <td>{{ showDateTime($item->start_from, 'h:i A') }}</td>
                                        <td>{{ showDateTime($item->end_at, 'h:i A') }}</td>
                                        <td>{{ __($diff->format('%h hours %i minutes')) }}</td>
                                        <td>@php echo $item->statusBadge; @endphp</td>
                                        <td>
                                            <div class="button--group">
                                                <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn" data-resource="{{ $item }}" data-modal_title="@lang('Edit Schedule')">
                                                    <i class="la la-pencil"></i>@lang('Edit')
                                                </button>

                                                @if (!$item->status)
                                                    <button type="button" class="btn btn-sm btn-outline--success confirmationBtn" data-action="{{ route('admin.trip.schedule.status', $item->id) }}" data-question="@lang('Are you sure to enable this schedule?')">
                                                        <i class="la la-eye"></i>@lang('Enable')
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline--danger  confirmationBtn" data-action="{{ route('admin.trip.schedule.status', $item->id) }}" data-question="@lang('Are you sure to disable this schedule?')">
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

                @if ($schedules->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($schedules) }}
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
                <form action="{{ route('admin.trip.schedule.store') }}" method="POST">
                    @csrf

                    <div class="modal-body">
                        <div class="form-group">
                            <label> @lang('Start From')</label>
                            <input type="time" class="form-control" name="start_from" required>
                        </div>

                        <div class="form-group">
                            <label> @lang('End At')</label>
                            <input type="time" name="end_at" class="form-control" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn" data-modal_title="@lang('Add New Schedule')">
        <i class="las la-plus"></i> @lang('Add New')
    </button>
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/cu-modal.js') }}"></script>
@endpush
