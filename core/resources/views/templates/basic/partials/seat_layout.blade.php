@php
    $seatLayout ??= app(\App\Services\SeatLayoutService::class)->layout($fleetType);
@endphp

@once
    <link rel="stylesheet" href="{{ asset('assets/global/css/shared-seat-layout.css?v=' . buildVer()) }}">
@endonce

@include('shared.seat_layout', [
    'seatLayout' => $seatLayout,
    'seatLayoutMode' => $seatLayoutMode ?? 'selection',
    'lockedSeatDetails' => $lockedSeatDetails ?? collect(),
])
