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
            $manifest && $manifest['blocked'] ? 'blocked' : null,
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
                <span class="manifest-seat-status">{{ $manifest['blocked'] ? 'Blocked' : 'Occupied' }}</span>
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
        @elseif ($manifest)
            <div class="manifest-passenger">
                <span class="manifest-reference">No. {{ $manifest['reference'] }}</span>
                <span class="manifest-passenger-name">{{ $manifest['passenger_name'] }}</span>
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
