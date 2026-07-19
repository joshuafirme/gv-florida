<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trip Manifest - {{ $trip->startFrom->name }} to {{ $trip->endTo->name }}</title>
    <link rel="stylesheet" href="{{ asset('assets/global/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/all.min.css') }}">
    <style>
        :root { --pink: #df1768; --navy: #1d2939; --line: #dfe3e8; --muted: #7a8290; }
        * { box-sizing: border-box; }
        body { background: #f1f3f6; color: #17202d; font-family: Arial, sans-serif; margin: 0; }
        .manifest-toolbar { align-items: center; background: #fff; border-bottom: 1px solid #e1e4e8; display: flex; gap: 12px; justify-content: space-between; padding: 12px 22px; position: sticky; top: 0; z-index: 5; }
        .manifest-toolbar a { color: #17202d; text-decoration: none; }
        .manifest-filters { align-items: end; display: flex; flex-wrap: wrap; gap: 10px; }
        .manifest-filters label { color: #626a76; display: block; font-size: 11px; margin-bottom: 3px; text-transform: uppercase; }
        .manifest-filters input { border: 1px solid #d4d8de; border-radius: 7px; height: 40px; padding: 8px 10px; }
        .manifest-btn { background: var(--pink); border: 0; border-radius: 7px; color: #fff; cursor: pointer; font-weight: 600; height: 40px; padding: 0 16px; }
        .manifest-page { background: #fff; border-radius: 14px; margin: 24px auto; max-width: 1120px; min-height: 900px; padding: 36px; }
        .manifest-header { border-bottom: 1px solid #253044; padding-bottom: 22px; text-align: center; }
        .manifest-header h1 { font-size: 25px; font-weight: 800; letter-spacing: .02em; margin: 0; }
        .manifest-header p { font-size: 14px; margin: 8px 0 0; text-transform: uppercase; }
        .manifest-info { display: grid; gap: 18px; grid-template-columns: 1.2fr 1fr 1fr .8fr; padding: 24px 0 14px; }
        .manifest-label { color: var(--muted); display: block; font-size: 10px; letter-spacing: .05em; text-transform: uppercase; }
        .manifest-info strong { display: block; font-size: 14px; margin-top: 5px; }
        .manifest-stats { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 24px; }
        .manifest-stat { border: 1px solid #d9dde3; border-radius: 20px; font-size: 11px; padding: 5px 11px; }
        .manifest-stat.capacity { border-color: #ef8db7; color: #d41462; }
        .manifest-stat.booked { background: #edfff4; border-color: #a9e5bf; color: #07813a; }
        .manifest-stat.blocked { background: #f2f6fa; border-color: #bdccd9; color: #38566d; }
        .manifest-stat.discounted { background: #fff8e9; border-color: #f1ca72; color: #a66405; }
        .manifest-search-note { background: #fff5f9; border: 1px solid #f1a2c3; border-radius: 8px; color: #9d1751; font-size: 12px; margin-bottom: 18px; padding: 10px 12px; }
        .manifest-deck { border: 1px solid var(--line); border-radius: 10px; margin-bottom: 18px; overflow: hidden; }
        .manifest-deck-title { background: var(--navy); color: #fff; font-size: 13px; font-weight: 700; padding: 9px 14px; text-transform: uppercase; }
        .manifest-seat-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .manifest-seat { border-bottom: 1px solid var(--line); min-height: 108px; padding: 10px 14px; position: relative; }
        .manifest-seat:nth-child(odd) { border-right: 1px solid var(--line); }
        .manifest-seat-number { color: #bdc4ce; font-size: 24px; font-weight: 800; line-height: 1; }
        .manifest-seat-status { color: #a2a8b0; float: right; font-size: 10px; font-style: italic; font-weight: 700; text-transform: uppercase; }
        .manifest-seat.occupied .manifest-seat-number { color: #0f1825; }
        .manifest-seat.blocked { background: #fafbfc; }
        .manifest-seat.disabled { background: #f2f3f5; color: #9ba2ad; }
        .manifest-seat.comfort-room { background: #eef3f7; }
        .manifest-seat.comfort-room .manifest-seat-number { color: #526474; }
        .manifest-passenger { align-items: start; display: grid; gap: 12px; grid-template-columns: minmax(0, 1fr) minmax(155px, .9fr); margin-top: 7px; }
        .manifest-reference { color: var(--pink); display: block; font-size: 28px; font-weight: 800; line-height: 1.05; }
        .manifest-passenger-name { display: block; font-size: 14px; font-weight: 800; margin-top: 4px; }
        .manifest-passenger-dropoff { color: #111; font-size: 18px; font-weight: 800; line-height: 1.12; text-align: right; text-transform: uppercase; }
        .manifest-km-post { font-size: 23px; white-space: nowrap; }
        .manifest-type { background: #fff7df; border: 1px solid #efc75a; border-radius: 5px; color: #9a6500; display: inline-block; font-size: 9px; font-weight: 700; margin-top: 7px; padding: 3px 7px; text-transform: uppercase; }
        .manifest-seat.filtered { opacity: .18; }
        @media (max-width: 700px) { .manifest-toolbar { align-items: flex-start; flex-direction: column; } .manifest-page { border-radius: 0; margin: 0; padding: 22px 14px; } .manifest-info { grid-template-columns: repeat(2, 1fr); } .manifest-seat-grid { grid-template-columns: 1fr; } .manifest-seat:nth-child(odd) { border-right: 0; } .manifest-passenger { grid-template-columns: minmax(0, 1fr) minmax(130px, .8fr); } .manifest-reference { font-size: 23px; } .manifest-passenger-dropoff { font-size: 15px; } .manifest-km-post { font-size: 18px; } }
        @media print {
            body { background: #fff; }
            .manifest-toolbar, .manifest-search-note { display: none !important; }
            .manifest-page { border-radius: 0; margin: 0; max-width: none; min-height: 0; padding: 0; }
            .manifest-header { padding-bottom: 9px; }
            .manifest-header h1 { font-size: 18px; }
            .manifest-header p { font-size: 11px; margin-top: 4px; }
            .manifest-info { gap: 10px; padding: 10px 0 8px; }
            .manifest-info strong { font-size: 11px; margin-top: 2px; }
            .manifest-stats { gap: 6px; margin-bottom: 9px; }
            .manifest-stat { font-size: 8px; padding: 3px 7px; }
            .manifest-deck { margin-bottom: 8px; }
            .manifest-deck-title { font-size: 9px; padding: 5px 9px; }
            .manifest-seat { break-inside: avoid; min-height: 76px; padding: 6px 9px; }
            .manifest-seat-number { font-size: 17px; }
            .manifest-seat-status { font-size: 7px; }
            .manifest-passenger { gap: 8px; grid-template-columns: minmax(0, 1fr) minmax(115px, .85fr); margin-top: 4px; }
            .manifest-reference { font-size: 20px; }
            .manifest-passenger-name { font-size: 10px; margin-top: 2px; }
            .manifest-passenger-dropoff { font-size: 13px; }
            .manifest-km-post { font-size: 17px; }
            .manifest-type { font-size: 7px; margin-top: 3px; padding: 2px 5px; }
            .manifest-seat.filtered { opacity: 1; }
            @page { margin: 8mm; size: legal portrait; }
        }
    </style>
</head>

<body>
    <div class="manifest-toolbar">
        <a href="{{ route('admin.trip.list') }}">&larr; Back to Trips</a>
        <form class="manifest-filters" method="GET">
            <div>
                <label for="manifestSearch">Search manifest</label>
                <input id="manifestSearch" type="search" name="search" value="{{ $search }}"
                    placeholder="Passenger, seat, reference">
            </div>
            <div>
                <label for="manifestDate">Travel date</label>
                <input id="manifestDate" type="date" name="date_of_journey" value="{{ $date }}" required>
            </div>
            <button class="manifest-btn" type="submit"><i class="fas fa-search"></i> View</button>
            <button class="manifest-btn" type="button" onclick="window.print()"><i class="fas fa-print"></i> Print Manifest</button>
        </form>
    </div>

    <main class="manifest-page">
        <header class="manifest-header">
            <h1>GV FLORIDA TRANSPORT INC.</h1>
            <p>{{ $trip->fleetType->name }} — Travel Manifest</p>
        </header>

        <section class="manifest-info">
            <div><span class="manifest-label">Route</span><strong>{{ $trip->startFrom->name }}–{{ $trip->endTo->name }}</strong></div>
            <div><span class="manifest-label">Departure</span><strong>{{ date('g:i A', strtotime($trip->schedule->start_from)) }}</strong></div>
            <div><span class="manifest-label">Date</span><strong>{{ \Carbon\Carbon::parse($date)->format('l, F d, Y') }}</strong></div>
            <div><span class="manifest-label">Distance</span><strong>{{ $trip->route->distance ?: '—' }}</strong></div>
        </section>

        <section class="manifest-stats">
            <span class="manifest-stat capacity">Capacity: {{ $stats['capacity'] }}</span>
            <span class="manifest-stat booked">Booked: {{ $stats['booked'] }}</span>
            <span class="manifest-stat blocked">Blocked: {{ $stats['blocked'] }}</span>
            <span class="manifest-stat">Vacant: {{ $stats['vacant'] }}</span>
            <span class="manifest-stat discounted">SC/PWD: {{ $stats['discounted'] }}</span>
        </section>

        @if ($search !== '')
            <div class="manifest-search-note">
                Showing {{ $stats['matches'] }} occupied-seat match(es) for “{{ $search }}”. Other occupied seats are dimmed.
            </div>
        @endif

        @foreach ($manifestDecks as $deckIndex => $cells)
            <section class="manifest-deck">
                <div class="manifest-deck-title">{{ $loop->first ? 'Lower Deck' : 'Upper Deck' }}</div>
                <div class="manifest-seat-grid">
                    @foreach ($cells as $cell)
                        @php
                            $isComfortRoom = $cell['type'] === 'cr';
                            $manifest = $isComfortRoom ? null : $seatManifest->get($cell['seat_id']);
                            $isDisabled = !$isComfortRoom && in_array($cell['label'], $disabledSeats, true);
                        @endphp
                        <article class="manifest-seat {{ $isComfortRoom ? 'comfort-room' : '' }} {{ $manifest ? 'occupied' : '' }} {{ $manifest && $manifest['blocked'] ? 'blocked' : '' }} {{ $isDisabled ? 'disabled' : '' }} {{ $manifest && !$manifest['matches'] ? 'filtered' : '' }}">
                            <span class="manifest-seat-number">{{ $cell['label'] }}</span>
                            @if ($isComfortRoom)
                                <span class="manifest-seat-status">Comfort Room</span>
                            @elseif ($isDisabled)
                                <span class="manifest-seat-status">Unavailable</span>
                            @elseif ($manifest)
                                <span class="manifest-seat-status">{{ $manifest['blocked'] ? 'Blocked' : 'Occupied' }}</span>
                                <div class="manifest-passenger">
                                    <div>
                                        <span class="manifest-reference">No. {{ $manifest['reference'] }}</span>
                                        <span class="manifest-passenger-name">{{ $manifest['passenger_name'] }}</span>
                                    </div>
                                    <div class="manifest-passenger-dropoff">
                                        <div>
                                            {{ $manifest['destination'] ?: '-' }}
                                            @if ($manifest['km_post'])
                                                <span class="manifest-km-post">- KM {{ $manifest['km_post'] }}</span>
                                            @endif
                                        </div>
                                        <span class="manifest-type">{{ $manifest['passenger_type'] }}</span>
                                    </div>
                                </div>
                            @else
                                <span class="manifest-seat-status">Vacant</span>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </main>
</body>

</html>
