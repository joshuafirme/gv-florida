@extends('admin.layouts.app')

@section('panel')
    <div class="row mb-none-30">
        <div class="col-xl-12 col-lg-12 col-md-12 mb-30">
            <div class="card">
                <h5 class="card-header">@lang('Information of Route') </h5>
                <div class="card-body">
                    <form action="{{ route('admin.trip.route.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('Name')</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label> @lang('Start From')</label>
                                    <select name="start_from" class="select2" required>
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($stoppages as $item)
                                            <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label> @lang('End To')</label>
                                    <select name="end_to" class="select2" required>
                                        <option value="">@lang('Select an option')</option>
                                        @foreach ($stoppages as $item)
                                            <option value="{{ $item->id }}">{{ __($item->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox form-check-primary">
                                        <input type="checkbox" class="custom-control-input" id="has-stoppage">
                                        <label class="custom-control-label" for="has-stoppage">@lang('Has More Stoppage')</label>
                                    </div>
                                </div>
                            </div>
                            <div class="stoppages-wrapper col-md-12 d-none"></div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label> @lang('Time')</label>
                                    <input type="text" class="form-control" name="time" placeholder="@lang('Enter Approximate Time')" required>
                                    <small class="text--info text--small"><i class="fas fa-info-circle"></i> @lang('Keep space between value & unit')</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label> @lang('Distance')</label>
                                    <input type="text" class="form-control" name="distance" required>
                                    <small class="text--info text--small"><i class="fas fa-info-circle"></i> @lang('Keep space between value & unit')</small>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn--primary h-45 w-100">@lang('Submit')</button>
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
            $('#has-stoppage').on('click', function() {
                if (this.checked) {
                    var html = `<div class="row stoppages-row">
                                    <div class="col-xxl-3 col-md-6">
                                        <div class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-text serial">1</span>
                                                <select class="select2-basic" name="stoppages[1]" required>
                                                    <option value="" selected>@lang('Select Stoppage')</option>
                                                    @foreach ($stoppages as $stoppage)
                                                    <option value="{{ $stoppage->id }}">{{ $stoppage->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" class="input-group-text btn btn--danger remove-stoppage"><i class="las la-times"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn--success add-stoppage-btn mb-1"><i class="la la-plus"></i>@lang('Next Stoppage')</button> <br>
                                <span class="text--danger text--small"><i class="fas fa-exclamation-triangle"></i> @lang('Make sure that you are adding stoppages serially followed by the starting point')</span>`;
                    $('.stoppages-wrapper').prepend(html).removeClass('d-none');

                    initializeSelect2();
                } else {
                    itr = 2;
                    $('.stoppages-wrapper').html('').addClass('d-none');
                }
            });

            var itr = 2;
            $(document).on('click', '.add-stoppage-btn', function() {
                var option = `<div class="col-xxl-3 col-md-6">
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-text serial">${itr}</span>
                                    <select class="select2-basic" name="stoppages[${itr}]">
                                        <option value="" selected>@lang('Select Stoppage')</option>
                                        @foreach ($stoppages as $stoppage)
                                            <option value="{{ $stoppage->id }}">{{ $stoppage->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="input-group-text btn btn--danger remove-stoppage"><i class="las la-times me-0"></i></button>
                                </div>
                                </div>
                            </div>`;

                $('.stoppages-row').append(option);
                initializeSelect2();
                itr++;
            });

            $(document).on('click', '.remove-stoppage', function() {
                $(this).closest('.col-md-3').remove();
                var elements = $('.stoppages-row .col-md-3').find();

                $($('.stoppages-row .col-md-3')).each(function(index, element) {
                    $(element).find('.input-group .serial').text(index + 1);
                    $(element).find('.select2').attr('name', `stoppages[${index+1}]`);

                });
            });

            function initializeSelect2() {
                $.each($('.select2-basic'), function() {
                    $(this)
                        .wrap(`<div class="position-relative flex-grow-1"></div>`)
                        .select2({
                            dropdownParent: $(this).parent()
                        });
                });
            }
        })(jQuery)
    </script>
@endpush

@push('style')
    <style>
        .input-group .select2-container--default .select2-selection--single {
            border-radius: 0 !important;
        }
    </style>
@endpush
