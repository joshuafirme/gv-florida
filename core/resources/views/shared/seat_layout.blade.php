@php
    $seatLayoutMode = $seatLayoutMode ?? 'selection';
    $lockedSeatDetails = $lockedSeatDetails ?? collect();
@endphp

<div class="shared-seat-layout shared-seat-layout--{{ $seatLayoutMode }}">
    <h4 class="shared-seat-layout__title">{{ $seatLayout['name'] }}</h4>

    @foreach ($seatLayout['decks'] as $deck)
        <div class="seat-plan-inner" data-deck="{{ $deck['number'] }}">
            <div class="single">
                <span class="front">Front</span>
                <span class="rear">Rear</span>

                @if ($deck['number'] === 1)
                    <span class="driver">
                        <img src="{{ asset('assets/templates/basic/images/icon/wheel.svg') }}" alt="Driver">
                    </span>
                    <span class="lower"><i class="las la-door-open"></i> Door</span>
                @else
                    <span class="driver">{{ $deck['name'] }}</span>
                @endif

                @foreach ($deck['rows'] as $row)
                    <div class="seat-wrapper {{ $row['centered'] ? 'seat-wrapper--centered justify-content-center' : '' }} {{ $row['is_front_row'] ? 'sc-pwd-row' : '' }}"
                        data-row="{{ $row['number'] }}">
                        @foreach ($row['groups'] as $group)
                            <div class="{{ $group['name'] }}-side seat-layout-group" data-group="{{ $group['name'] }}">
                                @foreach ($group['cells'] as $cell)
                                    @if ($cell['type'] === 'empty')
                                        <div class="seat-layout-cell seat-layout-cell--empty" aria-hidden="true"></div>
                                        @continue
                                    @endif

                                    @if ($cell['type'] === 'covered')
                                        @continue
                                    @endif

                                    @if ($cell['type'] === 'cr')
                                        <div class="seat-layout-cell seat-layout-cell--span is-static"
                                            style="--seat-span: {{ $cell['span'] ?? 1 }}; --seat-row-span: {{ $cell['row_span'] ?? 1 }};">
                                            <span class="seat comfort-room" aria-label="Comfort Room">CR</span>
                                        </div>
                                        @continue
                                    @endif

                                    @php
                                        $state = $cell['state'];
                                        $isUnavailable = in_array($state, ['disabled', 'booked', 'pending', 'locked'], true);
                                        $customerState = in_array($state, ['booked', 'pending', 'locked'], true) ? 'booked' : $state;
                                        $lock = $lockedSeatDetails->get($cell['seat_id']);
                                    @endphp

                                    <div class="seat-layout-cell is-{{ $customerState }} {{ $isUnavailable ? 'disabled' : '' }} {{ $cell['is_sc_pwd'] ? 'is-sc-pwd' : '' }} {{ $seatLayoutMode === 'lock' && $state === 'locked' ? 'is-admin-locked' : '' }}">
                                        @if ($seatLayoutMode === 'lock')
                                            @php
                                                $isStatic = in_array($state, ['booked', 'pending', 'disabled'], true);
                                                $action = $state === 'locked' ? 'unlock' : 'lock';
                                            @endphp
                                            <button type="button"
                                                class="seat {{ $state === 'disabled' ? 'disabled-seat' : '' }} {{ !$isStatic ? 'js-seat-lock' : '' }}"
                                                data-seat="{{ $cell['seat_id'] }}"
                                                data-label="{{ $cell['label'] }}"
                                                data-action="{{ $action }}"
                                                @if ($lock) data-reason="{{ $lock->reason }}" @endif
                                                @if ($isStatic) disabled @endif
                                                title="{{ match ($state) {
                                                    'locked' => 'Locked: ' . ($lock?->reason ?: 'Administrative lock'),
                                                    'booked', 'pending' => 'Booked or held by a passenger transaction',
                                                    'disabled' => 'Non-operational seat',
                                                    default => 'Available seat',
                                                } }}">
                                                @if ($state === 'locked')
                                                    <i class="las la-lock" aria-hidden="true"></i>
                                                @endif
                                                {{ $cell['label'] }}
                                                @if ($cell['is_sc_pwd']) <small>SC/PWD</small> @endif
                                                <span aria-hidden="true"></span>
                                            </button>
                                        @else
                                            <span class="seat {{ $state === 'disabled' ? 'disabled-seat' : '' }}"
                                                data-seat="{{ $cell['seat_id'] }}"
                                                data-label="{{ $cell['label'] }}"
                                                aria-disabled="{{ $isUnavailable ? 'true' : 'false' }}"
                                                title="{{ match ($state) {
                                                    'disabled' => 'Non-operational seat',
                                                    'booked', 'pending', 'locked' => 'Already booked',
                                                    default => $cell['is_sc_pwd'] ? 'Senior Citizen / PWD priority seat' : 'Available seat',
                                                } }}">
                                                {{ $cell['label'] }}
                                                @if ($cell['is_sc_pwd']) <small>SC/PWD</small> @endif
                                                <span aria-hidden="true"></span>
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
