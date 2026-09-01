<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Travel Manifest - {{ $trip->startFrom->name }} to {{ $trip->endTo->name }}</title>
    <style>
        @page { margin: 0; size: legal portrait; }
        * { box-sizing: border-box; }
        html, body { color: #17202d; font-family: DejaVu Sans, sans-serif; margin: 0; padding: 0; }
        body { font-size: 7px; padding: 10mm; }
        .manifest-page { background: #fff; margin: 0; padding: 0; width: 100%; }
        .manifest-header { border-bottom: .8pt solid #253044; padding-bottom: 4px; text-align: center; }
        .manifest-header h1 { font-size: 14px; font-weight: 800; margin: 0; }
        .manifest-header p { font-size: 8px; margin: 2px 0 0; text-transform: uppercase; }
        .manifest-info, .manifest-stats, .manifest-seat-table, .manifest-centered-table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .manifest-info { margin-top: 5px; }
        .manifest-info td { border: .65pt solid #9da7b3; padding: 3px 5px; vertical-align: top; }
        .manifest-label { color: #687282; display: block; font-size: 6px; text-transform: uppercase; }
        .manifest-info strong { display: block; font-size: 8px; margin-top: 1px; }
        .manifest-stats { margin: 4px 0 5px; }
        .manifest-stats td { border: .65pt solid #9da7b3; font-size: 6.5px; font-weight: 700; padding: 2px 3px; text-align: center; }
        .manifest-stats .capacity { color: #c7135c; }
        .manifest-stats .booked { background: #edfff4; color: #087a39; }
        .manifest-stats .blocked { background: #f2f6fa; color: #38566d; }
        .manifest-stats .locked { background: #fff7e7; color: #955400; }
        .manifest-stats .discounted { background: #fff8e9; color: #9a6200; }
        .manifest-search-note { border: .65pt solid #d76796; color: #8f1749; font-size: 6.5px; margin-bottom: 4px; padding: 3px 5px; }
        .manifest-decks { page-break-inside: avoid; width: 100%; }
        .manifest-deck { border: .8pt solid #6f7a88; margin: 0 0 4px; page-break-inside: avoid; width: 100%; }
        .manifest-deck:last-child { margin-bottom: 0; }
        .manifest-deck-title { background: #1d2939; border-bottom: .8pt solid #1d2939; color: #fff; font-size: 8px; font-weight: 700; padding: 3px 5px; text-transform: uppercase; }
        .manifest-seat-table td, .manifest-centered-table td { border: .65pt solid #8d98a6; overflow: hidden; padding: 3px 4px; vertical-align: top; }
        .manifest-seat-row { page-break-inside: avoid; }
        .manifest-seat { background: #fff; }
        .manifest-seat--empty { background: #fafbfc; }
        .manifest-seat-top { border-bottom: .4pt solid #c6ccd4; min-height: 10px; padding-bottom: 1px; width: 100%; }
        .manifest-seat-number { color: #9ea7b2; float: left; font-size: 11px; font-weight: 800; line-height: 1; }
        .manifest-seat-status { color: #7f8995; float: right; font-size: 5px; font-style: italic; font-weight: 700; text-transform: uppercase; }
        .manifest-seat.occupied .manifest-seat-number { color: #0f1825; }
        .manifest-seat.blocked { background: #fafbfc; }
        .manifest-seat.admin-locked { background: #fff8e8; border-left: 2pt solid #d99419; }
        .manifest-seat.admin-locked .manifest-seat-number, .manifest-seat.admin-locked .manifest-seat-status { color: #8b5100; }
        .manifest-seat.disabled { background: #eceff2; color: #7f8995; }
        .manifest-seat.comfort-room { background: #eef3f7; text-align: center; }
        .manifest-seat.comfort-room .manifest-seat-number { color: #526474; }
        .manifest-cr-fill { color: #526474; font-size: 11px; font-weight: 800; padding-top: 4px; text-align: center; }
        .manifest-aisle { background: #fff; border-left: 1.25pt solid #7f8995 !important; border-right: 1.25pt solid #7f8995 !important; padding: 0 !important; width: 9mm; }
        .manifest-aisle-line { border-left: .65pt dashed #8d98a6; margin-left: 50%; }
        .manifest-passenger { clear: both; padding-top: 2px; }
        .manifest-reference { color: #df1768; display: block; font-size: 10px; font-weight: 800; line-height: 1.05; }
        .manifest-passenger-name { display: block; font-size: 6.5px; font-weight: 800; margin-top: 1px; }
        .manifest-passenger-id { color: #5f6875; display: block; font-size: 5.5px; font-weight: 700; margin-top: 1px; }
        .manifest-passenger-dropoff { display: block; font-size: 6.5px; font-weight: 800; margin-top: 1px; text-transform: uppercase; }
        .manifest-km-post { font-size: 8px; white-space: nowrap; }
        .manifest-type { background: #fff7df; border: .5pt solid #d7ae3e; color: #8a5900; display: inline-block; font-size: 5px; font-weight: 700; margin-top: 1px; padding: 1px 2px; text-transform: uppercase; }
        .manifest-lock-details { clear: both; color: #744500; padding-top: 3px; }
        .manifest-lock-details strong { display: block; font-size: 6px; text-transform: uppercase; }
        .manifest-lock-details span { display: block; font-size: 5px; margin-top: 1px; }
        .manifest-centered-wrap { padding: 0 !important; text-align: center; }
        .manifest-centered-table { margin: 0 auto; }
        .manifest-page--dense .manifest-reference { font-size: 8px; }
        .manifest-page--dense .manifest-passenger-name, .manifest-page--dense .manifest-passenger-dropoff { font-size: 5.5px; }
        .manifest-page--dense .manifest-passenger-id { font-size: 5px; }
        .manifest-page--dense .manifest-km-post { font-size: 7px; }
        .manifest-page--compact .manifest-seat-table td, .manifest-page--compact .manifest-centered-table td { padding: 2px; }
        .manifest-page--compact .manifest-seat-number { font-size: 8px; }
        .manifest-page--compact .manifest-seat-status { font-size: 4px; }
        .manifest-page--compact .manifest-reference { font-size: 7px; }
        .manifest-page--compact .manifest-passenger-name, .manifest-page--compact .manifest-passenger-dropoff { font-size: 4.5px; }
        .manifest-page--compact .manifest-passenger-id { font-size: 4px; margin-top: 0; }
        .manifest-page--compact .manifest-km-post { font-size: 6px; }
    </style>
</head>

<body>
    @php
        $rowHeightMm = (float) $manifestPrint['row_height_mm'];
        $groupCount = count($manifestLayout['groups']);
        $pdfColumnCount = $manifestLayout['seats_per_row'] + max($groupCount - 1, 0);
    @endphp
    <main class="manifest-page manifest-page--{{ $manifestPrint['density'] }}">
        <header class="manifest-header">
            <h1>GV FLORIDA TRANSPORT INC.</h1>
            <p>{{ $trip->fleetType->name }} - Travel Manifest</p>
        </header>

        <table class="manifest-info">
            <tr>
                <td style="width: 31%;"><span class="manifest-label">Route</span><strong>{{ $trip->startFrom->name }} - {{ $trip->endTo->name }}</strong></td>
                <td style="width: 19%;"><span class="manifest-label">Departure</span><strong>{{ date('g:i A', strtotime($trip->schedule->start_from)) }}</strong></td>
                <td style="width: 31%;"><span class="manifest-label">Date</span><strong>{{ \Carbon\Carbon::parse($date)->format('l, F d, Y') }}</strong></td>
                <td style="width: 19%;"><span class="manifest-label">Distance</span><strong>{{ $trip->route->distance ?: '-' }}</strong></td>
            </tr>
        </table>

        <table class="manifest-stats">
            <tr>
                <td class="capacity">Capacity: {{ $stats['capacity'] }}</td>
                <td class="booked">Booked: {{ $stats['booked'] }}</td>
                <td class="blocked">Pending/Held: {{ $stats['blocked'] }}</td>
                <td class="locked">Admin Locked: {{ $stats['locked'] }}</td>
                <td>Available: {{ $stats['vacant'] }}</td>
                <td class="discounted">SC/PWD: {{ $stats['discounted'] }}</td>
            </tr>
        </table>

        @if ($search !== '')
            <div class="manifest-search-note">
                Showing {{ $stats['matches'] }} occupied-seat match(es) for "{{ $search }}".
            </div>
        @endif

        <div class="manifest-decks">
            @foreach ($manifestLayout['decks'] as $deck)
                <section class="manifest-deck">
                    <div class="manifest-deck-title">{{ $deck['name'] }}</div>
                    <table class="manifest-seat-table">
                        <tbody>
                            @foreach ($deck['rows'] as $row)
                                @if ($row['centered'])
                                    @php
                                        $centerCells = collect($row['groups'])->flatMap(fn (array $group) => $group['cells'])->all();
                                        $centerWidth = min(
                                            max((count($centerCells) / max($manifestLayout['seats_per_row'], 1)) * 100, 1),
                                            100
                                        );
                                    @endphp
                                    <tr class="manifest-seat-row" style="height: {{ number_format($rowHeightMm, 2, '.', '') }}mm;">
                                        <td class="manifest-centered-wrap" colspan="{{ $pdfColumnCount }}">
                                            <table class="manifest-centered-table" style="width: {{ number_format($centerWidth, 2, '.', '') }}%;">
                                                <tr style="height: {{ number_format($rowHeightMm, 2, '.', '') }}mm;">
                                                    @foreach ($centerCells as $cell)
                                                        @include('admin.pdf.partials.manifest-seat-cell', compact('cell', 'seatManifest', 'lockedSeats', 'rowHeightMm'))
                                                    @endforeach
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @else
                                    <tr class="manifest-seat-row" style="height: {{ number_format($rowHeightMm, 2, '.', '') }}mm;">
                                        @foreach ($row['groups'] as $group)
                                            @if (!$loop->first)
                                                <td class="manifest-aisle" aria-hidden="true">
                                                    <div class="manifest-aisle-line" style="height: {{ number_format(max($rowHeightMm - 1, 1), 2, '.', '') }}mm;"></div>
                                                </td>
                                            @endif
                                            @foreach ($group['cells'] as $cell)
                                                @include('admin.pdf.partials.manifest-seat-cell', compact('cell', 'seatManifest', 'lockedSeats', 'rowHeightMm'))
                                            @endforeach
                                        @endforeach
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </section>
            @endforeach
        </div>
    </main>
</body>

</html>
