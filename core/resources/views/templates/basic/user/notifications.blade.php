@extends($activeTemplate . 'layouts.master')

@section('content')
    @php
        $notificationGroups = $notifications->getCollection()->groupBy(function ($notification) {
            if ($notification->created_at->isToday()) {
                return 'Today';
            }

            if ($notification->created_at->isYesterday()) {
                return 'Yesterday';
            }

            return $notification->created_at->format('F j, Y');
        });
    @endphp

    <section class="notification-history-page">
        <div class="container">
            <a class="notification-history-back" href="{{ route('user.ticket.history') }}">
                <i class="las la-arrow-left"></i> @lang('My Tickets')
            </a>

            <div class="notification-history-heading">
                <div>
                    <h2>@lang('Notifications')</h2>
                    <p>@lang('Updates on your bookings and tickets.')</p>
                </div>
                <button type="button" class="notification-history-read-all js-passenger-notification-read-all"
                    data-read-all-url="{{ route('user.notifications.read-all') }}">
                    @lang('Mark all read')
                </button>
            </div>

            <div id="passengerNotificationHistoryGroups">
                @forelse ($notificationGroups as $label => $group)
                    <section class="notification-history-group" data-group-label="{{ $label }}">
                        <h3>{{ __($label) }}</h3>
                        <div class="notification-history-list">
                            @foreach ($group as $notification)
                                <button type="button"
                                    class="notification-history-item {{ $notification->is_read ? '' : 'is-unread' }}"
                                    data-detail-url="{{ route('user.notifications.show', $notification) }}"
                                    data-read-url="{{ route('user.notifications.read', $notification) }}">
                                    <span class="passenger-notification-icon is-{{ $notification->uiTone() }}">
                                        <i class="las {{ $notification->uiIcon() }}"></i>
                                    </span>
                                    <span class="notification-history-copy">
                                        <strong>{{ $notification->title }}</strong>
                                        <span>{{ $notification->message }}</span>
                                        <small>@lang('View details') <i class="las la-angle-right"></i></small>
                                    </span>
                                    <time datetime="{{ $notification->created_at->toIso8601String() }}">
                                        {{ diffForHumans($notification->created_at) }}
                                    </time>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="notification-history-empty" id="passengerNotificationHistoryEmpty">
                        <i class="las la-bell-slash"></i>
                        <strong>@lang('No notifications yet')</strong>
                        <span>@lang('Booking and ticket updates will appear here.')</span>
                    </div>
                @endforelse
            </div>

            @if ($notifications->hasPages())
                <div class="notification-history-pagination">{{ paginateLinks($notifications) }}</div>
            @endif
        </div>
    </section>
@endsection

@push('style')
    <style>
        .notification-history-page { background: #f5f7fa; min-height: 70vh; padding: 22px 0 60px; }
        .notification-history-back { align-items: center; color: #647083; display: inline-flex; font-size: 13px; gap: 5px; margin-bottom: 20px; }
        .notification-history-back:hover { color: var(--booking-primary, #df2a82); }
        .notification-history-heading { align-items: flex-end; display: flex; gap: 20px; justify-content: space-between; margin-bottom: 22px; }
        .notification-history-heading h2 { color: #111b2d; font-size: 27px; font-weight: 900; margin: 0; }
        .notification-history-heading p { color: #747e8d; font-size: 13px; margin: 4px 0 0; }
        .notification-history-read-all { background: transparent; border: 0; color: #168c91; font-size: 13px; font-weight: 800; padding: 6px 0; text-transform: none; }
        .notification-history-group + .notification-history-group { margin-top: 24px; }
        .notification-history-group > h3 { color: #929aa7; font-size: 11px; font-weight: 800; margin: 0 0 8px 3px; text-transform: uppercase; }
        .notification-history-list { background: #fff; border: 1px solid #e9edf2; border-radius: 8px; box-shadow: 0 2px 8px rgba(15, 23, 42, .04); overflow: hidden; }
        .notification-history-item { align-items: flex-start; background: #fff; border: 0; border-bottom: 1px solid #edf0f4; color: #293241; display: grid; gap: 13px; grid-template-columns: 40px minmax(0, 1fr) auto; padding: 16px; text-align: left; text-transform: none; width: 100%; }
        .notification-history-item:last-child { border-bottom: 0; }
        .notification-history-item:hover,
        .notification-history-item:focus { background: #f8fafc; outline: 0; }
        .notification-history-item.is-unread { background: #fff7fa; }
        .notification-history-copy { display: flex; flex-direction: column; min-width: 0; }
        .notification-history-copy strong { color: #172033; font-size: 13px; line-height: 1.35; }
        .notification-history-copy > span { color: #5f6978; font-size: 12px; line-height: 1.5; margin-top: 2px; }
        .notification-history-copy small { align-items: center; color: #168c91; display: inline-flex; font-size: 10px; font-weight: 800; gap: 3px; margin-top: 6px; }
        .notification-history-item time { color: #969eaa; font-size: 10px; padding-top: 2px; white-space: nowrap; }
        .notification-history-empty { align-items: center; background: #fff; border: 1px solid #e9edf2; border-radius: 8px; color: #818a98; display: flex; flex-direction: column; gap: 5px; padding: 54px 20px; text-align: center; }
        .notification-history-empty i { color: #a5adba; font-size: 34px; }
        .notification-history-empty strong { color: #3d4654; font-size: 14px; }
        .notification-history-empty span { font-size: 12px; }
        .notification-history-pagination { margin-top: 22px; }

        @media (max-width: 575px) {
            .notification-history-page { padding-top: 16px; }
            .notification-history-heading { align-items: flex-start; }
            .notification-history-heading h2 { font-size: 24px; }
            .notification-history-item { grid-template-columns: 38px minmax(0, 1fr); padding: 14px 12px; }
            .notification-history-item time { grid-column: 2; grid-row: 2; margin-top: -4px; padding: 0; }
        }
    </style>
@endpush

@push('script')
    <script>
        window.addEventListener('passenger-notification', function(event) {
            const item = event.detail || {};
            const groups = document.getElementById('passengerNotificationHistoryGroups');
            if (!groups) return;

            document.getElementById('passengerNotificationHistoryEmpty')?.remove();
            let today = groups.querySelector('[data-group-label="Today"]');
            if (!today) {
                today = document.createElement('section');
                today.className = 'notification-history-group';
                today.dataset.groupLabel = 'Today';
                const heading = document.createElement('h3');
                heading.textContent = 'Today';
                const list = document.createElement('div');
                list.className = 'notification-history-list';
                today.append(heading, list);
                groups.prepend(today);
            }

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'notification-history-item is-unread';
            button.dataset.detailUrl = item.detail_url;
            button.dataset.readUrl = item.read_url;

            const icon = document.createElement('span');
            icon.className = `passenger-notification-icon is-${item.tone || 'success'}`;
            const symbol = document.createElement('i');
            symbol.className = `las ${item.icon || 'la-bell'}`;
            icon.append(symbol);

            const copy = document.createElement('span');
            copy.className = 'notification-history-copy';
            const title = document.createElement('strong');
            title.textContent = item.title || 'Notification';
            const message = document.createElement('span');
            message.textContent = item.message || '';
            const details = document.createElement('small');
            details.textContent = 'View details';
            const detailsArrow = document.createElement('i');
            detailsArrow.className = 'las la-angle-right';
            details.append(detailsArrow);
            copy.append(title, message, details);

            const time = document.createElement('time');
            time.dateTime = item.created_at || '';
            time.textContent = item.created_at_label || 'Just now';
            button.append(icon, copy, time);
            today.querySelector('.notification-history-list').prepend(button);
        });
    </script>
@endpush
