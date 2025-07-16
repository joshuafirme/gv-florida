@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="container">
        <div class="row justify-content-center padding-top padding-bottom">
            <div class="col-md-12">
                <div class="card cmn--card">
                    <div class="card-body">
                        <form action="{{ route('ticket.store') }}" method="post" enctype="multipart/form-data" class="disableSubmission">
                            @csrf
                            <div class="row gy-3">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="website">@lang('Subject')</label>
                                    <input type="text" name="subject" value="{{ old('subject') }}" class="form--control" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="priority">@lang('Priority')</label>
                                    <select name="priority" class="form--control" required>
                                        <option value="3">@lang('High')</option>
                                        <option value="2">@lang('Medium')</option>
                                        <option value="1">@lang('Low')</option>
                                    </select>
                                </div>
                                <div class="col-12 form-group">
                                    <label class="form-label" for="inputMessage">@lang('Message')</label>
                                    <textarea name="message" id="inputMessage" rows="6" class="form--control" required>{{ old('message') }}</textarea>
                                </div>

                                <div class="col-md-9">
                                    <button type="button" class="btn btn-dark btn-sm addAttachment "> <i class="fas fa-plus"></i> @lang('Add Attachment') </button>
                                    <p class="my-2"><span class="text--info">@lang('Max 5 files can be uploaded | Maximum upload size is ' . convertToReadableSize(ini_get('upload_max_filesize')) . ' | Allowed File Extensions: .jpg, .jpeg, .png, .pdf, .doc, .docx')</span></p>
                                    <div class="row g-3 fileUploadsContainer"></div>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn--base w-100 ny-2" type="submit"><i class="fa fa-paper-plane"></i> @lang('Submit')</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function($) {
            "use strict";
            var fileAdded = 0;
            $('.addAttachment').on('click', function() {
                fileAdded++;
                if (fileAdded == 5) {
                    $(this).attr('disabled', true)
                }
                $(".fileUploadsContainer").append(`
                    <div class="col-lg-4 col-md-12 removeFileInput">
                        <div class="input-group">
                            <input type="file" name="attachments[]" class="form--control" accept=".jpeg,.jpg,.png,.pdf,.doc,.docx" required>
                            <button type="button" class="input-group-text removeFile bg--danger border-0"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                `)
            });
            $(document).on('click', '.removeFile', function() {
                $('.addAttachment').removeAttr('disabled', true)
                fileAdded--;
                $(this).closest('.removeFileInput').remove();
            });
        })(jQuery);
    </script>
@endpush

@push('style')
    <style>
        .cmn--card button[type="submit"] {
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        @media (max-width: 374px) {
            .cmn--card button[type="submit"] {
                width: 100%;
            }
        }

        .fileUploadsContainer .form--control[type="file"] {
            padding: 0;
            position: relative;
        }

        .fileUploadsContainer .form--control[type="file"]::file-selector-button {
            border: none;
            padding: 4px 10px;
            border-radius: 0em;
            color: #fff;
            background-color: var(--main-color) !important;
            transition: 0.2s linear;
            line-height: 30px;
            position: relative;
            margin-left: 0px;
        }

        .fileUploadsContainer .input-group {
            flex-wrap: nowrap
        }

        .fileUploadsContainer .input-group-text {
            height: auto;
            color: #fff;
        }
    </style>
@endpush
