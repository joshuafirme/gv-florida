@extends('admin.layouts.app')

@section('panel')
    <div class="row mb-none-30">
        <div class="col-xl-12 col-lg-12 col-md-12 mb-30">
            <div class="card shadow-sm">

                <div class="card-body">
                    <form
                        action="{{ isset($ticketPrice) ? route('admin.trip.ticket.price.update', $ticketPrice->id) : route('admin.trip.ticket.price.store') }}"
                        method="POST">
                        @csrf
                        @if (isset($ticketPrice))
                            @method('POST')
                        @endif

                        <input type="hidden" name="main_price" id="main_price_input" value="{{ $ticketPrice->price ?? 0 }}">

                        <div class="row gy-4 mb-4">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="text-danger fw-bold">@lang('Bus Type')</label>
                                    <select name="fleet_type" id="fleet_type" class="select2-basic" required>
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($fleetTypes as $item)
                                            <option value="{{ $item->id }}" @selected(old('fleet_type', $ticketPrice->fleet_type_id ?? '') == $item->id)>
                                                {{ __($item->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="text-danger fw-bold">@lang('Route')</label>
                                    <select name="route" id="route" class="select2-basic" required>
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($routes as $item)
                                            <option value="{{ $item->id }}" @selected(old('route', $ticketPrice->vehicle_route_id ?? '') == $item->id)>
                                                {{ __($item->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 price-error-message mb-3"></div>

                        <div class="price-wrapper d-none border rounded bg-light">
                            <div
                                class="d-flex justify-content-between align-items-center p-3 border-bottom bg-white rounded-top">
                                <span class="text-muted" style="font-size: 14px;">
                                    Ticket price from <strong class="text-dark" id="origin-name">ORIGIN</strong> (origin) to
                                    each stop.
                                </span>
                                <span class="text-muted fw-bold" style="font-size: 14px;" id="fleet-type-name">-</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-muted" style="font-size: 12px; letter-spacing: 0.5px;">
                                        <tr>
                                            <th class="ps-4">@lang('STOP')</th>
                                            <th>@lang('KM POST')</th>
                                            <th class="text-end pe-4">@lang('TICKET PRICE')</th>
                                        </tr>
                                    </thead>
                                    <tbody id="price-table-body" class="bg-white">
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary h-45 w-100 mt-4 submit-button"
                            disabled>@lang('Submit')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-back route="{{ route('admin.trip.ticket.price.index') }}" />
@endpush

@push('script')
    <script>
        "use strict";

        (function($) {
            // Master Datasets passed from Controller
            const routesData = @json($routes->keyBy('id'));
            const countersData = @json($counters->keyBy('id'));
            const existingPrices = @json($existingPrices ?? []);
            const isUpdateMode = {{ isset($ticketPrice) ? 'true' : 'false' }};

            // Initialize Select2
            $('.select2-basic').select2({
                width: '100%'
            });

            // Listeners
            $(document).on('change', 'select[name=fleet_type], select[name=route]', function() {
                var routeId = $('select[name="route"]').val();
                var fleetTypeId = $('select[name="fleet_type"]').val();
                var fleetTypeName = $('select[name="fleet_type"] option:selected').text().trim();

                $('#fleet-type-name').text(fleetTypeName || '-');

                if (routeId && fleetTypeId) {
                    checkDuplicateAndRender(routeId, fleetTypeId);
                } else {
                    $('.price-wrapper').addClass('d-none');
                    $('.submit-button').attr('disabled', 'disabled');
                }
            });

            // Mirror destination price to hidden main_price for backend validation
            $(document).on('input', '.destination-price-input', function() {
                $('#main_price_input').val($(this).val());
            });

            function checkDuplicateAndRender(routeId, fleetTypeId) {
                // If we are updating the SAME ticket price, skip the duplicate check
                if (isUpdateMode && routeId == '{{ $ticketPrice->vehicle_route_id ?? '' }}' && fleetTypeId ==
                    '{{ $ticketPrice->fleet_type_id ?? '' }}') {
                    renderTable(routeId);
                    return;
                }

                var data = {
                    'vehicle_route_id': routeId,
                    'fleet_type_id': fleetTypeId
                };

                $.ajax({
                    url: "{{ route('admin.trip.ticket.get_route_data') }}",
                    method: "GET",
                    data: data,
                    success: function(result) {
                        if (result.error) {
                            $('.price-error-message').html(
                                `<div class="alert alert-danger mb-0"><i class="las la-exclamation-circle"></i> ${result.error}</div>`
                                );
                            $('.price-wrapper').addClass('d-none');
                            $('.submit-button').attr('disabled', 'disabled');
                        } else {
                            $('.price-error-message').html('');
                            renderTable(routeId);
                        }
                    }
                });
            }

            function renderTable(routeId) {
                var route = routesData[routeId];
                if (!route || !route.stoppages) return;

                var stoppages = route.stoppages; // Assuming this casts to an array of IDs
                var startFromId = route.start_from;
                var html = '';

                // Set Origin Header Name
                var originCounter = countersData[startFromId];
                if (originCounter) $('#origin-name').text(originCounter.name);

                stoppages.forEach(function(stopId, index) {
                    var counter = countersData[stopId];
                    if (!counter) return;

                    var isOrigin = (index === 0);
                    var isDestination = (index === stoppages.length - 1);
                    var inputKey = `${startFromId}-${counter.id}`;
                    var priceValue = existingPrices[inputKey] !== undefined ? existingPrices[inputKey] : 0;

                    if (isOrigin) {
                        html += `
                        <tr>
                            <td class="ps-4 py-3">
                                <span class="badge bg-info text-white me-2 px-2 py-1" style="font-size: 10px; letter-spacing: 1px;">ORIGIN</span> 
                                <strong class="text-dark">${counter.name}</strong>
                            </td>
                            <td class="text-muted">${counter.km_post}</td>
                            <td class="text-end text-muted pe-4">—</td>
                        </tr>`;
                    } else {
                        var inputClass = isDestination ? 'destination-price-input' : '';

                        html += `
                        <tr>
                            <td class="ps-4 py-3"><strong class="text-dark">${counter.name}</strong></td>
                            <td class="text-muted">${counter.km_post}</td>
                            <td class="pe-4" style="width: 250px;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted border-end-0">₱</span>
                                    <input type="number" step="any" min="0" 
                                           class="form-control border-start-0 ${inputClass}" 
                                           name="price[${inputKey}]" 
                                           value="${priceValue}" required>
                                </div>
                            </td>
                        </tr>`;
                    }
                });

                $('#price-table-body').html(html);
                $('.price-wrapper').removeClass('d-none');
                $('.submit-button').removeAttr('disabled');

                // Trigger main_price sync for pre-populated forms
                $('.destination-price-input').trigger('input');
            }

            // Init call on page load for Update mode
            if ($('select[name=route]').val() && $('select[name=fleet_type]').val()) {
                $('select[name=route]').trigger('change');
            }

        })(jQuery);
    </script>
@endpush
