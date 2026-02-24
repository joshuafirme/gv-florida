@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-md-12 mb-30">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.settings.reservation-slip.udpate') }}" class="disableSubmission" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            {{-- <div class="col-md-12">
                                <div class="form-group">
                                    <label>{{ __(keyToTitle($k)) }}</label>
                                    <textarea rows="10" class="form-control" name="{{ $k }}" required>{{ old($k, @$data->data_values->$k) }}</textarea>
                                </div>
                            </div> --}}
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Terms & Condition Contents</label>
                                    <textarea rows="10" class="form-control nicEdit" name="terms_and_conditions">{{ isset($data->terms_and_conditions) ? $data->terms_and_conditions : '' }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit"
                                class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection



@push('style-lib')
    <link href="{{ asset('assets/admin/css/fontawesome-iconpicker.min.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/fontawesome-iconpicker.js') }}"></script>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";
            $('.iconPicker').iconpicker().on('iconpickerSelected', function(e) {
                $(this).closest('.form-group').find('.iconpicker-input').val(
                    `<i class="${e.iconpickerValue}"></i>`);
            });

            @if (@$section->element->slug)
                $('.buildSlug').on('click', function() {
                    let slugKey = '{{ @$section->element->slug }}';
                    let closestForm = $(this).closest('form');
                    let title = closestForm.find(`[name=${slugKey}]`).val();
                    closestForm.find('[name=slug]').val(title);
                    closestForm.find('[name=slug]').trigger('input');
                });



                $('[name=slug]').on('input', function() {
                    let closestForm = $(this).closest('form');
                    closestForm.find('[type=submit]').addClass('disabled')
                    let slug = $(this).val();
                    slug = slug.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
                    $(this).val(slug);
                    if (slug) {
                        closestForm.find('.slug-verification').removeClass('d-none');
                        closestForm.find('.slug-verification').html(`
                            <small class="text--info"><i class="las la-spinner la-spin"></i> @lang('Checking')</small>
                        `);
                        $.get("{{ route('admin.frontend.sections.element.slug.check', [$key, @$data->id]) }}", {
                            slug: slug
                        }, function(response) {
                            if (!response.exists) {
                                closestForm.find('.slug-verification').html(`
                                    <small class="text--success"><i class="las la-check"></i> @lang('Available')</small>
                                `);
                                closestForm.find('[type=submit]').removeClass('disabled')
                            }
                            if (response.exists) {
                                closestForm.find('.slug-verification').html(`
                                    <small class="text--danger"><i class="las la-times"></i> @lang('Slug already exists')</small>
                                `);
                            }
                        });
                    } else {
                        closestForm.find('.slug-verification').addClass('d-none');
                    }
                })
            @endif
        })(jQuery);
    </script>
@endpush
