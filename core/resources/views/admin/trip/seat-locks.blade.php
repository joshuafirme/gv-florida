@extends('admin.layouts.app')

@section('panel')
    <div class="seat-lock-page">
        <header class="seat-lock-header">
            <div>
                <p>
                    {{ $trip->route?->name ?: $trip->title }}
                    &middot; {{ $trip->schedule?->start_from ? date('h:i A', strtotime($trip->schedule->start_from)) : '-' }}
                    &middot; {{ $trip->fleetType?->name ?: '-' }}
                </p>
            </div>

            <div class="seat-lock-stats" aria-label="Seat totals">
                <div>
                    <span>Capacity</span>
                    <strong>{{ $stats['capacity'] }}</strong>
                </div>
                <div>
                    <span>Booked</span>
                    <strong class="is-booked">{{ $stats['booked'] }}</strong>
                </div>
                <div>
                    <span>Locked</span>
                    <strong class="is-locked">{{ $stats['locked'] }}</strong>
                </div>
                <div>
                    <span>Available</span>
                    <strong class="is-available">{{ $stats['available'] }}</strong>
                </div>
            </div>
        </header>

        <div class="seat-lock-toolbar">
            <a href="{{ route('admin.trip.list') }}" class="seat-lock-back">
                <i class="las la-arrow-left"></i> Trips
            </a>
            <form action="{{ route('admin.trip.seat-locks.index', $trip->id) }}" method="GET"
                id="seatLockDateForm">
                <label for="seatLockDate">Travel date</label>
                <div class="seat-lock-date">
                    <input id="seatLockDate" type="date" name="date" value="{{ $date->format('Y-m-d') }}">
                    <i class="las la-calendar"></i>
                </div>
            </form>
        </div>

        <div class="seat-lock-legend" aria-label="Seat status legend">
            <span><i class="legend-swatch is-available"></i> Available &mdash; select to lock</span>
            <span><i class="legend-swatch is-locked"><i class="las la-lock"></i></i> Locked &mdash; select to unlock</span>
            <span><i class="legend-swatch is-booked"></i> Booked / held</span>
            <span><i class="legend-swatch is-disabled"></i> Fleet unavailable</span>
        </div>

        <div class="seat-lock-decks">
            @foreach ($decks as $deckIndex => $cells)
                @php
                    $layout = array_map('intval', explode('x', str_replace(' ', '', (string) $trip->fleetType->seat_layout)));
                    $columnCount = max(array_sum($layout), 1);
                @endphp
                <section class="seat-lock-deck">
                    <div class="seat-lock-deck__heading">
                        <strong>{{ $deckIndex === 0 ? 'Lower Deck' : 'Upper Deck' }}</strong>
                        <span>
                            @if ($deckIndex === 0)
                                <i class="las la-user"></i>
                                <i class="las la-door-open"></i> Door
                            @else
                                Deck {{ $deckIndex + 1 }}
                            @endif
                        </span>
                    </div>

                    <div class="seat-lock-grid" style="--seat-columns: {{ $columnCount }}">
                        @foreach ($cells as $cell)
                            @if ($cell['type'] === 'cr')
                                <div class="seat-lock-seat is-cr" aria-label="Comfort room">CR</div>
                            @else
                                @php
                                    $seatId = $cell['seat_id'];
                                    $lock = $lockedSeats->get($seatId);
                                    $isBooked = in_array($seatId, $bookedSeats, true);
                                    $isDisabled = in_array($seatId, $disabledSeats, true);
                                    $state = $lock ? 'locked' : ($isBooked ? 'booked' : ($isDisabled ? 'disabled' : 'available'));
                                    $action = $lock ? 'unlock' : 'lock';
                                @endphp

                                <button type="button"
                                    class="seat-lock-seat is-{{ $state }} {{ in_array($state, ['booked', 'disabled'], true) ? 'is-static' : 'js-seat-lock' }}"
                                    data-seat="{{ $seatId }}"
                                    data-label="{{ $cell['label'] }}"
                                    data-action="{{ $action }}"
                                    @if ($lock)
                                        data-reason="{{ $lock->reason }}"
                                        title="Locked: {{ $lock->reason }}"
                                    @elseif ($isBooked)
                                        disabled title="Booked or held by a passenger transaction"
                                    @elseif ($isDisabled)
                                        disabled title="Unavailable in the fleet layout"
                                    @endif>
                                    @if ($lock)
                                        <i class="las la-lock" aria-hidden="true"></i>
                                    @endif
                                    <span>{{ $cell['label'] }}</span>
                                </button>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <p class="seat-lock-note">
            Administrative locks do not create a booking, ticket, PNR, or payment record. Locked seats remain
            unavailable in every booking channel until an authorized administrator unlocks them.
        </p>
    </div>

    <div class="modal fade" id="seatLockModal" tabindex="-1" aria-labelledby="seatLockModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content seat-lock-modal">
                <div class="modal-header">
                    <div>
                        <span class="seat-lock-modal__icon"><i class="las la-lock"></i></span>
                        <h5 class="modal-title" id="seatLockModalTitle">Lock Seat</h5>
                        <p class="js-seat-lock-subtitle"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="seatLockActionForm">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
                    <input type="hidden" name="seat">
                    <input type="hidden" name="action">

                    <div class="modal-body">
                        <label class="seat-lock-label" for="seatLockReason">Reason</label>
                        <div class="seat-lock-reasons">
                            @foreach (['Company Owner', 'VIP Guest', 'Crew', 'Maintenance', 'Operational Requirement', 'Government Official', 'Other'] as $reason)
                                <button type="button" class="seat-lock-reason-chip" data-reason="{{ $reason }}">
                                    {{ $reason }}
                                </button>
                            @endforeach
                        </div>
                        <textarea id="seatLockReason" name="reason" rows="3"
                            placeholder="Enter the reason for locking this seat" required></textarea>

                        <div class="seat-lock-authorization">
                            <div class="seat-lock-authorization__title">
                                <i class="las la-shield-alt"></i>
                                <div>
                                    <strong>Authorization Required</strong>
                                    <p>An authorized administrator must approve this action.</p>
                                </div>
                            </div>
                            <label class="seat-lock-label" for="seatLockAuthorizationCode">Authorization Code</label>
                            <div class="seat-lock-code">
                                <input id="seatLockAuthorizationCode" type="password" name="authorization_code"
                                    placeholder="Enter authorization code" autocomplete="new-password" required>
                                <button type="button" id="toggleSeatLockCode" aria-label="Show authorization code">
                                    <i class="las la-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="seat-lock-error js-seat-lock-error" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn--primary js-seat-lock-submit">
                            <i class="las la-lock"></i> Lock Seat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .seat-lock-page {
            color: #252b37;
        }

        .seat-lock-header {
            align-items: flex-start;
            display: flex;
            gap: 24px;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .seat-lock-header h2 {
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 5px;
        }

        .seat-lock-header p {
            color: #646b78;
            margin: 0;
        }

        .seat-lock-stats {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(4, minmax(80px, 1fr));
        }

        .seat-lock-stats > div {
            background: #fff;
            border: 1px solid #dfe2e7;
            border-radius: 7px;
            min-width: 80px;
            padding: 10px 14px;
            text-align: center;
        }

        .seat-lock-stats span {
            color: #7a818e;
            display: block;
            font-size: 10px;
            text-transform: uppercase;
        }

        .seat-lock-stats strong {
            display: block;
            font-size: 18px;
            margin-top: 2px;
        }

        .seat-lock-stats .is-booked { color: #1477a5; }
        .seat-lock-stats .is-locked { color: #a85b00; }
        .seat-lock-stats .is-available { color: #14804a; }

        .seat-lock-toolbar {
            align-items: center;
            display: flex;
            gap: 14px;
            margin-bottom: 20px;
        }

        .seat-lock-back {
            align-items: center;
            background: #e9eaed;
            border: 1px solid #d7d9de;
            border-radius: 7px;
            color: #343943;
            display: inline-flex;
            font-weight: 600;
            gap: 6px;
            height: 44px;
            padding: 0 16px;
        }

        .seat-lock-toolbar form {
            align-items: center;
            display: flex;
            gap: 10px;
        }

        .seat-lock-toolbar label {
            color: #555d6a;
            font-weight: 600;
            margin: 0;
        }

        .seat-lock-date {
            position: relative;
        }

        .seat-lock-date input {
            background: #fff;
            border: 1px solid #d7d9de;
            border-radius: 7px;
            height: 44px;
            padding: 8px 38px 8px 12px;
        }

        .seat-lock-date i {
            color: #555d6a;
            pointer-events: none;
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
        }

        .seat-lock-legend {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 20px;
        }

        .seat-lock-legend > span {
            align-items: center;
            color: #626a78;
            display: inline-flex;
            font-size: 12px;
            gap: 7px;
        }

        .legend-swatch {
            align-items: center;
            border: 1px solid #d4d7dd;
            border-radius: 4px;
            display: inline-flex;
            height: 22px;
            justify-content: center;
            width: 22px;
        }

        .legend-swatch.is-available { background: #f7f8fa; }
        .legend-swatch.is-locked { background: #fff0d9; border-color: #efbd71; color: #a85b00; }
        .legend-swatch.is-booked { background: #dfe2e7; }
        .legend-swatch.is-disabled { background: #f1f2f4; opacity: .55; }

        .seat-lock-decks {
            align-items: start;
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }

        .seat-lock-deck {
            background: #fff;
            border: 1px solid #dfe2e7;
            border-radius: 8px;
            max-width: 540px;
            min-width: 360px;
            padding: 22px;
        }

        .seat-lock-deck__heading {
            align-items: center;
            color: #727986;
            display: flex;
            font-size: 11px;
            justify-content: space-between;
            margin-bottom: 22px;
            text-transform: uppercase;
        }

        .seat-lock-deck__heading span {
            align-items: center;
            display: flex;
            gap: 5px;
        }

        .seat-lock-grid {
            display: grid;
            gap: 10px 18px;
            grid-template-columns: repeat(var(--seat-columns), 48px);
            justify-content: center;
        }

        .seat-lock-seat {
            align-items: center;
            background: #f7f8fa;
            border: 1px solid #d7dae0;
            border-radius: 6px;
            color: #4b5260;
            display: inline-flex;
            flex-direction: column;
            font-size: 11px;
            font-weight: 700;
            height: 48px;
            justify-content: center;
            line-height: 1;
            min-width: 48px;
            padding: 4px;
            position: relative;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }

        button.seat-lock-seat:not(.is-static):hover {
            border-color: #d92378;
            box-shadow: 0 0 0 3px rgba(217, 35, 120, .10);
            transform: translateY(-1px);
        }

        .seat-lock-seat span::after {
            background: currentColor;
            content: '';
            display: block;
            height: 2px;
            margin: 7px auto 0;
            opacity: .35;
            width: 20px;
        }

        .seat-lock-seat.is-locked {
            background: #fff0d9;
            border-color: #efbd71;
            color: #9a5500;
        }

        .seat-lock-seat.is-locked > i {
            font-size: 12px;
            position: absolute;
            right: 4px;
            top: 4px;
        }

        .seat-lock-seat.is-booked {
            background: #dfe2e7;
            color: #69717f;
        }

        .seat-lock-seat.is-disabled {
            background: #f1f2f4;
            color: #a4a9b1;
            opacity: .55;
        }

        .seat-lock-seat.is-static {
            cursor: not-allowed;
        }

        .seat-lock-seat.is-cr {
            background: #eef4f7;
            color: #557080;
        }

        .seat-lock-note {
            color: #737b88;
            font-size: 11px;
            margin: 18px 0 0;
            max-width: 800px;
        }

        .seat-lock-modal {
            border: 0;
            border-radius: 8px;
            overflow: hidden;
        }

        .seat-lock-modal .modal-header {
            align-items: flex-start;
            border-bottom: 1px solid #e6e8ec;
            padding: 20px 22px;
        }

        .seat-lock-modal .modal-header > div {
            display: grid;
            gap: 2px 10px;
            grid-template-columns: 38px auto;
        }

        .seat-lock-modal__icon {
            align-items: center;
            background: #fff0d9;
            border-radius: 7px;
            color: #a85b00;
            display: inline-flex;
            font-size: 20px;
            grid-row: span 2;
            height: 38px;
            justify-content: center;
            width: 38px;
        }

        .seat-lock-modal .modal-title {
            font-size: 17px;
            font-weight: 800;
            margin: 0;
        }

        .seat-lock-modal .modal-header p {
            color: #727986;
            font-size: 12px;
            margin: 0;
        }

        .seat-lock-modal .modal-body {
            padding: 20px 22px;
        }

        .seat-lock-label {
            color: #4d5563;
            display: block;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 7px;
            text-transform: uppercase;
        }

        .seat-lock-reasons {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-bottom: 9px;
        }

        .seat-lock-reason-chip {
            background: #f4f5f7;
            border: 1px solid #d8dbe1;
            border-radius: 16px;
            color: #4f5664;
            font-size: 11px;
            padding: 5px 10px;
        }

        .seat-lock-reason-chip.is-selected {
            background: #fff0f7;
            border-color: #d92378;
            color: #b71f67;
        }

        #seatLockReason,
        .seat-lock-code input {
            border: 1px solid #d7dae0;
            border-radius: 7px;
            font-size: 13px;
            width: 100%;
        }

        #seatLockReason {
            padding: 10px 12px;
            resize: vertical;
        }

        .seat-lock-authorization {
            background: #f8f9fa;
            border: 1px solid #e0e3e7;
            border-radius: 8px;
            margin-top: 18px;
            padding: 15px;
        }

        .seat-lock-authorization__title {
            align-items: flex-start;
            display: flex;
            gap: 9px;
            margin-bottom: 13px;
        }

        .seat-lock-authorization__title > i {
            color: #d92378;
            font-size: 18px;
            margin-top: 1px;
        }

        .seat-lock-authorization__title strong {
            display: block;
            font-size: 13px;
        }

        .seat-lock-authorization__title p {
            color: #747b87;
            font-size: 11px;
            margin: 2px 0 0;
        }

        .seat-lock-code {
            position: relative;
        }

        .seat-lock-code input {
            height: 42px;
            padding: 8px 42px 8px 12px;
        }

        .seat-lock-code button {
            background: transparent;
            border: 0;
            color: #737b88;
            height: 100%;
            position: absolute;
            right: 0;
            top: 0;
            width: 42px;
        }

        .seat-lock-error {
            color: #bd2c2c;
            display: none;
            font-size: 12px;
            margin-top: 12px;
        }

        .seat-lock-modal .modal-footer {
            border-top: 1px solid #e6e8ec;
            padding: 14px 22px;
        }

        @media (max-width: 767px) {
            .seat-lock-header {
                flex-direction: column;
            }

            .seat-lock-stats {
                width: 100%;
            }

            .seat-lock-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .seat-lock-toolbar form {
                align-items: stretch;
                flex-direction: column;
            }

            .seat-lock-date input {
                width: 100%;
            }

            .seat-lock-deck {
                min-width: 0;
                width: 100%;
            }

            .seat-lock-grid {
                gap: 8px;
                grid-template-columns: repeat(var(--seat-columns), minmax(38px, 48px));
            }
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';

            const modalElement = document.getElementById('seatLockModal');
            const seatLockModal = new bootstrap.Modal(modalElement);
            const actionUrl = @json(route('admin.trip.seat-locks.change', $trip->id));

            $('#seatLockDate').on('change', function() {
                $('#seatLockDateForm').trigger('submit');
            });

            $('.js-seat-lock').on('click', function() {
                const button = $(this);
                const action = button.data('action');
                const label = button.data('label');
                const isLocking = action === 'lock';

                $('#seatLockActionForm')[0].reset();
                $('#seatLockActionForm input[name="date"]').val(@json($date->format('Y-m-d')));
                $('#seatLockActionForm input[name="seat"]').val(button.data('seat'));
                $('#seatLockActionForm input[name="action"]').val(action);
                $('#seatLockModalTitle').text(`${isLocking ? 'Lock' : 'Unlock'} Seat ${label}`);
                $('.js-seat-lock-subtitle').text(
                    isLocking
                        ? 'Reserve this seat for internal or operational use.'
                        : `Current reason: ${button.data('reason') || 'Administrative lock'}`
                );
                $('#seatLockReason').attr(
                    'placeholder',
                    isLocking ? 'Enter the reason for locking this seat' : 'Enter the reason for unlocking this seat'
                );
                $('.seat-lock-reason-chip').removeClass('is-selected');
                $('.js-seat-lock-error').hide().text('');
                $('.js-seat-lock-submit')
                    .html(`<i class="las la-${isLocking ? 'lock' : 'unlock'}"></i> ${isLocking ? 'Lock' : 'Unlock'} Seat`);
                seatLockModal.show();
            });

            $('.seat-lock-reason-chip').on('click', function() {
                const reason = $(this).data('reason');
                $('.seat-lock-reason-chip').removeClass('is-selected');
                $(this).addClass('is-selected');

                if (reason === 'Other') {
                    $('#seatLockReason').val('').trigger('focus');
                    return;
                }

                $('#seatLockReason').val(reason);
            });

            $('#toggleSeatLockCode').on('click', function() {
                const input = document.getElementById('seatLockAuthorizationCode');
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                $(this).attr('aria-label', show ? 'Hide authorization code' : 'Show authorization code')
                    .find('i')
                    .toggleClass('la-eye', !show)
                    .toggleClass('la-eye-slash', show);
            });

            $('#seatLockActionForm').on('submit', async function(event) {
                event.preventDefault();

                const form = this;
                const submitButton = $('.js-seat-lock-submit');
                const originalLabel = submitButton.html();
                $('.js-seat-lock-error').hide().text('');
                submitButton.prop('disabled', true)
                    .html('<i class="las la-spinner la-spin"></i> Verifying...');

                try {
                    const response = await fetch(actionUrl, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const result = await response.json();

                    if (!response.ok) {
                        const message = result.message
                            || (result.errors ? Object.values(result.errors).flat()[0] : null)
                            || 'Unable to update this seat.';
                        throw new Error(message);
                    }

                    if (typeof notify === 'function') {
                        notify('success', result.message);
                    }
                    window.location.reload();
                } catch (error) {
                    $('.js-seat-lock-error').text(error.message).show();
                    submitButton.prop('disabled', false).html(originalLabel);
                }
            });
        })(jQuery);
    </script>
@endpush
