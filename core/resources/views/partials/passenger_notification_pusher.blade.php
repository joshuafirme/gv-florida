@auth
    @if (config('services.pusher.key'))
        <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    @endif

    <script>
        (function() {
            'use strict';

            const csrfToken = @json(csrf_token());
            const count = document.getElementById('passengerNotificationCount');
            const dropdown = document.querySelector('.passenger-notification');
            const header = document.querySelector('.header-bottom');
            const modalElement = document.getElementById('passengerNotificationModal');
            const modal = modalElement && window.bootstrap
                ? bootstrap.Modal.getOrCreateInstance(modalElement)
                : null;

            function post(url) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
            }

            function setUnreadCount(value) {
                if (!count) return;

                const nextValue = Math.max(0, Number(value) || 0);
                count.textContent = String(nextValue);
                count.classList.toggle('d-none', nextValue === 0);
            }

            function markItemRead(item) {
                if (!item?.classList.contains('is-unread')) return;

                item.classList.remove('is-unread');
                setUnreadCount((parseInt(count?.textContent || '0', 10) || 0) - 1);
            }

            function iconElement(tone, icon) {
                const wrapper = document.createElement('span');
                wrapper.className = `passenger-notification-icon is-${tone || 'success'}`;
                const symbol = document.createElement('i');
                symbol.className = `las ${icon || 'la-bell'}`;
                wrapper.append(symbol);
                return wrapper;
            }

            function createDropdownItem(item) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'passenger-notification-item is-unread';
                button.dataset.detailUrl = item.detail_url;
                button.dataset.readUrl = item.read_url;

                const copy = document.createElement('span');
                copy.className = 'passenger-notification-copy';
                const title = document.createElement('strong');
                title.textContent = item.title || 'Notification';
                const message = document.createElement('span');
                message.textContent = item.message || '';
                const time = document.createElement('small');
                time.textContent = item.created_at_label || 'Just now';
                copy.append(title, message, time);
                button.append(iconElement(item.tone, item.icon), copy);

                return button;
            }

            function detailValue(row) {
                const value = document.createElement('span');
                value.className = 'passenger-notification-detail-value';

                if (row.type === 'change') {
                    value.classList.add('passenger-notification-change');
                    const previous = document.createElement('del');
                    previous.textContent = row.from || '';
                    const arrow = document.createElement('i');
                    arrow.className = 'las la-arrow-right';
                    const current = document.createElement('strong');
                    current.textContent = row.value || '';
                    value.append(previous, arrow, current);
                    return value;
                }

                if (row.type === 'badge') {
                    const badge = document.createElement('span');
                    badge.className = `passenger-notification-status is-${row.tone || 'neutral'}`;
                    badge.textContent = row.value || '';
                    value.append(badge);
                    return value;
                }

                if (row.type === 'amount') value.classList.add('is-amount');
                value.textContent = row.value || '';
                return value;
            }

            function renderRows(container, rows) {
                if (!container) return;
                container.replaceChildren();

                (rows || []).forEach(function(row) {
                    const element = document.createElement('div');
                    element.className = 'passenger-notification-detail-row';
                    const label = document.createElement('span');
                    label.className = 'passenger-notification-detail-label';
                    label.textContent = row.label || '';
                    element.append(label, detailValue(row));
                    container.append(element);
                });
            }

            function renderModal(item) {
                const title = document.getElementById('passengerNotificationModalTitle');
                const date = document.getElementById('passengerNotificationModalDate');
                const message = document.getElementById('passengerNotificationModalMessage');
                const icon = document.getElementById('passengerNotificationModalIcon');
                const movementSection = document.getElementById('passengerNotificationMovementSection');
                const movementTitle = document.getElementById('passengerNotificationMovementTitle');
                const voucher = document.getElementById('passengerNotificationVoucher');

                title.textContent = item.title || 'Notification';
                date.textContent = item.created_at_label || '';
                message.textContent = item.message || '';
                icon.className = `passenger-notification-modal-icon is-${item.tone || 'success'}`;
                icon.querySelector('i').className = `las ${item.icon || 'la-bell'}`;
                movementTitle.textContent = item.movement_title || 'Update Details';
                movementSection.hidden = !item.movement?.length;
                renderRows(document.getElementById('passengerNotificationMovement'), item.movement);
                renderRows(document.getElementById('passengerNotificationDetails'), item.details);

                if (item.voucher_url) {
                    voucher.href = item.voucher_url;
                    voucher.hidden = false;
                } else {
                    voucher.removeAttribute('href');
                    voucher.hidden = true;
                }
            }

            async function openNotification(item) {
                const detailUrl = item.dataset.detailUrl;
                const readUrl = item.dataset.readUrl;
                if (!detailUrl) return;

                const toggle = dropdown?.querySelector('[data-bs-toggle="dropdown"]');
                if (toggle && window.bootstrap) {
                    bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
                }

                markItemRead(item);
                if (readUrl) post(readUrl).catch(() => {});

                renderModal({
                    title: 'Notification',
                    message: 'Loading notification details...',
                    details: [],
                    movement: [],
                    tone: 'success',
                    icon: 'la-bell'
                });
                modal?.show();

                try {
                    const response = await fetch(detailUrl, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!response.ok) throw new Error(`Notification request failed (${response.status})`);
                    renderModal(await response.json());
                } catch (error) {
                    renderModal({
                        title: 'Notification unavailable',
                        message: 'The notification details could not be loaded. Please try again.',
                        details: [],
                        movement: [],
                        tone: 'danger',
                        icon: 'la-exclamation-circle'
                    });
                    console.error(error);
                }
            }

            dropdown?.addEventListener('show.bs.dropdown', function() {
                header?.classList.add('is-notification-open');
            });
            dropdown?.addEventListener('hidden.bs.dropdown', function() {
                header?.classList.remove('is-notification-open');
            });
            modalElement?.addEventListener('show.bs.modal', function() {
                document.body.classList.add('passenger-notification-modal-active');
            });
            modalElement?.addEventListener('hidden.bs.modal', function() {
                document.body.classList.remove('passenger-notification-modal-active');
            });

            document.addEventListener('click', function(event) {
                const item = event.target.closest('[data-detail-url][data-read-url]');
                if (item) {
                    event.preventDefault();
                    openNotification(item);
                    return;
                }

                const readAll = event.target.closest('.js-passenger-notification-read-all[data-read-all-url]');
                if (!readAll) return;

                event.preventDefault();
                readAll.disabled = true;
                post(readAll.dataset.readAllUrl).then(function() {
                    document.querySelectorAll('.passenger-notification-item.is-unread, .notification-history-item.is-unread')
                        .forEach(element => element.classList.remove('is-unread'));
                    setUnreadCount(0);
                }).finally(function() {
                    readAll.disabled = false;
                });
            });

            window.PassengerNotificationUI = {
                createDropdownItem,
                openNotification,
                setUnreadCount
            };

            @if (config('services.pusher.key'))
                const pusher = new Pusher(@json(config('services.pusher.key')), {
                    cluster: @json(config('services.pusher.cluster', 'ap1')),
                    forceTLS: true
                });
                const channel = pusher.subscribe(@json(\App\Services\PassengerNotificationBroadcaster::channelFor((int) auth()->id())));

                channel.bind('notification-created', function(item) {
                    setUnreadCount((parseInt(count?.textContent || '0', 10) || 0) + 1);

                    const list = document.getElementById('passengerNotificationDropdown');
                    if (list) {
                        list.querySelector('.passenger-notification-empty')?.remove();
                        list.prepend(createDropdownItem(item));
                    }

                    if (typeof window.notify === 'function') {
                        window.notify('info', `${item.title}: ${item.message}`);
                    }

                    window.dispatchEvent(new CustomEvent('passenger-notification', { detail: item }));
                });
            @endif
        })();
    </script>
@endauth
