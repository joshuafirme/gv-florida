<div class="modal fade" id="notifyPassengerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="notifyPassengerForm" method="POST">
            @csrf
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">@lang('Notify Passenger')</h5>
                    <small class="text-muted">PNR <span id="notifyPassengerPnr">-</span></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-0">
                    <label for="notifyPassengerEvent">@lang('Predefined notification')</label>
                    <select class="form-control" id="notifyPassengerEvent" name="event_type" required>
                        <option value="">@lang('Select a notification')</option>
                        @foreach (\App\Services\BookingNotificationService::manualTemplates() as $key => [$label, $message])
                            <option value="{{ $key }}" data-title="{{ $label }}" data-preview="{{ $message }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted mt-2">
                        @lang('The notification automatically includes the latest passenger, trip, route, seat, and PNR details.')
                    </small>

                    <div id="notifyPassengerPreview" class="notify-passenger-preview d-none" aria-live="polite">
                        <div class="notify-passenger-preview__label">@lang('Message preview')</div>
                        <strong id="notifyPassengerPreviewTitle"></strong>
                        <p id="notifyPassengerPreviewMessage" class="mb-0"></p>
                        <small>
                            @lang('The latest passenger name, trip date, departure time, origin, destination, seat number, and PNR will be appended automatically.')
                        </small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline--secondary" data-bs-dismiss="modal">@lang('Close')</button>
                <button type="submit" class="btn btn--primary" id="notifyPassengerSubmit">
                    <i class="las la-paper-plane"></i> @lang('Send notification')
                </button>
            </div>
        </form>
    </div>
</div>

@push('style')
    <style>
        .notify-passenger-preview { background: #f7f9fc; border: 1px solid #e1e6ee; border-radius: 9px; margin-top: 16px; padding: 14px 16px; }
        .notify-passenger-preview__label { color: #8a93a3; font-size: 10px; font-weight: 700; letter-spacing: .06em; margin-bottom: 7px; text-transform: uppercase; }
        .notify-passenger-preview strong { color: #273142; display: block; font-size: 14px; margin-bottom: 4px; }
        .notify-passenger-preview p { color: #505969; font-size: 13px; }
        .notify-passenger-preview small { color: #8a93a3; display: block; font-size: 11px; line-height: 1.45; margin-top: 9px; }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';

            const element = document.getElementById('notifyPassengerModal');
            if (!element) return;

            const modal = new bootstrap.Modal(element);
            const form = $('#notifyPassengerForm');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                || form.find('input[name="_token"]').val();

            function updatePreview() {
                const option = $('#notifyPassengerEvent option:selected');
                const preview = $('#notifyPassengerPreview');
                const message = option.data('preview');

                if (!message) {
                    preview.addClass('d-none');
                    $('#notifyPassengerPreviewTitle, #notifyPassengerPreviewMessage').text('');
                    return;
                }

                $('#notifyPassengerPreviewTitle').text(option.data('title') || option.text().trim());
                $('#notifyPassengerPreviewMessage').text(message);
                preview.removeClass('d-none');
            }

            $('#notifyPassengerEvent').on('change', updatePreview);

            $(document).on('click', '.notify-passenger-btn', function(event) {
                event.preventDefault();
                form.attr('action', $(this).data('notify-url') || $(this).attr('href'));
                form[0].reset();
                updatePreview();
                $('#notifyPassengerPnr').text($(this).data('pnr') || '-');
                modal.show();
            });

            form.on('submit', function(event) {
                event.preventDefault();
                const button = $('#notifyPassengerSubmit');
                button.prop('disabled', true);

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    data: {
                        _token: csrfToken,
                        event_type: $('#notifyPassengerEvent').val()
                    }
                }).done(function(response) {
                    modal.hide();
                    notify('success', response.message);
                }).fail(function(xhr) {
                    const message = xhr.status === 419
                        ? 'Your admin session token changed. Refresh the page and try again.'
                        : xhr.responseJSON?.message
                        || Object.values(xhr.responseJSON?.errors || {}).flat()[0]
                        || 'Unable to send the passenger notification.';
                    notify(xhr.status === 409 ? 'info' : 'error', message);
                }).always(function() {
                    button.prop('disabled', false);
                });
            });
        })(jQuery);
    </script>
@endpush
