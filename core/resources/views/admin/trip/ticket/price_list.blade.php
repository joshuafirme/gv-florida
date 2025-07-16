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
                                    <th>@lang('Fleet Type')</th>
                                    <th>@lang('Route')</th>
                                    <th>@lang('Price')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($prices as $item)
                                    <tr>
                                        <td>{{ __($item->fleetType->name) }}</td>
                                        <td>{{ __($item->route->name) }}</td>
                                        <td>{{ __(showAmount($item->price)) }}</td>
                                        <td>
                                            <div class="button--group">
                                                <a href="{{ route('admin.trip.ticket.price.edit', $item->id) }}" class="btn btn-sm btn-outline--primary">
                                                    <i class="la la-pencil"></i>@lang('Edit')
                                                </a>

                                                <button type="button" class="btn btn-sm btn-outline--danger confirmationBtn" data-question="@lang('Are you sure to remove price list?')" data-action="{{ route('admin.trip.ticket.price.delete', $item->id) }}"><i class="la la-trash"></i>@lang('Remove')
                                                </button>

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

                @if ($prices->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($prices) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection
@push('breadcrumb-plugins')
    <a href="{{ route('admin.trip.ticket.price.create') }}" class="btn btn-sm btn-outline--primary">
        <i class="las la-plus"></i> @lang('Add New')
    </a>
@endpush
