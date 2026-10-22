@php
    $passengerReminders = $bookingNotices['reminders'] ?? collect();
    $petTripCodes = $bookingNotices['tripCodes'] ?? collect();
    $reminderIcons = $bookingNotices['icons'] ?? [];
    $reminderVersion = $bookingNotices['version'] ?? 'default';
@endphp

@if ($passengerReminders->isNotEmpty() || $petTripCodes->isNotEmpty())
    <div class="modal fade passenger-reminders-modal" id="passengerRemindersModal" tabindex="-1"
        aria-labelledby="passengerRemindersTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <aside class="passenger-reminders-brand">
                    <div class="passenger-reminders-logo">
                        <img src="{{ siteLogo('dark') }}" alt="{{ __(gs('site_name')) }}">
                    </div>
                    <div class="passenger-reminders-brand-copy">
                        <span class="passenger-reminders-bus"><i class="las la-bus"></i></span>
                        <h3>@lang('Before we hit the road!')</h3>
                        <p>@lang('A few quick reminders to keep your trip smooth, safe, and comfortable.')</p>
                    </div>
                    <small>@lang('Safe travels')</small>
                </aside>

                <section class="passenger-reminders-content">
                    <header>
                        <h2 id="passengerRemindersTitle">@lang('Important Reminders')</h2>
                        <button type="button" class="passenger-reminders-close" data-bs-dismiss="modal"
                            aria-label="@lang('Close reminders')" title="@lang('Close reminders')">
                            <i class="las la-times" aria-hidden="true"></i>
                        </button>
                    </header>
                    <div class="passenger-reminders-scroll">
                        @foreach ($passengerReminders as $reminder)
                            @php
                                $paragraphs = preg_split('/\R\s*\R/', trim($reminder->content)) ?: [];
                            @endphp
                            <article class="passenger-reminder-item {{ $reminder->icon === 'discount' ? 'is-featured' : '' }}">
                                <span class="passenger-reminder-icon">
                                    <i class="{{ $reminderIcons[$reminder->icon] ?? 'las la-info-circle' }}"></i>
                                </span>
                                <div class="passenger-reminder-copy">
                                    @foreach ($paragraphs as $paragraph)
                                        <p>{!! nl2br(e(trim($paragraph))) !!}</p>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach

                        @if ($petTripCodes->isNotEmpty())
                            <div class="passenger-trip-codes">
                                <h3>@lang('Pet-Friendly Buses Trip Codes')</h3>
                                @foreach ($petTripCodes as $tripCode)
                                    <div class="passenger-trip-code">
                                        <span><i class="las la-bus"></i></span>
                                        <strong>{{ $tripCode->place }} - {{ $tripCode->code }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <footer>
                        <button type="button" class="btn passenger-reminders-acknowledge" id="passengerRemindersAcknowledge">
                            @lang("Got it - let's ride!") <i class="las la-bus"></i>
                        </button>
                    </footer>
                </section>
            </div>
        </div>
    </div>

    @push('style')
        <style>
            .passenger-reminders-modal .modal-dialog {
                max-width: 860px;
                padding: 12px;
            }

            .passenger-reminders-modal .modal-content {
                background: #f1f6fa;
                border: 0;
                border-radius: 8px;
                box-shadow: 0 24px 70px rgba(12, 28, 40, .3);
                display: grid;
                grid-template-columns: minmax(250px, 34%) minmax(0, 66%);
                max-height: min(720px, calc(100vh - 32px));
                overflow: hidden;
            }

            .passenger-reminders-brand {
                background: #11b8bd;
                color: #fff;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                min-height: 620px;
                padding: 34px 30px 26px;
            }

            .passenger-reminders-logo img {
                max-height: 38px;
                max-width: 145px;
                object-fit: contain;
                object-position: left center;
            }

            .passenger-reminders-brand-copy {
                margin: auto 0;
            }

            .passenger-reminders-bus {
                align-items: center;
                background: rgba(255, 255, 255, .18);
                border-radius: 8px;
                display: inline-flex;
                font-size: 34px;
                height: 66px;
                justify-content: center;
                margin-bottom: 20px;
                width: 66px;
            }

            .passenger-reminders-brand h3 {
                color: #fff;
                font-size: 27px;
                font-weight: 800;
                line-height: 1.12;
                margin-bottom: 14px;
                max-width: 210px;
            }

            .passenger-reminders-brand p {
                color: rgba(255, 255, 255, .9);
                font-size: 13px;
                line-height: 1.55;
                margin: 0;
            }

            .passenger-reminders-brand small {
                color: rgba(255, 255, 255, .82);
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
            }

            .passenger-reminders-content {
                display: flex;
                flex-direction: column;
                min-height: 0;
                overflow: hidden;
            }

            .passenger-reminders-content > header {
                align-items: center;
                background: #fff;
                border-bottom: 1px solid #e2e8ed;
                display: flex;
                gap: 16px;
                justify-content: space-between;
                padding: 23px 28px;
            }

            .passenger-reminders-content > header h2 {
                color: #333a48;
                font-size: 20px;
                font-weight: 800;
                margin: 0;
                text-transform: uppercase;
            }

            .passenger-reminders-close {
                align-items: center;
                background: transparent;
                border: 0;
                border-radius: 50%;
                color: #667080;
                display: inline-flex;
                flex: 0 0 38px;
                font-size: 22px;
                height: 38px;
                justify-content: center;
                padding: 0;
                transition: background-color .2s ease, color .2s ease;
                width: 38px;
            }

            .passenger-reminders-close:hover,
            .passenger-reminders-close:focus-visible {
                background: #e9f3f5;
                color: #0c969b;
                outline: 0;
            }

            .passenger-reminders-scroll {
                flex: 1;
                min-height: 0;
                overflow-y: auto;
                overscroll-behavior: contain;
                padding: 22px 28px 16px;
                -webkit-overflow-scrolling: touch;
            }

            .passenger-reminder-item {
                display: grid;
                gap: 14px;
                grid-template-columns: 38px minmax(0, 1fr);
                margin-bottom: 20px;
            }

            .passenger-reminder-icon,
            .passenger-trip-code > span {
                align-items: center;
                background: #fff;
                border-radius: 50%;
                color: #10aeb5;
                display: inline-flex;
                flex: 0 0 auto;
                font-size: 18px;
                height: 38px;
                justify-content: center;
                width: 38px;
            }

            .passenger-reminder-copy {
                color: #536071;
                font-size: 13px;
                line-height: 1.55;
                min-width: 0;
            }

            .passenger-reminder-copy p {
                margin: 0 0 12px;
            }

            .passenger-reminder-copy p:last-child {
                margin-bottom: 0;
            }

            .passenger-reminder-item:not(.is-featured) .passenger-reminder-copy p:first-child {
                color: #424b59;
                font-weight: 700;
            }

            .passenger-reminder-item.is-featured .passenger-reminder-copy {
                color: #169ca4;
                font-weight: 700;
            }

            .passenger-trip-codes {
                margin-left: 52px;
                padding-top: 2px;
            }

            .passenger-trip-codes h3 {
                color: #46505e;
                font-size: 12px;
                font-weight: 800;
                margin-bottom: 10px;
                text-transform: uppercase;
            }

            .passenger-trip-code {
                align-items: center;
                color: #5a6675;
                display: flex;
                font-size: 13px;
                gap: 11px;
                margin-bottom: 8px;
            }

            .passenger-trip-code > span {
                font-size: 15px;
                height: 34px;
                width: 34px;
            }

            .passenger-reminders-content > footer {
                background: #fff;
                border-top: 1px solid #e2e8ed;
                padding: 14px 28px;
            }

            .passenger-reminders-acknowledge {
                background: #12b5bb;
                border: 0;
                border-radius: 8px;
                color: #fff;
                font-size: 14px;
                font-weight: 700;
                min-height: 48px;
                width: 100%;
            }

            .passenger-reminders-acknowledge:hover,
            .passenger-reminders-acknowledge:focus {
                background: #0c969b;
                color: #fff;
            }

            @media (max-width: 767.98px) {
                .passenger-reminders-modal .modal-dialog {
                    align-items: center;
                    display: flex;
                    height: 100dvh;
                    margin: 0 auto;
                    max-width: 560px;
                    padding: 10px;
                }

                .passenger-reminders-modal .modal-content {
                    grid-template-columns: 1fr;
                    grid-template-rows: auto minmax(0, 1fr);
                    height: calc(100dvh - 20px);
                    max-height: calc(100dvh - 20px);
                }

                .passenger-reminders-brand {
                    align-items: center;
                    display: grid;
                    gap: 14px;
                    grid-template-columns: auto minmax(0, 1fr);
                    min-height: auto;
                    padding: 18px;
                }

                .passenger-reminders-logo {
                    grid-column: 1 / -1;
                }

                .passenger-reminders-brand-copy {
                    display: contents;
                }

                .passenger-reminders-bus {
                    height: 52px;
                    margin: 0;
                    width: 52px;
                }

                .passenger-reminders-brand h3 {
                    font-size: 20px;
                    margin: 0;
                    max-width: none;
                }

                .passenger-reminders-brand p,
                .passenger-reminders-brand small {
                    display: none;
                }

                .passenger-reminders-content > header,
                .passenger-reminders-scroll,
                .passenger-reminders-content > footer {
                    padding-left: 18px;
                    padding-right: 18px;
                }

                .passenger-reminders-content > header {
                    padding-bottom: 16px;
                    padding-top: 16px;
                }

                .passenger-reminders-content > header h2 {
                    font-size: 17px;
                }

                .passenger-reminders-close {
                    flex-basis: 40px;
                    height: 40px;
                    width: 40px;
                }

                .passenger-reminders-scroll {
                    padding-top: 17px;
                }

                .passenger-trip-codes {
                    margin-left: 0;
                }
            }
        </style>
    @endpush

    @push('script')
        <script>
            (function() {
                'use strict';

                const modalElement = document.getElementById('passengerRemindersModal');
                const acknowledgeButton = document.getElementById('passengerRemindersAcknowledge');
                const legacyStorageKey = 'gvf-passenger-reminders-seen';
                const storageKey = legacyStorageKey + ':' + @json($reminderVersion);
                let pendingForm = null;

                if (!modalElement || !acknowledgeButton) return;

                const reminderModal = new bootstrap.Modal(modalElement, {
                    backdrop: true,
                    keyboard: true
                });

                try {
                    sessionStorage.removeItem(legacyStorageKey);
                } catch (error) {
                    // Browser storage is optional; the reminder can still be displayed.
                }

                function remindersSeen() {
                    try {
                        return sessionStorage.getItem(storageKey) === '1';
                    } catch (error) {
                        return false;
                    }
                }

                function markRemindersSeen() {
                    try {
                        sessionStorage.setItem(storageKey, '1');
                    } catch (error) {
                        // Continue the booking when browser storage is unavailable.
                    }
                }

                document.querySelectorAll('.js-passenger-reminder-form').forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (remindersSeen()) return;

                        event.preventDefault();
                        event.stopImmediatePropagation();
                        pendingForm = form;
                        reminderModal.show();
                    }, true);
                });

                acknowledgeButton.addEventListener('click', function() {
                    markRemindersSeen();
                    reminderModal.hide();

                    if (pendingForm) {
                        pendingForm.submit();
                    }
                });

                modalElement.addEventListener('hidden.bs.modal', function() {
                    pendingForm = null;
                });
            })();
        </script>
    @endpush
@endif
