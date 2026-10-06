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
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted mt-2">
                        @lang('The notification automatically includes the latest passenger, trip, route, seat, and PNR details.')
                    </small>
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

@push('script')
    <script>
        (function($) {
            'use strict';

            const element = document.getElementById('notifyPassengerModal');
            if (!element) return;

            const modal = new bootstrap.Modal(element);
            const form = $('#notifyPassengerForm');

            $(document).on('click', '.notify-passenger-btn', function(event) {
                event.preventDefault();
                form.attr('action', $(this).data('notify-url') || $(this).attr('href'));
                form[0].reset();
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
                    data: form.serialize()
                }).done(function(response) {
                    modal.hide();
                    notify('success', response.message);
                }).fail(function(xhr) {
                    const message = xhr.responseJSON?.message
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
