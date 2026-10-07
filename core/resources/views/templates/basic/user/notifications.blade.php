@extends($activeTemplate . 'layouts.master')

@section('content')
    <section class="dashboard-section padding-top padding-bottom">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <h4 class="mb-0">@lang('Notification History')</h4>
                @if ($notifications->where('is_read', false)->isNotEmpty())
                    <form method="POST" action="{{ route('user.notifications.read-all') }}">
                        @csrf
                        <button class="btn btn-sm btn--base" type="submit">
                            <i class="las la-check-double"></i> @lang('Mark all as read')
                        </button>
                    </form>
                @endif
            </div>

            <div class="card notification-history-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 notification-history-table">
                            <thead>
                                <tr>
                                    <th>@lang('Date & Time')</th>
                                    <th>@lang('Notification')</th>
                                    <th>@lang('Channel')</th>
                                    <th>@lang('Sent By/System')</th>
                                    <th>@lang('Status')</th>
                                </tr>
                            </thead>
                            <tbody id="passengerNotificationHistoryRows">
                                @forelse ($notifications as $notification)
                                    <tr class="{{ $notification->is_read ? '' : 'notification-history-unread' }}">
                                        <td data-label="Date & Time">{{ showDateTime($notification->created_at) }}</td>
                                        <td data-label="Notification">
                                            <strong>{{ $notification->title }}</strong>
                                            <div class="text-muted">{{ $notification->message }}</div>
                                        </td>
                                        <td data-label="Channel">{{ $notification->channel }}</td>
                                        <td data-label="Sent By/System">{{ $notification->sent_by }}</td>
                                        <td data-label="Status">
                                            <span class="badge badge--success">{{ $notification->status }}</span>
                                            @unless ($notification->is_read)
                                                <form method="POST" action="{{ route('user.notifications.read', $notification) }}" class="mt-2">
                                                    @csrf
                                                    <button class="btn btn-link btn-sm p-0" type="submit">@lang('Mark read')</button>
                                                </form>
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="passengerNotificationHistoryEmpty">
                                        <td colspan="5" class="text-center text-muted py-5">@lang('No notifications yet.')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($notifications->hasPages())
                    <div class="card-footer py-3">{{ paginateLinks($notifications) }}</div>
                @endif
            </div>
        </div>
    </section>
@endsection

@push('style')
    <style>
        .notification-history-card { border: 0; border-radius: 12px; box-shadow: 0 8px 28px rgba(18, 38, 63, .08); overflow: hidden; }
        .notification-history-table th { background: #f7f8fb; color: #596174; font-size: 12px; letter-spacing: .03em; text-transform: uppercase; white-space: nowrap; }
        .notification-history-table td { font-size: 13px; vertical-align: middle; }
        .notification-history-table td:nth-child(2) { min-width: 360px; }
        .notification-history-unread { background: color-mix(in srgb, var(--booking-primary) 6%, #fff); }
    </style>
@endpush

@push('script')
    <script>
        window.addEventListener('passenger-notification', function(event) {
            const item = event.detail || {};
            const escapeHtml = (value) => $('<div>').text(value || '').html();
            $('#passengerNotificationHistoryEmpty').remove();
            $('#passengerNotificationHistoryRows').prepend(`
                <tr class="notification-history-unread">
                    <td data-label="Date & Time">${escapeHtml(item.created_at_label)}</td>
                    <td data-label="Notification"><strong>${escapeHtml(item.title)}</strong><div class="text-muted">${escapeHtml(item.message)}</div></td>
                    <td data-label="Channel">${escapeHtml(item.channel)}</td>
                    <td data-label="Sent By/System">${escapeHtml(item.sent_by)}</td>
                    <td data-label="Status"><span class="badge badge--success">${escapeHtml(item.status)}</span></td>
                </tr>
            `);
        });
    </script>
@endpush
