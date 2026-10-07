@auth
    @if (config('services.pusher.key'))
        <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
        <script>
            (function() {
                'use strict';

                const pusher = new Pusher(@json(config('services.pusher.key')), {
                    cluster: @json(config('services.pusher.cluster', 'ap1')),
                    forceTLS: true
                });
                const channel = pusher.subscribe(@json(\App\Services\PassengerNotificationBroadcaster::channelFor((int) auth()->id())));

                channel.bind('notification-created', function(item) {
                    const count = document.getElementById('passengerNotificationCount');
                    if (count) {
                        count.textContent = String((parseInt(count.textContent || '0', 10) || 0) + 1);
                        count.classList.remove('d-none');
                    }

                    const list = document.getElementById('passengerNotificationDropdown');
                    if (list) {
                        const empty = list.querySelector('.passenger-notification-empty');
                        if (empty) empty.remove();

                        const link = document.createElement('a');
                        link.href = item.history_url;
                        link.className = 'passenger-notification-item is-unread';
                        link.dataset.readUrl = item.read_url;

                        const title = document.createElement('strong');
                        title.textContent = item.title;
                        const message = document.createElement('span');
                        message.textContent = item.message;
                        const time = document.createElement('small');
                        time.textContent = item.created_at_label;
                        link.append(title, message, time);
                        list.prepend(link);
                    }

                    if (typeof window.notify === 'function') {
                        window.notify('info', `${item.title}: ${item.message}`);
                    }

                    window.dispatchEvent(new CustomEvent('passenger-notification', { detail: item }));
                });

                document.addEventListener('click', function(event) {
                    const link = event.target.closest('.passenger-notification-item[data-read-url]');
                    if (!link) return;

                    event.preventDefault();
                    fetch(link.dataset.readUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'Accept': 'application/json'
                        }
                    }).finally(function() {
                        window.location.href = link.href;
                    });
                });
            })();
        </script>
    @endif
@endauth
