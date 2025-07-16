@extends('admin.layouts.app')

@section('panel')
    <div class="row mb-none-30">
        <div class="col-xl-12 col-lg-12 col-md-12 mb-30">
            <div class="card">
                <h5 class="card-header">@lang('Information About Ticket Price') </h5>
                <div class="card-body">

                    <form action="{{ route('admin.trip.ticket.price.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label> @lang('Fleet Type')</label>
                                    <select name="fleet_type" class="select2" required>
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($fleetTypes as $item)
                                            <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label> @lang('Route')</label>
                                    <select name="route" class="select2" required>
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($routes as $item)
                                            <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label> @lang('Price For Source To Destination')</label>
                                    <div class="input-group">
                                        <div class="input-group-text">{{ __(gs('cur_text')) }}</div>
                                        <input type="number" step="any" class="form-control" name="main_price" required value="{{ old('main_price') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 price-error-message"></div>

                            <div class="price-wrapper col-md-12"></div>
                        </div>
                        <button type="submit" class="btn btn--primary h-45 w-100 submit-button">@lang('Submit')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('breadcrumb-plugins')
    <a href="{{ route('admin.trip.ticket.price.index') }}" class="btn btn-sm btn-outline--primary "><i class="la la-fw la-backward"></i>@lang('Go Back')</a>
@endpush

@push('script')
    <script>
        "use strict";

        (function($) {
            $(document).on('change', 'select[name=fleet_type] , select[name=route]', function() {
                var routeId = $('select[name="route"]').find("option:selected").val();
                var fleetTypeId = $('select[name="fleet_type"]').find("option:selected").val();

                if (routeId && fleetTypeId) {
                    var data = {
                        'vehicle_route_id': routeId,
                        'fleet_type_id': fleetTypeId
                    }
                    $.ajax({
                        url: "{{ route('admin.trip.ticket.get_route_data') }}",
                        method: "get",
                        data: data,
                        success: function(result) {
                            if (result.error) {
                                $('.price-error-message').html(
                                    `<h5 class="text--danger">${result.error}</h5>`);
                                $('.price-wrapper').html('');
                                $('.submit-button').attr('disabled', 'disabled');
                            } else {
                                $('.price-error-message').html(``);
                                $('.submit-button').removeAttr('disabled');
                                $('.price-wrapper').html(`<h5>${result}</h5>`);
                            }
                        }
                    });
                } else {
                    $('.price-wrapper').html('');
                }
            })
        })(jQuery)
    </script>
@endpush
