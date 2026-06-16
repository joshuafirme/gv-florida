@extends('admin.layouts.app')

@section('panel')
    <div class="row mb-none-30">
        <div class="col-xl-12 col-lg-12 col-md-12 mb-30">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __($pageTitle) }}</h5>
                </div>

                <div class="card-body">
                    <form
                        action="{{ isset($route) ? route('admin.trip.route.update', $route->id) : route('admin.trip.route.store') }}"
                        method="POST">
                        @csrf
                        @if (isset($route))
                            @method('POST')
                        @endif

                        <div class="row gy-4">
                            <div class="col-md-12">
                                <div class="form-group mb-0">
                                    <label class="text-muted text-uppercase font-weight-bold">@lang('Name') <small
                                            class="text-lowercase">(optional)</small></label>
                                    <input type="text" class="form-control" name="name"
                                        value="{{ old('name', $route->name ?? '') }}" id="route_name">
                                    <small class="text--info mt-2 d-block auto-name-hint" style="min-height: 18px;">
                                        @if (isset($route))
                                            Auto: {{ $route->name }}
                                        @endif
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Starting Point')</label>
                                    <select name="start_from" id="start_from" class="select2-basic" required>
                                        <option value="" data-km="0">@lang('Select an option')</option>
                                        @foreach ($allStoppages as $item)
                                            <option value="{{ $item->id }}" data-km="{{ $item->km_post }}"
                                                @selected(old('start_from', $route->start_from ?? '') == $item->id)>
                                                {{ __($item->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Destination')</label>
                                    <select name="end_to" id="end_to" class="select2-basic" required>
                                        <option value="" data-km="0">@lang('Select an option')</option>
                                        @foreach ($allStoppages as $item)
                                            <option value="{{ $item->id }}" data-km="{{ $item->km_post }}"
                                                @selected(old('end_to', $route->end_to ?? '') == $item->id)>
                                                {{ __($item->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox form-check-primary">
                                        <input type="checkbox" class="custom-control-input" id="has-stoppage"
                                            {{ isset($route) && count($stoppages) > 0 ? 'checked' : '' }}>
                                        <label class="custom-control-label fw-bold"
                                            for="has-stoppage">@lang('Has More Stoppage')</label>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="col-md-12 stoppages-wrapper border rounded p-4 {{ isset($route) && count($stoppages) > 0 ? '' : 'd-none' }}">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h6 class="mb-0 fw-bold text-muted">@lang('Intermediate Stops')</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary add-stoppage-btn">
                                        <i class="las la-plus"></i> @lang('Add Stop')
                                    </button>
                                </div>

                                <div class="row stoppages-row">
                                    @if (isset($route) && count($stoppages) > 0)
                                        @foreach ($stoppages as $index => $stop)
                                            <div class="col-md-12 stoppage-item mb-4">
                                                <div class="row align-items-center">
                                                    <div class="col-md-10">
                                                        <label class="text-muted font-weight-bold"
                                                            style="font-size: 12px;">STOP</label>
                                                        <div class="input-group">
                                                            <span
                                                                class="input-group-text serial">{{ $index + 1 }}.</span>
                                                            <select class="select2-basic stoppage-select"
                                                                name="stoppages[{{ $index + 1 }}]" required>
                                                                <option value="">@lang('Select Stoppage')</option>
                                                                @foreach ($allStoppages as $stoppageItem)
                                                                    <option value="{{ $stoppageItem->id }}"
                                                                        data-km="{{ $stoppageItem->km_post }}"
                                                                        @selected($stop->id == $stoppageItem->id)>
                                                                        {{ $stoppageItem->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="text-muted font-weight-bold d-block text-end"
                                                            style="font-size: 12px;">KM POST</label>
                                                        <div class="input-group">
                                                            <input type="number"
                                                                class="form-control km-post-input text-end"
                                                                value="{{ $stop->km_post }}" readonly tabindex="-1">
                                                            <button type="button"
                                                                class="input-group-text btn btn-danger remove-stoppage"><i
                                                                    class="las la-times"></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-12 mt-3">
                                <div class="form-group">
                                    <label class="text-muted font-weight-bold mb-2">@lang('Route Sequence Preview')</label>
                                    <div
                                        class="global-route-badges d-flex align-items-center flex-wrap gap-2 p-3 border rounded">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mt-4">
                                <div class="form-group">
                                    <label>@lang('Time')</label>
                                    <input type="text" class="form-control" name="time"
                                        value="{{ old('time', $route->time ?? '') }}" placeholder="e.g. 8 hrs 30 min"
                                        required>
                                    <small class="text-muted mt-1 d-block"><i class="las la-info-circle"></i>
                                        @lang('Keep space between value & unit')</small>
                                </div>
                            </div>

                            <div class="col-md-6 mt-4">
                                <div class="form-group">
                                    <label>@lang('Distance')</label>
                                    <input type="text" class="form-control" name="distance" id="distance_input"
                                        value="{{ old('distance', $route->distance ?? '') }}" readonly required>
                                    <small class="text-success mt-1 d-block"><i class="las la-check"></i>
                                        @lang('Auto-computed based on start and destination')</small>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary h-45 w-100 mt-4">@lang('Submit')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-back route="{{ route('admin.trip.route.index') }}" />
@endpush

@push('script')
    <script>
        "use strict";

        (function($) {
            // Count dynamically generated intermediate stoppages passed from the controller
            let itr = {{ isset($route) && isset($stoppages) ? count($stoppages) + 1 : 1 }};

            const generateStoppageHTML = (iterator) => {
                let options = `<option value="" selected>@lang('Select Stoppage')</option>`;
                @foreach ($allStoppages as $stoppageItem)
                    options +=
                        `<option value="{{ $stoppageItem->id }}" data-km="{{ $stoppageItem->km_post }}">{{ $stoppageItem->name }}</option>`;
                @endforeach

                return `
    <div class="col-md-12 stoppage-item mb-4">
        <div class="row align-items-center">
            <div class="col-md-10">
                <label class="text-muted font-weight-bold" style="font-size: 12px;">STOP</label>
                <div class="input-group">
                    <span class="input-group-text serial">${iterator}.</span>
                    <select class="select2-basic stoppage-select" name="stoppages[${iterator}]" required>
                        ${options}
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <label class="text-muted font-weight-bold d-block text-end" style="font-size: 12px;">KM POST</label>
                <div class="input-group">
                    <input type="number" class="form-control km-post-input text-end" readonly tabindex="-1">
                    <button type="button" class="input-group-text btn btn-danger remove-stoppage"><i class="las la-times"></i></button>
                </div>
            </div>
        </div>
    </div>`;
            };

            function updateRouteBadges() {
                let startText = $('#start_from').find(':selected').text().trim();
                let endText = $('#end_to').find(':selected').text().trim();

                startText = startText && $('#start_from').val() !== "" ? startText : 'ORIGIN';
                endText = endText && $('#end_to').val() !== "" ? endText : 'DESTINATION';

                // Start with the origin badge
                let badgesHtml = `<span class="badge bg-success px-3 py-2" style="font-size:13px;">${startText}</span>`;

                // Loop through every existing stoppage dropdown and append it
                $('.stoppage-select').each(function() {
                    let stopText = $(this).find(':selected').text().trim();
                    let displayTxt = (stopText && $(this).val() !== "") ? stopText : '...';

                    badgesHtml += `
            <i class="las la-long-arrow-alt-right text-muted fs-4"></i>
            <span class="badge bg-secondary px-3 py-2" style="font-size:13px;">${displayTxt}</span>
        `;
                });

                // End with the destination badge
                badgesHtml += `
        <i class="las la-long-arrow-alt-right text-muted fs-4"></i>
        <span class="badge bg-danger px-3 py-2" style="font-size:13px;">${endText}</span>
    `;

                // Inject the final string
                $('.global-route-badges').html(badgesHtml);
            }

            $('#has-stoppage').on('change', function() {
                if (this.checked) {
                    $('.stoppages-wrapper').removeClass('d-none');
                    if ($('.stoppages-row').children().length === 0) {
                        $('.stoppages-row').append(generateStoppageHTML(itr));
                        initializeSelect2();
                        updateRouteBadges();
                        itr++;
                    }
                } else {
                    $('.stoppages-wrapper').addClass('d-none');
                    $('.stoppages-row').empty();
                    itr = 1;
                }
            });

            $(document).on('click', '.add-stoppage-btn', function() {
                $('.stoppages-row').append(generateStoppageHTML(itr));
                initializeSelect2();
                updateRouteBadges();
                itr++;
            });

            $(document).on('click', '.remove-stoppage', function() {
                $(this).closest('.stoppage-item').remove();

                $('.stoppage-item').each(function(index, element) {
                    $(element).find('.serial').text((index + 1) + '.');
                    $(element).find('.stoppage-select').attr('name', `stoppages[${index + 1}]`);
                });
                itr = $('.stoppage-item').length + 1;
                updateRouteBadges()
            });

            $(document).on('change', '#start_from, #end_to', function() {
                updateRouteBadges();
                autoPopulateNameAndDistance();
            });

            $(document).on('change', '.stoppage-select', function() {
                let selectedOption = $(this).find(':selected');
                let km = selectedOption.data('km');

                // Auto-fill the KM Post input
                $(this).closest('.stoppage-item').find('.km-post-input').val(km || 0);

                // Trigger the global sequence to rebuild immediately
                updateRouteBadges();
            });


            function autoPopulateNameAndDistance() {
                let startVal = $('#start_from').val();
                let endVal = $('#end_to').val();

                if (startVal && endVal) {
                    let startText = $('#start_from').find(':selected').text().trim();
                    let endText = $('#end_to').find(':selected').text().trim();
                    let startKm = parseFloat($('#start_from').find(':selected').data('km') || 0);
                    let endKm = parseFloat($('#end_to').find(':selected').data('km') || 0);

                    let routeString = `${startText} → ${endText}`;
                    $('.auto-name-hint').text(`Auto: ${routeString}`);

                    if (!$('#route_name').val()) {
                        $('#route_name').val(`${startText} - ${endText}`);
                    }

                    let totalDistance = Math.abs(endKm - startKm);
                    $('#distance_input').val(`${totalDistance} km`);
                }
            }

            function initializeSelect2() {
                $.each($('.select2-basic'), function() {
                    if (!$(this).hasClass("select2-hidden-accessible")) {
                        $(this).wrap(`<div class="position-relative flex-grow-1"></div>`).select2({
                            dropdownParent: $(this).parent(),
                            width: '100%'
                        });
                    }
                });
            }

            initializeSelect2();
            updateRouteBadges();

        })(jQuery);
    </script>
@endpush

@push('style')
    <style>
        .stoppage-item {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
        }

        .stoppage-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .route-badges {
            user-select: none;
        }

        .badge {
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .input-group>.position-relative.flex-grow-1 {
            width: auto;
            flex: 1 1 auto;
        }

        .select2-container .select2-selection--single {
            height: 45px !important;
            display: flex;
            align-items: center;
            border-radius: 0;
            border-color: #ced4da;
        }

        .km-post-input {
            height: 45px;
            font-weight: bold;
            pointer-events: none;
        }
    </style>
@endpush
