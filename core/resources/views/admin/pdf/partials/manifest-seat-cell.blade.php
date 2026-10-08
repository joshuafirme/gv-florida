@if ($cell['type'] === 'covered')
@elseif ($cell['type'] === 'empty')
    <td class="manifest-seat manifest-seat--empty" aria-hidden="true"></td>
@else
    @php
        $isComfortRoom = $cell['type'] === 'cr';
        $manifest = $isComfortRoom ? null : $seatManifest->get($cell['seat_id']);
        $lockedSeat = $isComfortRoom ? null : $lockedSeats->get($cell['seat_id']);
        $isDisabled = !$isComfortRoom && $cell['state'] === 'disabled';
        $columnSpan = $isComfortRoom ? ($cell['span'] ?? 1) : 1;
        $rowSpan = $isComfortRoom ? ($cell['row_span'] ?? 1) : 1;
        $classes = collect([
            'manifest-seat',
            $isComfortRoom ? 'comfort-room' : null,
            $manifest ? 'occupied' : null,
            $manifest && $manifest['pending_payment'] ? 'blocked pending-payment' : null,
            $manifest && $manifest['online_booking'] ? 'online-booking' : null,
            $lockedSeat ? 'admin-locked' : null,
            $isDisabled ? 'disabled' : null,
        ])->filter()->implode(' ');
    @endphp
    <td class="{{ $classes }}" colspan="{{ $columnSpan }}" rowspan="{{ $rowSpan }}">
        <div class="manifest-seat-top">
            <span class="manifest-seat-number">{{ $cell['label'] }}</span>
            @if ($isComfortRoom)
                <span class="manifest-seat-status">Comfort Room</span>
            @elseif ($isDisabled)
                <span class="manifest-seat-status">Unavailable</span>
            @elseif ($lockedSeat)
                <span class="manifest-seat-status">Admin Locked</span>
            @elseif ($manifest)
                <span class="manifest-seat-status">
                    @if ($manifest['pending_payment'])
                        Pending
                    @elseif ($manifest['online_booking'])
                        Online
                    @else
                        Occupied
                    @endif
                </span>
            @else
                <span class="manifest-seat-status">Vacant</span>
            @endif
        </div>

        @if ($isComfortRoom)
            <div class="manifest-cr-fill"
                style="height: {{ number_format(max(($rowHeightMm * $rowSpan) - 9, 4), 2, '.', '') }}mm;">
                CR
            </div>
        @elseif ($lockedSeat)
            <div class="manifest-lock-details">
                <strong>Reserved for internal use</strong>
                <span>Reason: {{ $lockedSeat['reason'] }}</span>
                @if ($lockedSeat['authorized_by'])
                    <span>Authorized by: {{ $lockedSeat['authorized_by'] }}</span>
                @endif
            </div>
        @elseif ($manifest && $manifest['pending_payment'])
            <div class="manifest-pending-details">
                <strong>Pending Payment &ndash; Temporarily Locked</strong>
                <span>
                    {{ $manifest['passenger_name'] }} &middot; {{ $manifest['destination'] ?: '-' }}
                    @if ($manifest['km_post'])
                        &middot; KM {{ $manifest['km_post'] }}
                    @endif
                </span>
                <span class="manifest-pending-reference">
                    {{ $manifest['pnr'] ?: 'No. ' . $manifest['reference'] }}
                    &middot; {{ $manifest['booking_channel'] }}
                </span>
            </div>
        @elseif ($manifest)
            <div class="manifest-passenger">
                <span class="manifest-reference">No. {{ $manifest['reference'] }}</span>
                <span class="manifest-passenger-name">{{ $manifest['passenger_name'] }}</span>
                @if ($manifest['discount_applied'] && $manifest['passenger_id'])
                    <span class="manifest-passenger-id">ID No. {{ $manifest['passenger_id'] }}</span>
                @endif
                <span class="manifest-passenger-dropoff">
                    {{ $manifest['destination'] ?: '-' }}
                    @if ($manifest['km_post'])
                        <b class="manifest-km-post">- KM {{ $manifest['km_post'] }}</b>
                    @endif
                </span>
                <span class="manifest-type">{{ $manifest['passenger_type'] }}</span>
            </div>
        @endif
    </td>
@endif
