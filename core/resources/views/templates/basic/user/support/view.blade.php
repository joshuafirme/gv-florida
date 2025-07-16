@extends($activeTemplate . $layout)
@section('content')

    <div class="container padding-top padding-bottom">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card cmn--card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:10px">
                        <h5 class="card-title m-0 text-white">
                            @php echo $myTicket->statusBadge @endphp
                            [@lang('Ticket')#{{ $myTicket->ticket }}] {{ $myTicket->subject }}
                        </h5>

                        @if ($myTicket->status != Status::TICKET_CLOSE && $myTicket->user)
                            <button class="btn btn--danger w-auto h-auto confirmationBtn" type="button" data-action="{{ route('ticket.close', $myTicket->id) }}" data-question="@lang('Are you sure to close this ticket?')"><i class="fa fa-lg fa-times-circle"></i>
                            </button>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordionExample">
                            <div id="collapseThree" class="collapse show" aria-labelledby="headingThree" data-parent="#accordionExample">
                                @if ($myTicket->status != 4)
                                    <form method="post" action="{{ route('ticket.reply', $myTicket->id) }}" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="replayTicket" value="1">
                                        <div class="row gy-3 justify-content-between">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <textarea name="message" class="form-control form--control shadow-none" id="inputMessage" placeholder="@lang('Your Reply')" rows="4" cols="10"></textarea>
                                                </div>
                                            </div>

                                            <div class="col-md-9">
                                                <button type="button" class="btn btn-dark btn-sm addAttachment ">
                                                    <i class="fas fa-plus"></i> @lang('Add Attachment') </button>
                                                <p class="my-2"><span class="text--info">@lang('Max 5 files can be uploaded | Maximum upload size is ' . convertToReadableSize(ini_get('upload_max_filesize')) . ' | Allowed File Extensions: .jpg, .jpeg, .png, .pdf, .doc, .docx')</span>
                                                </p>
                                                <div class="row g-3 fileUploadsContainer"></div>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="submit" class="btn btn--base my-2 w-100 h-40">
                                                    <i class="fa fa-reply"></i> @lang('Reply')
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row pt-3">
            <div class="col-md-12">
                <div class="card cmn--card">
                    <div class="card-body">
                        @foreach ($messages as $message)
                            @if ($message->admin_id == 0)
                                <div class="row border border-primary border-radius-3 my-sm-3 my-2 py-3 mx-0 mx-sm-2" style="background-color: #dbe9ff">
                                    <div class="col-md-3 border--right text-right">
                                        <h5 class="my-3">{{ $message->ticket->name }}</h5>
                                    </div>
                                    <div class="col-md-9 ps-2">
                                        <p class="text-muted fw-bold">
                                            @lang('Posted on')
                                            {{ $message->created_at->format('l, dS F Y @ H:i') }}</p>
                                        <p>{{ $message->message }}</p>
                                        @if ($message->attachments()->count() > 0)
                                            <div class="mt-2">
                                                @foreach ($message->attachments as $k => $image)
                                                    <a href="{{ route('ticket.download', encrypt($image->id)) }}" class="text--base"><i class="fa fa-file"></i>
                                                        @lang('Attachment') {{ ++$k }} </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="row border border-warning border-radius-3 my-sm-3 my-2 py-3 mx-0 mx-sm-2">
                                    <div class="col-md-3 border--right text-right">
                                        <h5 class="my-1">{{ $message->admin->name }}</h5>
                                        <p class="lead text-muted">@lang('Staff')</p>
                                    </div>
                                    <div class="col-md-9">
                                        <p class="text-muted fw-bold">
                                            @lang('Posted on')
                                            {{ $message->created_at->format('l, dS F Y @ H:i') }}</p>
                                        <p>{{ $message->message }}</p>
                                        @if ($message->attachments()->count() > 0)
                                            <div class="mt-2">
                                                @foreach ($message->attachments as $k => $image)
                                                    <a href="{{ route('ticket.download', encrypt($image->id)) }}" class="text--base"><i class="fa fa-file"></i>
                                                        @lang('Attachment') {{ ++$k }} </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection

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

        #confirmationModal .modal-header .close {
            padding: 0;
            background: transparent;
            color: #000;
        }
    </style>
@endpush

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
