@extends('admin.layouts.app')

@section('panel')
    @php
        $initialStops = old('route_stops', $routeStops);

        if (count($initialStops) < 2) {
            $initialStops = [
                ['counter_id' => '', 'km_post' => ''],
                ['counter_id' => '', 'km_post' => ''],
            ];
        }
    @endphp

    <div class="row">
        <div class="col-12">
            <form action="{{ $route ? route('admin.trip.route.update', $route->id) : route('admin.trip.route.store') }}"
                method="POST" id="routeForm">
                @csrf

                <div class="card route-editor-card">
                    <div class="card-body">
                        <div class="row gy-4 route-meta-fields">
                            <div class="col-lg-8">
                                <div class="form-group mb-0">
                                    <label for="route_name">@lang('Route Name') <span class="text-muted fw-normal">(@lang('optional'))</span></label>
                                    <input type="text" class="form-control" name="name" id="route_name"
                                        value="{{ old('name', $route->name ?? '') }}"
                                        placeholder="@lang('Generated from the origin and destination when left blank')">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-2">
                                <div class="form-group mb-0">
                                    <label for="route_time">@lang('Travel Time')</label>
                                    <input type="text" class="form-control" name="time" id="route_time"
                                        value="{{ old('time', $route->time ?? '') }}" placeholder="8 hrs 30 min" required>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-2">
                                <div class="form-group mb-0">
                                    <label for="distance_input">@lang('Distance')</label>
                                    <input type="text" class="form-control" id="distance_input"
                                        value="{{ old('distance', $route->distance ?? '0 km') }}" readonly>
                                </div>
                            </div>
                        </div>

                        @error('route_stops')
                            <div class="alert alert-danger mt-4 mb-0">{{ $message }}</div>
                        @enderror
                        @error('route_stops.*.counter_id')
                            <div class="alert alert-danger mt-4 mb-0">{{ $message }}</div>
                        @enderror
                        @error('route_stops.*.km_post')
                            <div class="alert alert-danger mt-4 mb-0">{{ $message }}</div>
                        @enderror

                        <section class="route-stops-section" aria-labelledby="route-stops-title">
                            <div class="route-stops-header">
                                <div>
                                    <h6 id="route-stops-title" class="mb-1">@lang('Route Stops')</h6>
                                    <p class="mb-0">@lang('Origin-to-destination sequence with route-specific KM Posts.')</p>
                                </div>
                                <button type="button" class="btn btn-outline--primary btn-sm add-stop-btn">
                                    <i class="las la-plus" aria-hidden="true"></i> @lang('Add Stop')
                                </button>
                            </div>

                            <div class="route-stops-labels" aria-hidden="true">
                                <span>@lang('Stop')</span>
                                <span>@lang('KM Post')</span>
                                <span></span>
                            </div>

                            <div id="routeStops" class="route-stops-list"></div>
                        </section>

                        <div class="route-sequence-preview">
                            <span class="route-preview-label">@lang('Route Sequence')</span>
                            <div id="routeSequence" class="route-preview-items"></div>
                        </div>

                        <button type="submit" class="btn btn--primary h-45 w-100 mt-4">
                            <i class="las la-save" aria-hidden="true"></i>
                            {{ $route ? __('Update Route') : __('Create Route') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-back route="{{ route('admin.trip.route.index') }}" />
@endpush

@push('script')
    <script>
        'use strict';

        (function($) {
            const locations = @json($locationOptions);
            const initialStops = @json(array_values($initialStops));
            const $list = $('#routeStops');
            let draggedUnit = null;

            function locationOptions(selectedId) {
                const fragment = document.createDocumentFragment();
                fragment.appendChild(new Option('@lang('Select a location')', '', false, !selectedId));

                locations.forEach(function(location) {
                    const suffix = location.active ? '' : ' (@lang('Inactive'))';
                    const option = new Option(location.name + suffix, location.id, false, location.id === String(selectedId || ''));
                    option.dataset.km = location.km_post ?? '';
                    fragment.appendChild(option);
                });

                return fragment;
            }

            function stopUnit(stop = {}) {
                const unit = document.createElement('div');
                unit.className = 'route-stop-unit';
                unit.innerHTML = `
                    <div class="route-stop-row">
                        <button type="button" class="route-drag-handle" draggable="true" title="@lang('Drag to reorder')" aria-label="@lang('Drag to reorder')">
                            <i class="las la-grip-vertical" aria-hidden="true"></i>
                        </button>
                        <span class="route-stop-number" aria-hidden="true"></span>
                        <div class="route-location-field">
                            <label class="mobile-field-label">@lang('Stop')</label>
                            <select class="form-control route-location-select" required></select>
                            <small class="route-stop-role"></small>
                        </div>
                        <div class="route-km-field">
                            <label class="mobile-field-label">@lang('KM Post')</label>
                            <input type="number" class="form-control route-km-input" min="0" max="999999.99" step="0.01" required>
                        </div>
                        <div class="route-stop-actions">
                            <button type="button" class="route-action move-up" title="@lang('Move up')" aria-label="@lang('Move up')"><i class="las la-angle-up"></i></button>
                            <button type="button" class="route-action move-down" title="@lang('Move down')" aria-label="@lang('Move down')"><i class="las la-angle-down"></i></button>
                            <button type="button" class="route-action remove-stop" title="@lang('Remove stop')" aria-label="@lang('Remove stop')"><i class="las la-times"></i></button>
                        </div>
                    </div>
                    <button type="button" class="insert-stop-btn" title="@lang('Insert a stop here')">
                        <span></span><i class="las la-plus" aria-hidden="true"></i><small>@lang('Insert here')</small><span></span>
                    </button>`;

                const select = unit.querySelector('.route-location-select');
                select.appendChild(locationOptions(stop.counter_id));
                unit.querySelector('.route-km-input').value = stop.km_post ?? '';
                unit.dataset.customKm = stop.km_post !== undefined && stop.km_post !== null && stop.km_post !== '' ? '1' : '0';

                return unit;
            }

            function initializeStopSelect(unit) {
                const $select = $(unit).find('.route-location-select');

                $select.select2({
                    width: '100%',
                    dropdownParent: $select.closest('.route-location-field'),
                    placeholder: '@lang('Select a location')',
                });
            }

            function addStop(stop = {}, position = null) {
                const unit = stopUnit(stop);
                const units = $list.children('.route-stop-unit');

                if (position === null || position >= units.length) {
                    $list.append(unit);
                } else {
                    units.eq(position).before(unit);
                }

                initializeStopSelect(unit);
                refreshEditor();
            }

            function refreshEditor() {
                const units = $list.children('.route-stop-unit');

                units.each(function(index) {
                    const $unit = $(this);
                    const isFirst = index === 0;
                    const isLast = index === units.length - 1;
                    const role = isFirst ? '@lang('Origin')' : (isLast ? '@lang('Destination')' : '@lang('Stop')');

                    $unit.toggleClass('is-origin', isFirst).toggleClass('is-destination', isLast);
                    $unit.find('.route-stop-number').text(index + 1);
                    $unit.find('.route-stop-role').text(role);
                    $unit.find('.route-location-select').attr('name', `route_stops[${index}][counter_id]`);
                    $unit.find('.route-km-input').attr('name', `route_stops[${index}][km_post]`);
                    $unit.find('.move-up').prop('disabled', isFirst);
                    $unit.find('.move-down').prop('disabled', isLast);
                    $unit.find('.remove-stop').prop('disabled', units.length <= 2);
                    $unit.find('.insert-stop-btn').toggle(!isLast);
                });

                updateSummary();
            }

            function updateSummary() {
                const selected = [];
                const kmPosts = [];

                $list.children('.route-stop-unit').each(function() {
                    const $unit = $(this);
                    const $selected = $unit.find('.route-location-select option:selected');
                    const name = $selected.val() ? $selected.text().replace(' (@lang('Inactive'))', '') : '@lang('Select location')';
                    selected.push(name);
                    kmPosts.push(parseFloat($unit.find('.route-km-input').val()));
                });

                $('#routeSequence').empty();
                selected.forEach(function(name, index) {
                    if (index > 0) {
                        $('#routeSequence').append('<i class="las la-long-arrow-alt-right" aria-hidden="true"></i>');
                    }

                    $('<span>', {
                        class: index === 0 ? 'origin' : (index === selected.length - 1 ? 'destination' : ''),
                        text: name,
                    }).appendTo('#routeSequence');
                });

                let distance = 0;
                for (let index = 1; index < kmPosts.length; index++) {
                    if (!Number.isNaN(kmPosts[index - 1]) && !Number.isNaN(kmPosts[index])) {
                        distance += Math.abs(kmPosts[index] - kmPosts[index - 1]);
                    }
                }

                $('#distance_input').val(`${Number.isInteger(distance) ? distance : distance.toFixed(2)} km`);
            }

            initialStops.forEach(function(stop) {
                addStop(stop);
            });

            $('.add-stop-btn').on('click', function() {
                const count = $list.children('.route-stop-unit').length;
                addStop({}, Math.max(1, count - 1));
            });

            $list.on('click', '.insert-stop-btn', function() {
                const position = $(this).closest('.route-stop-unit').index() + 1;
                addStop({}, position);
            });

            $list.on('click', '.remove-stop', function() {
                if ($list.children('.route-stop-unit').length <= 2) {
                    return;
                }

                const $unit = $(this).closest('.route-stop-unit');
                $unit.find('.route-location-select').select2('destroy');
                $unit.remove();
                refreshEditor();
            });

            $list.on('click', '.move-up, .move-down', function() {
                const $unit = $(this).closest('.route-stop-unit');

                if ($(this).hasClass('move-up')) {
                    $unit.prev('.route-stop-unit').before($unit);
                } else {
                    $unit.next('.route-stop-unit').after($unit);
                }

                refreshEditor();
            });

            $list.on('change', '.route-location-select', function() {
                const $unit = $(this).closest('.route-stop-unit');
                const kmPost = $(this).find(':selected').data('km');

                if ($unit.attr('data-custom-km') !== '1' || $unit.find('.route-km-input').val() === '') {
                    $unit.find('.route-km-input').val(kmPost ?? '');
                }

                updateSummary();
            });

            $list.on('input', '.route-km-input', function() {
                $(this).closest('.route-stop-unit').attr('data-custom-km', '1');
                updateSummary();
            });

            $list.on('dragstart', '.route-drag-handle', function(event) {
                draggedUnit = $(this).closest('.route-stop-unit').get(0);
                draggedUnit.classList.add('is-dragging');
                event.originalEvent.dataTransfer.effectAllowed = 'move';
            });

            $list.on('dragover', '.route-stop-unit', function(event) {
                event.preventDefault();

                if (!draggedUnit || draggedUnit === this) {
                    return;
                }

                const bounds = this.getBoundingClientRect();
                const insertAfter = event.originalEvent.clientY > bounds.top + bounds.height / 2;
                this.parentNode.insertBefore(draggedUnit, insertAfter ? this.nextSibling : this);
            });

            $list.on('dragend', '.route-drag-handle', function() {
                if (draggedUnit) {
                    draggedUnit.classList.remove('is-dragging');
                }
                draggedUnit = null;
                refreshEditor();
            });

            refreshEditor();
        })(jQuery);
    </script>
@endpush

@push('style')
    <style>
        .route-editor-card {
            border: 1px solid #e1e5eb;
            box-shadow: 0 4px 14px rgba(30, 40, 60, .05);
        }

        .route-meta-fields .form-control,
        .route-stop-row .form-control,
        .route-stop-row .select2-selection--single {
            min-height: 44px !important;
            border-color: #d8dce3 !important;
        }

        .route-stops-section {
            margin-top: 28px;
            border: 1px solid #dfe3e8;
            border-radius: 6px;
            padding: 22px 20px 12px;
        }

        .route-stops-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .route-stops-header h6 {
            color: #3c4251;
            font-size: 15px;
        }

        .route-stops-header p {
            color: #8a91a1;
            font-size: 12px;
        }

        .route-stops-labels {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 130px 112px;
            gap: 10px;
            margin-left: 56px;
            padding: 0 8px 8px;
            color: #9097a6;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .route-stops-labels span:nth-child(2) {
            text-align: center;
        }

        .route-stop-unit {
            position: relative;
        }

        .route-stop-row {
            display: grid;
            grid-template-columns: 26px 30px minmax(0, 1fr) 130px 112px;
            gap: 10px;
            align-items: center;
            min-height: 58px;
            transition: opacity .15s ease, background-color .15s ease;
        }

        .route-stop-unit.is-dragging .route-stop-row {
            opacity: .55;
            background: #f5f7fa;
        }

        .route-drag-handle,
        .route-action {
            display: inline-grid;
            place-items: center;
            border: 0;
            background: transparent;
            color: #9aa1ae;
            width: 28px;
            height: 32px;
            padding: 0;
        }

        .route-drag-handle {
            cursor: grab;
            font-size: 18px;
        }

        .route-drag-handle:active {
            cursor: grabbing;
        }

        .route-stop-number {
            display: inline-grid;
            place-items: center;
            width: 25px;
            height: 25px;
            border: 1px solid #d2d7df;
            border-radius: 50%;
            background: #eef0f3;
            color: #5c6370;
            font-size: 11px;
            font-weight: 700;
        }

        .route-stop-unit.is-origin .route-stop-number {
            border-color: #20bf6b;
            background: #20bf6b;
            color: #fff;
        }

        .route-stop-unit.is-destination .route-stop-number {
            border-color: #e83e8c;
            background: #e83e8c;
            color: #fff;
        }

        .route-location-field {
            position: relative;
            min-width: 0;
        }

        .route-stop-role {
            position: absolute;
            right: 38px;
            top: 50%;
            z-index: 3;
            transform: translateY(-50%);
            color: #9aa1ae;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0;
            pointer-events: none;
            text-transform: uppercase;
        }

        .route-location-field .select2-selection__rendered {
            padding-right: 100px !important;
            font-weight: 600;
        }

        .route-km-input {
            text-align: center;
            font-weight: 600;
        }

        .route-stop-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .route-action:hover:not(:disabled) {
            color: #e83e8c;
        }

        .route-action:disabled {
            opacity: .3;
        }

        .insert-stop-btn {
            display: grid;
            grid-template-columns: 1fr auto auto 1fr;
            align-items: center;
            gap: 5px;
            width: calc(100% - 66px);
            height: 20px;
            margin-left: 66px;
            border: 0;
            background: transparent;
            color: #bcc1cb;
            padding: 0;
        }

        .insert-stop-btn span {
            height: 1px;
            background: #e5e8ed;
        }

        .insert-stop-btn small {
            font-size: 10px;
        }

        .insert-stop-btn:hover {
            color: #e83e8c;
        }

        .insert-stop-btn:hover span {
            background: #e83e8c;
        }

        .route-sequence-preview {
            display: flex;
            align-items: flex-start;
            gap: 18px;
            margin-top: 18px;
            padding: 14px 16px;
            border: 1px solid #e2e6eb;
            border-radius: 6px;
            background: #f8f9fb;
        }

        .route-preview-label {
            flex: 0 0 auto;
            color: #7d8492;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .route-preview-items {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            color: #5e6572;
            font-size: 12px;
            font-weight: 600;
        }

        .route-preview-items .origin {
            color: #179b5a;
        }

        .route-preview-items .destination {
            color: #d63384;
        }

        .mobile-field-label {
            display: none;
        }

        @media (max-width: 767px) {
            .route-stops-section {
                padding: 16px 12px 8px;
            }

            .route-stops-header {
                align-items: stretch;
                flex-direction: column;
                gap: 12px;
            }

            .route-stops-header .btn {
                width: 100%;
            }

            .route-stops-labels {
                display: none;
            }

            .route-stop-row {
                grid-template-columns: 24px 26px minmax(0, 1fr) 84px;
                gap: 6px;
                padding: 10px 0;
            }

            .route-stop-actions {
                grid-column: 3 / 5;
                justify-content: flex-end;
                min-height: 28px;
            }

            .mobile-field-label {
                display: block;
                margin-bottom: 4px;
                color: #9299a7;
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
            }

            .route-stop-role {
                display: none;
            }

            .route-location-field .select2-selection__rendered {
                padding-right: 28px !important;
            }

            .insert-stop-btn {
                width: calc(100% - 56px);
                margin-left: 56px;
            }

            .route-sequence-preview {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
@endpush
