<div id="channelAccessModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content channel-access-modal">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">@lang('Channel Access')</h5>
                    <p class="channel-access-subtitle mb-0" id="channelAccessTripSummary"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('Close')"></button>
            </div>

            <form id="channelAccessForm">
                @csrf
                <div class="modal-body">
                    <div id="channelAccessLoading" class="channel-access-loading">
                        <i class="las la-spinner la-spin"></i> @lang('Loading channel settings...')
                    </div>

                    <div id="channelAccessContent" class="d-none">
                        <div class="channel-access-tabs" role="tablist" aria-label="@lang('Channel access views')">
                            <button type="button" class="is-active" data-channel-tab="configure" role="tab"
                                aria-selected="true">@lang('Configure Access')</button>
                            <button type="button" data-channel-tab="blocks" role="tab" aria-selected="false">
                                @lang('Active Channel Blocks')
                                <span id="channelBlockTabCount">0</span>
                            </button>
                        </div>

                        <div data-channel-pane="configure">
                        <section class="channel-access-section">
                            <label class="channel-access-label">@lang('Apply To')</label>
                            <div class="channel-date-modes" role="radiogroup" aria-label="@lang('Date selection mode')">
                                <label>
                                    <input type="radio" name="selection_mode" value="single" checked>
                                    <span>@lang('Specific Date')</span>
                                </label>
                                <label>
                                    <input type="radio" name="selection_mode" value="multiple">
                                    <span>@lang('Multiple Dates')</span>
                                </label>
                                <label>
                                    <input type="radio" name="selection_mode" value="range">
                                    <span>@lang('Date Range')</span>
                                </label>
                            </div>

                            <div class="channel-date-panel" data-date-panel="single">
                                <label for="channelSingleDate">@lang('Journey Date')</label>
                                <input id="channelSingleDate" type="date" name="single_date" class="form-control">
                            </div>

                            <div class="channel-date-panel d-none" data-date-panel="multiple">
                                <label for="channelMultipleDate">@lang('Add Journey Dates')</label>
                                <div class="channel-multiple-date-input">
                                    <input id="channelMultipleDate" type="date" class="form-control">
                                    <button type="button" class="btn btn-outline--primary" id="addChannelDate"
                                        title="@lang('Add date')" aria-label="@lang('Add date')">
                                        <i class="las la-plus"></i>
                                    </button>
                                </div>
                                <div id="channelSelectedDates" class="channel-selected-dates"></div>
                            </div>

                            <div class="channel-date-panel channel-range-fields d-none" data-date-panel="range">
                                <div>
                                    <label for="channelDateFrom">@lang('Effective Start Date')</label>
                                    <input id="channelDateFrom" type="date" name="date_from" class="form-control">
                                </div>
                                <div>
                                    <label for="channelDateTo">@lang('Effective End Date') <span>@lang('(optional)')</span></label>
                                    <input id="channelDateTo" type="date" name="date_to" class="form-control">
                                </div>
                            </div>
                        </section>

                        <section class="channel-access-section">
                            <label class="channel-access-label">@lang('Channel Availability')</label>
                            <div class="channel-state-grid">
                                @foreach ([['key' => 'online', 'label' => 'Online Booking', 'icon' => 'la-globe'], ['key' => 'kiosk', 'label' => 'Kiosk Booking', 'icon' => 'la-desktop']] as $channel)
                                    <div class="channel-state-card" data-state-card="{{ $channel['key'] }}">
                                        <div class="channel-state-card__head">
                                            <i class="las {{ $channel['icon'] }}"></i>
                                            <div>
                                                <strong>{{ __($channel['label']) }}</strong>
                                                <small data-channel-default="{{ $channel['key'] }}"></small>
                                            </div>
                                        </div>
                                        <div class="channel-state-options">
                                            <label>
                                                <input type="radio" name="{{ $channel['key'] }}_state" value="" checked>
                                                <span>@lang('No Change')</span>
                                            </label>
                                            <label>
                                                <input type="radio" name="{{ $channel['key'] }}_state" value="enabled">
                                                <span><i class="las la-check"></i> @lang('Available')</span>
                                            </label>
                                            <label>
                                                <input type="radio" name="{{ $channel['key'] }}_state" value="disabled">
                                                <span><i class="las la-ban"></i> @lang('Blocked')</span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <section class="channel-access-section">
                            <label class="channel-access-label" for="channelAccessReason">@lang('Reason') <span>@lang('(optional)')</span></label>
                            <div class="channel-reason-options">
                                @foreach (['Online maintenance', 'Kiosk-only promo', 'Counter sales only', 'Fully booked online', 'Special / chartered trip', 'System maintenance'] as $reason)
                                    <button type="button" data-channel-reason="{{ $reason }}">{{ __($reason) }}</button>
                                @endforeach
                            </div>
                            <textarea id="channelAccessReason" name="reason" class="form-control" rows="2"
                                placeholder="@lang('Pick a reason above or type your own...')"></textarea>
                        </section>

                        <section class="channel-access-section">
                            <label class="channel-access-label" for="channelAuthorizationCode">
                                <i class="las la-key"></i> @lang('Authorization Code')
                            </label>
                            <div class="channel-auth-input">
                                <input id="channelAuthorizationCode" type="password" name="authorization_code"
                                    class="form-control" maxlength="100" autocomplete="new-password"
                                    placeholder="@lang('Supervisor code - required to update channel access')">
                                <button type="button" id="toggleChannelAuthorization" title="@lang('Show or hide authorization code')"
                                    aria-label="@lang('Show or hide authorization code')">
                                    <i class="las la-eye"></i>
                                </button>
                            </div>
                            <small>@lang('The authorizing personnel and every dated change are recorded in the Audit Trail.')</small>
                            <div id="channelAuthorizationStatus" class="channel-authorization-status is-idle" aria-live="polite">
                                <i class="las la-key"></i>
                                <span>@lang('Enter a valid authorization code to enable saving.')</span>
                            </div>
                        </section>
                        </div>

                        <div class="d-none" data-channel-pane="blocks">
                        <section class="channel-access-section channel-calendar-section pt-0">
                            <div class="channel-calendar-head">
                                <div>
                                    <label class="channel-access-label mb-0">@lang('Active Dated Settings')</label>
                                    <small>@lang('Red marks blocked dates; green marks explicitly available dates.')</small>
                                </div>
                                <div class="channel-calendar-nav">
                                    <button type="button" id="channelCalendarPrev" title="@lang('Previous month')"><i class="las la-angle-left"></i></button>
                                    <strong id="channelCalendarTitle"></strong>
                                    <button type="button" id="channelCalendarNext" title="@lang('Next month')"><i class="las la-angle-right"></i></button>
                                </div>
                            </div>
                            <div class="channel-calendar" id="channelAccessCalendar"></div>
                            <div class="channel-calendar-legend">
                                <span><i class="is-blocked"></i> @lang('Blocked')</span>
                                <span><i class="is-enabled"></i> @lang('Available')</span>
                                <span class="channel-calendar-legend__hint">@lang('Circle: Online') &nbsp; @lang('Square: Kiosk')</span>
                            </div>
                            <div id="channelAccessRules" class="channel-access-rules"></div>
                        </section>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('Close')</button>
                    <button type="submit" class="btn btn--primary" id="saveChannelAccess">
                        <i class="las la-save"></i> @lang('Save Channel Access')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('style')
    <style>
        .channel-access-modal {
            max-height: calc(100vh - 32px);
            max-height: calc(100dvh - 32px);
        }

        .channel-access-modal .modal-header {
            align-items: flex-start;
        }

        .channel-access-modal form {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
        }

        .channel-access-modal .modal-body {
            overflow-y: auto;
            padding: 20px 24px;
        }

        .channel-access-modal .modal-footer {
            background: #fff;
            flex-shrink: 0;
        }

        .channel-access-subtitle {
            color: #697180;
            font-size: 12px;
            margin-top: 5px;
        }

        .channel-access-loading {
            color: #777f8c;
            padding: 70px 20px;
            text-align: center;
        }

        .channel-access-loading i {
            color: #df2a82;
            font-size: 22px;
            margin-right: 5px;
        }

        .channel-access-tabs {
            border-bottom: 1px solid #e3e6eb;
            display: flex;
            gap: 22px;
            margin: -4px 0 18px;
        }

        .channel-access-tabs button {
            background: transparent;
            border: 0;
            border-bottom: 2px solid transparent;
            color: #747c88;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: -1px;
            padding: 10px 1px;
        }

        .channel-access-tabs button.is-active {
            border-bottom-color: #df2a82;
            color: #bd1e69;
        }

        .channel-access-tabs button span {
            align-items: center;
            background: #fff0f6;
            border-radius: 10px;
            color: #bd1e69;
            display: inline-flex;
            font-size: 9px;
            justify-content: center;
            margin-left: 5px;
            min-height: 18px;
            min-width: 18px;
            padding: 1px 5px;
        }

        .channel-access-section {
            border-bottom: 1px solid #e8eaee;
            padding: 0 0 18px;
        }

        .channel-access-section + .channel-access-section {
            padding-top: 18px;
        }

        .channel-access-section:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .channel-access-label,
        .channel-date-panel label {
            color: #383e49;
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .channel-access-label span,
        .channel-range-fields label span {
            color: #9298a3;
            font-weight: 500;
        }

        .channel-date-modes,
        .channel-state-options {
            display: grid;
            gap: 7px;
            grid-template-columns: repeat(3, 1fr);
        }

        .channel-date-modes {
            margin-bottom: 14px;
        }

        .channel-date-modes input,
        .channel-state-options input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .channel-date-modes span,
        .channel-state-options span {
            align-items: center;
            border: 1px solid #d9dce2;
            border-radius: 6px;
            color: #5d6572;
            cursor: pointer;
            display: flex;
            font-size: 12px;
            font-weight: 600;
            justify-content: center;
            min-height: 36px;
            padding: 7px;
            text-align: center;
        }

        .channel-date-modes input:checked + span,
        .channel-state-options input:checked + span {
            background: #fff1f7;
            border-color: #df2a82;
            color: #bd1e69;
        }

        .channel-date-panel {
            max-width: 360px;
        }

        .channel-range-fields {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            max-width: none;
        }

        .channel-multiple-date-input {
            display: grid;
            gap: 8px;
            grid-template-columns: 1fr 42px;
        }

        .channel-multiple-date-input .btn {
            align-items: center;
            display: inline-flex;
            justify-content: center;
            padding: 0;
        }

        .channel-selected-dates,
        .channel-reason-options {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 9px;
        }

        .channel-date-chip,
        .channel-reason-options button {
            background: #f6f7f9;
            border: 1px solid #d9dce2;
            border-radius: 16px;
            color: #555d69;
            font-size: 11px;
            padding: 5px 9px;
        }

        .channel-date-chip button {
            background: transparent;
            border: 0;
            color: #a44;
            margin-left: 5px;
            padding: 0;
        }

        .channel-state-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .channel-state-card {
            border: 1px solid #dfe2e7;
            border-radius: 7px;
            padding: 13px;
        }

        .channel-state-card__head {
            align-items: center;
            display: flex;
            gap: 9px;
            margin-bottom: 11px;
        }

        .channel-state-card__head > i {
            color: #df2a82;
            font-size: 21px;
        }

        .channel-state-card__head strong,
        .channel-state-card__head small {
            display: block;
        }

        .channel-state-card__head small {
            color: #888f9b;
            font-size: 10px;
            margin-top: 2px;
        }

        .channel-state-options span {
            font-size: 10px;
            min-height: 32px;
            padding: 5px;
        }

        .channel-access-section textarea::placeholder,
        .channel-auth-input input::placeholder {
            font-style: italic;
            opacity: .62;
        }

        .channel-auth-input {
            position: relative;
        }

        .channel-auth-input input {
            padding-right: 44px;
        }

        .channel-auth-input button {
            background: transparent;
            border: 0;
            color: #737b88;
            height: 100%;
            position: absolute;
            right: 0;
            top: 0;
            width: 42px;
        }

        .channel-access-section > small,
        .channel-calendar-head small {
            color: #858d99;
            display: block;
            font-size: 10px;
            margin-top: 5px;
        }

        .channel-authorization-status {
            align-items: center;
            display: flex;
            font-size: 11px;
            font-weight: 600;
            gap: 6px;
            margin-top: 8px;
        }

        .channel-authorization-status.is-idle,
        .channel-authorization-status.is-checking {
            color: #777f8c;
        }

        .channel-authorization-status.is-authorized {
            color: #168d55;
        }

        .channel-authorization-status.is-invalid {
            color: #cf3139;
        }

        .channel-calendar-head {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 11px;
        }

        .channel-calendar-nav {
            align-items: center;
            display: grid;
            gap: 5px;
            grid-template-columns: 28px 120px 28px;
        }

        .channel-calendar-nav button {
            background: #f5f6f8;
            border: 1px solid #dfe2e7;
            border-radius: 5px;
            height: 28px;
            padding: 0;
        }

        .channel-calendar-nav strong {
            font-size: 11px;
            text-align: center;
        }

        .channel-calendar {
            display: grid;
            gap: 3px;
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }

        .channel-calendar__weekday,
        .channel-calendar__day {
            align-items: center;
            display: flex;
            justify-content: center;
            min-height: 31px;
        }

        .channel-calendar__weekday {
            color: #858d98;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .channel-calendar__day {
            border: 1px solid #eceef1;
            border-radius: 5px;
            color: #4b5360;
            flex-direction: column;
            font-size: 10px;
            position: relative;
        }

        .channel-calendar__day.is-empty {
            border-color: transparent;
        }

        .channel-calendar__marks {
            display: flex;
            gap: 3px;
            margin-top: 2px;
        }

        .channel-calendar__mark,
        .channel-calendar-legend i {
            border-radius: 50%;
            display: inline-block;
            height: 6px;
            width: 6px;
        }

        .channel-calendar__mark.is-kiosk {
            border-radius: 1px;
        }

        .channel-calendar__mark.is-blocked,
        .channel-calendar-legend .is-blocked {
            background: #d93e46;
        }

        .channel-calendar__mark.is-enabled,
        .channel-calendar-legend .is-enabled {
            background: #1b9b5e;
        }

        .channel-calendar__mark.is-preview {
            box-shadow: 0 0 0 2px #fff, 0 0 0 3px currentColor;
        }

        .channel-calendar-legend {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 6px;
        }

        .channel-calendar-legend span {
            align-items: center;
            color: #737b87;
            display: inline-flex;
            font-size: 9px;
            gap: 4px;
        }

        .channel-calendar-legend .channel-calendar-legend__hint {
            color: #9299a3;
            margin-left: auto;
        }

        .channel-access-rules {
            border-top: 1px solid #eceef1;
            margin-top: 12px;
            max-height: 190px;
            overflow-y: auto;
            padding-top: 8px;
        }

        .channel-access-rule {
            align-items: center;
            border-bottom: 1px solid #f0f1f3;
            display: grid;
            gap: 9px;
            grid-template-columns: 74px 1fr auto 30px;
            padding: 8px 3px;
        }

        .channel-access-rule__channel {
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            padding: 4px 7px;
            text-align: center;
        }

        .channel-access-rule__channel.is-online {
            background: #fff0f6;
            color: #c21d6a;
        }

        .channel-access-rule__channel.is-kiosk {
            background: #edf9f2;
            color: #167547;
        }

        .channel-access-rule strong,
        .channel-access-rule small {
            display: block;
        }

        .channel-access-rule strong {
            font-size: 11px;
        }

        .channel-access-rule small {
            color: #858d99;
            font-size: 9px;
            margin-top: 2px;
        }

        .channel-access-rule__state {
            font-size: 10px;
            font-weight: 800;
        }

        .channel-access-rule__state.is-blocked {
            color: #d23838;
        }

        .channel-access-rule__state.is-enabled {
            color: #168d55;
        }

        .channel-access-rule__delete {
            align-items: center;
            background: #fff;
            border: 1px solid #e1e4e9;
            border-radius: 5px;
            color: #c83942;
            display: inline-flex;
            height: 28px;
            justify-content: center;
            padding: 0;
            width: 28px;
        }

        .channel-access-rule__delete:hover,
        .channel-access-rule__delete:focus {
            background: #fff1f2;
            border-color: #efb8bc;
            color: #b6252e;
        }

        .channel-access-rule__delete:disabled {
            cursor: wait;
            opacity: .55;
        }

        .channel-access-empty {
            color: #8a929e;
            font-size: 11px;
            padding: 18px;
            text-align: center;
        }

        @media (max-width: 767.98px) {
            .channel-access-modal .modal-body {
                padding: 16px;
            }

            .channel-date-modes,
            .channel-state-grid,
            .channel-range-fields {
                grid-template-columns: 1fr;
            }

            .channel-calendar-head {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';

            const modalElement = document.getElementById('channelAccessModal');
            if (!modalElement) return;

            const modal = new bootstrap.Modal(modalElement);
            const today = @json(now()->format('Y-m-d'));
            let loadUrl = '';
            let storeUrl = '';
            let authorizeUrl = '';
            let tripId = null;
            let rules = [];
            let multipleDates = [];
            let calendarMonth = new Date(`${today}T00:00:00`);
            let authorizationTimer = null;
            let authorizationRequest = 0;
            let authorizationVerified = false;
            let savingChannelAccess = false;

            function resetForm() {
                const form = document.getElementById('channelAccessForm');
                form.reset();
                $('#channelSingleDate, #channelMultipleDate, #channelDateFrom').attr('min', today).val(today);
                $('#channelDateTo').attr('min', today).val('');
                $('#channelAuthorizationCode').attr('type', 'password').val('');
                $('#toggleChannelAuthorization i').attr('class', 'las la-eye');
                multipleDates = [];
                renderSelectedDates();
                showDatePanel('single');
                activateTab('configure');
                setAuthorizationState('idle', 'Enter a valid authorization code to enable saving.');
            }

            function activateTab(tab) {
                $('[data-channel-tab]').removeClass('is-active').attr('aria-selected', 'false');
                $(`[data-channel-tab="${tab}"]`).addClass('is-active').attr('aria-selected', 'true');
                $('[data-channel-pane]').addClass('d-none');
                $(`[data-channel-pane="${tab}"]`).removeClass('d-none');
            }

            function setAuthorizationState(state, message) {
                authorizationVerified = state === 'authorized';
                const icons = {
                    idle: 'las la-key',
                    checking: 'las la-spinner la-spin',
                    authorized: 'las la-check-circle',
                    invalid: 'las la-times-circle',
                };
                $('#channelAuthorizationStatus')
                    .attr('class', `channel-authorization-status is-${state}`)
                    .find('i').attr('class', icons[state] || icons.idle).end()
                    .find('span').text(message);
                updateSaveButton();
            }

            function updateSaveButton() {
                $('#saveChannelAccess').prop('disabled', !authorizationVerified || savingChannelAccess);
            }

            function showDatePanel(mode) {
                $('[data-date-panel]').addClass('d-none');
                $(`[data-date-panel="${mode}"]`).removeClass('d-none');
            }

            function renderSelectedDates() {
                const container = $('#channelSelectedDates').empty();
                multipleDates.sort().forEach(date => {
                    const chip = $('<span class="channel-date-chip"></span>').text(formatDate(date));
                    $('<input>', { type: 'hidden', name: 'dates[]', value: date }).appendTo(chip);
                    $('<button type="button" aria-label="Remove date"><i class="las la-times"></i></button>')
                        .on('click', function() {
                            multipleDates = multipleDates.filter(item => item !== date);
                            renderSelectedDates();
                        })
                        .appendTo(chip);
                    container.append(chip);
                });
                renderCalendar();
            }

            function formatDate(value, options = { month: 'short', day: 'numeric', year: 'numeric' }) {
                return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, options);
            }

            function loadChannelAccess(button) {
                loadUrl = button.data('channel-url');
                storeUrl = button.data('channel-store-url');
                authorizeUrl = button.data('channel-authorize-url');
                tripId = String(loadUrl).match(/trip\/(\d+)\/channel-access/)?.[1] || null;
                resetForm();
                $('#channelAccessLoading').removeClass('d-none');
                $('#channelAccessContent').addClass('d-none');
                $('#saveChannelAccess').prop('disabled', true);
                modal.show();

                $.get(loadUrl)
                    .done(response => {
                        rules = response.rules || [];
                        const departure = response.trip.departure ? ` - ${response.trip.departure}` : '';
                        $('#channelAccessTripSummary').text(`${response.trip.title}${departure}`);
                        Object.entries(response.trip.defaults || {}).forEach(([channel, enabled]) => {
                            $(`[data-channel-default="${channel}"]`).text(
                                `Default: ${enabled ? 'Available' : 'Blocked'} (from Add/Edit Trip)`
                            );
                        });
                        calendarMonth = rules.length
                            ? new Date(`${rules[0].date}T00:00:00`)
                            : new Date(`${today}T00:00:00`);
                        renderRules();
                        $('#channelAccessLoading').addClass('d-none');
                        $('#channelAccessContent').removeClass('d-none');
                        updateSaveButton();
                    })
                    .fail(() => {
                        modal.hide();
                        notify('error', 'Unable to load channel access settings.');
                    });
            }

            function renderRules() {
                renderCalendar();
                const list = $('#channelAccessRules').empty();

                if (!rules.length) {
                    list.html('<div class="channel-access-empty">No active dated channel settings.</div>');
                } else {
                    rules.forEach(rule => {
                        const meta = [rule.reason, rule.authorized_by ? `Authorized by ${rule.authorized_by}` : null]
                            .filter(Boolean)
                            .join(' - ');
                        list.append(`
                            <div class="channel-access-rule">
                                <span class="channel-access-rule__channel is-${rule.channel}">${capitalize(rule.channel)}</span>
                                <div>
                                    <strong>${escapeHtml(rule.date_label)}</strong>
                                    <small>${escapeHtml(meta || 'Dated channel override')}</small>
                                </div>
                                <span class="channel-access-rule__state ${rule.is_enabled ? 'is-enabled' : 'is-blocked'}">${rule.state}</span>
                                <button type="button" class="channel-access-rule__delete"
                                    data-delete-url="${escapeHtml(rule.delete_url)}"
                                    title="Remove dated override" aria-label="Remove dated override">
                                    <i class="las la-trash"></i>
                                </button>
                            </div>
                        `);
                    });
                }

                updateSummaryCounts();
            }

            function renderCalendar() {
                const year = calendarMonth.getFullYear();
                const month = calendarMonth.getMonth();
                const firstDay = new Date(year, month, 1);
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                $('#channelCalendarTitle').text(firstDay.toLocaleDateString(undefined, { month: 'long', year: 'numeric' }));

                let html = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
                    .map(day => `<div class="channel-calendar__weekday">${day}</div>`)
                    .join('');
                html += '<div class="channel-calendar__day is-empty"></div>'.repeat(firstDay.getDay());

                for (let day = 1; day <= daysInMonth; day++) {
                    const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    const dateRules = calendarRulesForDate(date);
                    const title = dateRules.map(rule =>
                        `${capitalize(rule.channel)}: ${rule.state}${rule.is_preview ? ' (selected)' : ''}`
                    ).join(', ');
                    const marks = dateRules.map(rule =>
                        `<i class="channel-calendar__mark is-${rule.channel} ${rule.is_enabled ? 'is-enabled' : 'is-blocked'} ${rule.is_preview ? 'is-preview' : ''}"></i>`
                    ).join('');
                    html += `<div class="channel-calendar__day" title="${escapeHtml(title)}"><span>${day}</span><span class="channel-calendar__marks">${marks}</span></div>`;
                }

                $('#channelAccessCalendar').html(html);
            }

            function calendarRulesForDate(date) {
                const dateRules = new Map(
                    rules.filter(rule => rule.date === date).map(rule => [rule.channel, { ...rule }])
                );

                if (isSelectedDate(date)) {
                    ['online', 'kiosk'].forEach(channel => {
                        const state = $(`input[name="${channel}_state"]:checked`).val();
                        if (!state) return;
                        dateRules.set(channel, {
                            channel,
                            is_enabled: state === 'enabled',
                            state: state === 'enabled' ? 'Available' : 'Blocked',
                            is_preview: true,
                        });
                    });
                }

                return Array.from(dateRules.values());
            }

            function isSelectedDate(date) {
                const mode = $('input[name="selection_mode"]:checked').val() || 'single';
                if (mode === 'single') return date === $('#channelSingleDate').val();
                if (mode === 'multiple') return multipleDates.includes(date);

                const from = $('#channelDateFrom').val();
                const to = $('#channelDateTo').val() || from;
                return Boolean(from && date >= from && date <= to);
            }

            function focusCalendarOn(date) {
                if (!date) return;
                const parsedDate = new Date(`${date}T00:00:00`);
                if (Number.isNaN(parsedDate.getTime())) return;
                calendarMonth = new Date(parsedDate.getFullYear(), parsedDate.getMonth(), 1);
                renderCalendar();
            }

            function updateSummaryCounts() {
                if (!tripId) return;
                ['online', 'kiosk'].forEach(channel => {
                    const count = rules.filter(rule => rule.channel === channel && !rule.is_enabled).length;
                    const item = $(`[data-channel-summary-trip="${tripId}"] [data-channel="${channel}"]`);
                    item.toggleClass('has-blocks', count > 0);
                    item.find(`[data-channel-count="${channel}"]`).text(count);
                });
                $('#channelBlockTabCount').text(rules.filter(rule => !rule.is_enabled).length);
            }

            function capitalize(value) {
                return value.charAt(0).toUpperCase() + value.slice(1);
            }

            function escapeHtml(value) {
                return $('<div>').text(value || '').html();
            }

            function verifyAuthorizationCode() {
                const code = String($('#channelAuthorizationCode').val() || '').trim();
                if (!code) {
                    setAuthorizationState('idle', 'Enter a valid authorization code to enable saving.');
                    return;
                }

                if (!authorizeUrl) {
                    setAuthorizationState('invalid', 'Authorization validation is currently unavailable.');
                    return;
                }

                const requestId = ++authorizationRequest;
                setAuthorizationState('checking', 'Checking authorization code...');

                $.ajax({
                    url: authorizeUrl,
                    method: 'POST',
                    dataType: 'json',
                    headers: {
                        Accept: 'application/json',
                    },
                    data: {
                        _token: $('#channelAccessForm input[name="_token"]').val(),
                        authorization_code: code,
                        reason: $('#channelAccessReason').val(),
                    },
                }).done(response => {
                    if (requestId !== authorizationRequest || code !== String($('#channelAuthorizationCode').val() || '').trim()) return;
                    const authorizedBy = typeof response?.authorized_by === 'string'
                        ? response.authorized_by.trim()
                        : '';
                    if (response?.authorized !== true || !authorizedBy) {
                        setAuthorizationState(
                            'invalid',
                            response?.message || 'The authorization code could not be verified.'
                        );
                        return;
                    }
                    setAuthorizationState('authorized', response.message || `Authorized by ${authorizedBy}.`);
                }).fail(xhr => {
                    if (requestId !== authorizationRequest) return;
                    const errors = xhr.responseJSON?.errors || {};
                    const message = Object.values(errors).flat()[0]
                        || xhr.responseJSON?.message
                        || (xhr.status === 200
                            ? 'The server returned an invalid authorization response. Please try again.'
                            : 'The authorization code is invalid or is not permitted for Channel Access.');
                    setAuthorizationState('invalid', message);
                });
            }

            $(document).on('click', '.channelAccessBtn', function() {
                loadChannelAccess($(this));
            });

            $('[data-channel-tab]').on('click', function() {
                activateTab($(this).data('channel-tab'));
            });

            $('input[name="selection_mode"]').on('change', function() {
                showDatePanel(this.value);
                const focusDate = this.value === 'multiple'
                    ? (multipleDates[0] || $('#channelMultipleDate').val())
                    : (this.value === 'range' ? $('#channelDateFrom').val() : $('#channelSingleDate').val());
                focusCalendarOn(focusDate || today);
            });

            $('#channelSingleDate').on('change', function() {
                focusCalendarOn(this.value);
            });

            $('#channelMultipleDate').on('change', function() {
                focusCalendarOn(this.value);
            });

            $('input[name="online_state"], input[name="kiosk_state"]').on('change', renderCalendar);

            $('#addChannelDate').on('click', function() {
                const date = $('#channelMultipleDate').val();
                if (date && !multipleDates.includes(date)) {
                    multipleDates.push(date);
                    renderSelectedDates();
                    focusCalendarOn(date);
                }
            });

            $('#channelDateFrom').on('change', function() {
                $('#channelDateTo').attr('min', this.value || today);
                if ($('#channelDateTo').val() && $('#channelDateTo').val() < this.value) {
                    $('#channelDateTo').val('');
                }
                focusCalendarOn(this.value);
            });

            $('#channelDateTo').on('change', renderCalendar);

            $('[data-channel-reason]').on('click', function() {
                $('#channelAccessReason').val($(this).data('channel-reason'));
            });

            $('#toggleChannelAuthorization').on('click', function() {
                const input = $('#channelAuthorizationCode');
                const show = input.attr('type') === 'password';
                input.attr('type', show ? 'text' : 'password');
                $(this).find('i').attr('class', show ? 'las la-eye-slash' : 'las la-eye');
            });

            $('#channelAuthorizationCode').on('input', function() {
                window.clearTimeout(authorizationTimer);
                authorizationRequest++;
                authorizationVerified = false;
                updateSaveButton();

                if (!String(this.value || '').trim()) {
                    setAuthorizationState('idle', 'Enter a valid authorization code to enable saving.');
                    return;
                }

                setAuthorizationState('checking', 'Waiting for code entry...');
                authorizationTimer = window.setTimeout(verifyAuthorizationCode, 650);
            }).on('blur', function() {
                window.clearTimeout(authorizationTimer);
                if (String(this.value || '').trim() && !authorizationVerified) {
                    verifyAuthorizationCode();
                }
            });

            $('#channelCalendarPrev').on('click', function() {
                calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() - 1, 1);
                renderCalendar();
            });

            $('#channelCalendarNext').on('click', function() {
                calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() + 1, 1);
                renderCalendar();
            });

            $(document).on('click', '.channel-access-rule__delete', function() {
                if (!authorizationVerified) {
                    activateTab('configure');
                    setAuthorizationState('idle', 'Enter and verify an authorization code before removing a dated override.');
                    notify('error', 'Authorization is required before removing a Channel Access setting.');
                    window.setTimeout(() => $('#channelAuthorizationCode').trigger('focus'), 100);
                    return;
                }

                if (!window.confirm('Remove this dated channel override? The trip will return to its default Booking Channels setting for this date.')) {
                    return;
                }

                const button = $(this).prop('disabled', true);
                $.ajax({
                    url: button.data('delete-url'),
                    method: 'DELETE',
                    dataType: 'json',
                    headers: {
                        Accept: 'application/json',
                    },
                    data: {
                        _token: $('#channelAccessForm input[name="_token"]').val(),
                        authorization_code: $('#channelAuthorizationCode').val(),
                    },
                }).done(response => {
                    if (response?.success !== true || !Array.isArray(response.rules)) {
                        notify('error', response?.message || 'The Channel Access setting could not be removed.');
                        return;
                    }

                    rules = response.rules;
                    $('#channelAuthorizationCode').val('').attr('type', 'password');
                    $('#toggleChannelAuthorization i').attr('class', 'las la-eye');
                    setAuthorizationState('idle', 'Enter a valid authorization code to make another change.');
                    renderRules();
                    notify('success', response.message);
                }).fail(xhr => {
                    const errors = xhr.responseJSON?.errors || {};
                    const message = Object.values(errors).flat()[0]
                        || xhr.responseJSON?.message
                        || 'Unable to remove the dated channel override.';
                    notify('error', message);
                    if (errors.authorization_code || xhr.status === 401 || xhr.status === 403) {
                        $('#channelAuthorizationCode').val('');
                        setAuthorizationState('invalid', message);
                    }
                }).always(() => button.prop('disabled', false));
            });

            $('#channelAccessForm').on('submit', function(event) {
                event.preventDefault();
                if (!authorizationVerified) {
                    setAuthorizationState('invalid', 'Verify a valid authorization code before saving.');
                    return;
                }

                savingChannelAccess = true;
                updateSaveButton();
                const formData = new FormData(this);

                $.ajax({
                    url: storeUrl,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                }).done(response => {
                    rules = response.rules || [];
                    $('#channelAuthorizationCode').val('').attr('type', 'password');
                    $('#toggleChannelAuthorization i').attr('class', 'las la-eye');
                    $('input[name="online_state"][value=""], input[name="kiosk_state"][value=""]').prop('checked', true);
                    setAuthorizationState('idle', 'Enter a valid authorization code to make another change.');
                    renderRules();
                    activateTab('blocks');
                    notify('success', response.message);
                }).fail(xhr => {
                    const errors = xhr.responseJSON?.errors || {};
                    const message = Object.values(errors).flat()[0] || xhr.responseJSON?.message || 'Unable to save channel access.';
                    notify('error', message);
                    if (errors.authorization_code || xhr.status === 401 || xhr.status === 403) {
                        $('#channelAuthorizationCode').val('');
                        setAuthorizationState('invalid', message);
                    }
                }).always(() => {
                    savingChannelAccess = false;
                    updateSaveButton();
                });
            });

            modalElement.addEventListener('hidden.bs.modal', function() {
                window.clearTimeout(authorizationTimer);
                authorizationRequest++;
                $('#channelAuthorizationCode').val('').attr('type', 'password');
                setAuthorizationState('idle', 'Enter a valid authorization code to enable saving.');
            });

            $('.channel-summary-item').each(function() {
                const count = Number($(this).find('b').text()) || 0;
                $(this).toggleClass('has-blocks', count > 0);
            });
        })(jQuery);
    </script>
@endpush
