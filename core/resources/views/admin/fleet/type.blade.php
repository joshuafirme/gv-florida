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
                                    <th>@lang('Seat Layout')</th>
                                    <th>@lang('No of Deck')</th>
                                    <th>@lang('Total Seat')</th>
                                    <th>@lang('Facilities')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fleetType as $item)
                                    <tr>
                                        <td>{{ __($item->name) }}</td>
                                        <td>{{ __($item->seat_layout) }}</td>
                                        <td>{{ __($item->deck) }}</td>
                                        <td>{{ array_sum($item->deck_seats) }}</td>
                                        <td>
                                            @if ($item->facilities)
                                                {{ __(implode(',', $item->facilities)) }}
                                            @else
                                                @lang('No facilities')
                                            @endif
                                        </td>
                                        <td>@php echo $item->statusBadge; @endphp</td>

                                        <td>
                                            <div class="button--group">
                                                <button type="button" class="btn btn-sm btn-outline--primary cuModalBtn" data-resource="{{ $item }}" data-modal_title="@lang('Edit Type')">
                                                    <i class="la la-pencil"></i>@lang('Edit')
                                                </button>

                                                @if (!$item->status)
                                                    <button type="button" class="btn btn-sm btn-outline--success confirmationBtn" data-action="{{ route('admin.fleet.type.status', $item->id) }}" data-question="@lang('Are you sure to enable this type?')">
                                                        <i class="la la-eye"></i>@lang('Enable')
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline--danger  confirmationBtn" data-action="{{ route('admin.fleet.type.status', $item->id) }}" data-question="@lang('Are you sure to disable this type?')">
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

                @if ($fleetType->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($fleetType) }}
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
                <form action="{{ route('admin.fleet.type.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label> @lang('Name')</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="form-group">
                            <label> @lang('Seat Layout')</label>
                            <select name="seat_layout" class="form-control select2" data-minimum-results-for-search="-1">
                                <option value="">@lang('Select an option')</option>
                                @foreach ($seatLayouts as $item)
                                    <option value="{{ $item->layout }}">{{ __($item->layout) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label> @lang('No of Deck')</label>
                            <input type="number" min="0" class="form-control" name="deck" required>
                        </div>
                        <div class="showSeat"></div>
                        <div class="form-group">
                            <label for="facilities">@lang('Facilities')</label>
                            <select class="select2-auto-tokenize" name="facilities[]" id="facilities" multiple="multiple">
                                @foreach ($facilities as $item)
                                    <option value="{{ $item->data_values->title }}">
                                        {{ $item->data_values->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="inputName">@lang('AC status')</label>
                            <input type="checkbox" data-width="100%" data-height="40px" data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle" data-on="@lang('YES')" data-off="@lang('NO')" name="has_ac">
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

                if (data.has_ac) {
                    modal.find('input[name=has_ac]').bootstrapToggle('on');
                } else {
                    modal.find('input[name=has_ac]').bootstrapToggle('off');
                }

                $('.showSeat').empty();
                if (data.deck) {
                    for (var i = 1; i <= data.deck; i++) {
                        $('.showSeat').append(`
                            <div class="form-group">
                                <label> Seats of Deck - ${i} </label>
                                <input type="text" class="form-control hasArray" placeholder="@lang('Enter Number of Seat')" value="${data.deck_seats[i-1]}" name="deck_seats[]" required>
                            </div>
                        `);
                    }
                }

                if (data.facilities) {
                    $('#facilities').val(data.facilities).trigger("change");
                } else {
                    $('#facilities').val('').trigger("change");
                }
            });
        })(jQuery);
    </script>
@endpush
