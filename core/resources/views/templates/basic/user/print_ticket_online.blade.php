@php
    use App\Constants\Status;
    use App\Services\TicketPassengerResolver;

    $payment = $ticket->payment_record;
    $isPaid = (int) ($payment?->status ?? Status::PAYMENT_INITIATE) === Status::PAYMENT_SUCCESS;
    $paymentStatus = $isPaid ? 'PAID' : strtoupper(strip_tags((string) ($payment?->statusString ?? 'PENDING')));
    $paymentStatusClass = $isPaid ? 'status-paid' : 'status-pending';
    $issuedAt = $payment?->completed_at ?: $payment?->updated_at ?: $ticket->created_at;
    $seats = collect($ticket->seats ?: collect($ticket->passenger_manifest ?: [])->pluck('seat'))
        ->map(fn ($seat) => trim((string) $seat))
        ->filter()
        ->values();
    $passengerResolver = app(TicketPassengerResolver::class);
    $accountName = trim((string) ($ticket->user?->fullname ?? ''));
    $passengers = $seats->map(function ($seat, $index) use ($ticket, $passengerResolver, $accountName, $seats) {
        $passenger = $passengerResolver->forSeat($ticket, $seat);
        $name = trim((string) ($passenger['name'] ?? ''));

        if ($name === '' || strcasecmp($name, 'Guest') === 0) {
            $name = $seats->count() === 1 && $accountName !== ''
                ? $accountName
                : 'Passenger ' . ($index + 1);
        }

        return [
            'name' => $name,
            'type' => $passenger['type'] ?? 'Regular',
            'seat' => formatSeatLabel($seat),
            'fare' => (float) ($passenger['entry']['fare'] ?? 0),
        ];
    });

    if ($passengers->isEmpty()) {
        $passengers = collect([[
            'name' => $accountName ?: 'Passenger',
            'type' => getPassengerType($payment),
            'seat' => '-',
            'fare' => 0,
        ]]);
    }

    $manifestDiscount = collect($ticket->passenger_manifest ?: [])->sum(
        fn ($passenger) => (float) ($passenger['discount_amount'] ?? 0)
    );
    $discountAmount = max((float) ($payment?->userDiscount?->amount ?? 0), $manifestDiscount);
    $fareAmount = (float) ($payment?->amount ?? $ticket->sub_total ?? 0);
    $paymentFee = (float) ($payment?->charge ?? 0);
    $totalPaid = (float) ($payment?->final_amount ?? max($fareAmount - $discountAmount + $paymentFee, 0));
    $gatewayName = trim((string) ($payment?->gateway?->name ?? ''));
    $paymentMethod = $gatewayName;
    if (strcasecmp($gatewayName, 'Paynamics') === 0 && $payment?->pchannel) {
        $paymentMethod = getPaynamicsPChannel($payment->pchannel, true) ?: $gatewayName;
    }
    $paymentMethod = $paymentMethod ?: 'Online Payment';
    $vehicle = $ticket->trip?->assignedVehicle?->vehicle;
    $vehicleNumber = collect([$vehicle?->bus_no, $vehicle?->register_no, $vehicle?->nick_name])
        ->map(fn ($value) => trim((string) $value))
        ->filter()
        ->unique()
        ->implode(' / ');
    $pickupCity = trim((string) ($ticket->pickup?->city ?? ''));
    $dropCity = trim((string) ($ticket->drop?->city ?? ''));
    $showPickupCity = $pickupCity !== '' && strcasecmp($pickupCity, (string) $ticket->pickup?->name) !== 0;
    $showDropCity = $dropCity !== '' && strcasecmp($dropCity, (string) $ticket->drop?->name) !== 0;
    $qr = base64_encode(QrCode::format('svg')->size(180)->margin(0)->generate($ticket->pnr_number));
    $logoPath = public_path('assets/images/logo_icon/logo.png');
    $logo = is_file($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
    $renderForPdf = $renderForPdf ?? false;
    $fontUrl = static fn ($filename) => asset('assets/fonts/montserrat/' . $filename);
    $money = static fn ($amount) => 'PHP ' . number_format((float) $amount, 2);
    $boardingReminders = collect([
        ['Validate first', 'Present this voucher and a valid ID at the Online Validation Window before boarding.'],
        ['Arrive early', 'Be at the terminal at least 45 minutes before departure, or 1 hour early with large baggage.'],
        ['Reservation validity', 'This booking is valid only for the date, schedule, and trip shown on this voucher.'],
        ['Discount IDs', 'Discounted passengers must present a valid ID and the required supporting documents.'],
        ['Refunds', 'Submit refund requests at least 24 hours before departure. Rebooked tickets are not refundable.'],
        ['Rebooking', 'Rebooking is allowed once when requested at least 1 day before departure, subject to policy.'],
    ]);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ticket->pnr_number }} - Online E-Voucher</title>
    <style>
        @page { size: A4 portrait; margin: 9mm; }
        @unless ($renderForPdf)
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 400;
            src: url('{{ $fontUrl('Montserrat-Regular.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 600;
            src: url('{{ $fontUrl('Montserrat-SemiBold.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 700;
            src: url('{{ $fontUrl('Montserrat-Bold.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 800;
            src: url('{{ $fontUrl('Montserrat-ExtraBold.ttf') }}') format('truetype');
        }
        @endunless
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            background: #eef1f5;
            color: #172033;
            font-family: 'Montserrat', DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            padding: 20px;
        }
        .voucher {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 7px 28px rgba(16, 24, 40, .12);
            margin: 0 auto;
            max-width: 820px;
            overflow: hidden;
        }
        .voucher-header {
            background: #c71968;
            color: #fff;
            padding: 15px 20px;
        }
        .voucher-header table, .voucher-summary table, .payment-layout { border-collapse: collapse; width: 100%; }
        .brand-cell { vertical-align: middle; }
        .brand-logo {
            background: #fff;
            border-radius: 50%;
            display: inline-block;
            height: 46px;
            margin-right: 10px;
            object-fit: contain;
            padding: 4px;
            vertical-align: middle;
            width: 46px;
        }
        .brand-copy { display: inline-block; vertical-align: middle; }
        .brand-name { font-size: 17px; font-weight: 800; line-height: 1.15; white-space: nowrap; }
        .brand-tagline { font-size: 8px; margin-top: 3px; opacity: .9; }
        .voucher-label { text-align: right; vertical-align: middle; }
        .voucher-label small { display: block; font-size: 8px; font-weight: 700; letter-spacing: 1.5px; }
        .voucher-label strong { display: block; font-size: 22px; line-height: 1.1; }
        .voucher-body { padding: 17px; }
        .voucher-summary {
            border: 1px solid #f1b7d1;
            border-radius: 8px;
            margin-bottom: 12px;
            padding: 12px;
            page-break-inside: avoid;
        }
        .voucher-summary td { padding: 4px 12px; vertical-align: middle; }
        .voucher-summary .reference-cell { border-right: 1px solid #f4d4e2; width: 38%; }
        .voucher-summary .qr-cell { border-right: 1px solid #f4d4e2; text-align: center; width: 24%; }
        .voucher-summary .status-cell { text-align: right; width: 38%; }
        .eyebrow { color: #8290a3; font-size: 8px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase; }
        .reference-number { color: #d91f73; font-size: 22px; font-weight: 800; letter-spacing: .7px; margin-top: 3px; }
        .qr-code { display: block; height: 108px; margin: 0 auto; width: 108px; }
        .qr-help { color: #718096; font-size: 7px; margin-top: 4px; }
        .status-badge {
            border: 1px solid;
            border-radius: 999px;
            display: inline-block;
            font-size: 10px;
            font-weight: 800;
            padding: 4px 10px;
        }
        .status-paid { background: #ecfdf3; border-color: #74dba3; color: #087a44; }
        .status-pending { background: #fff8e7; border-color: #f3c968; color: #986300; }
        .issued-label { color: #8994a6; font-size: 7px; font-weight: 700; margin-top: 10px; text-transform: uppercase; }
        .issued-value { font-size: 9px; font-weight: 700; margin-top: 2px; }
        .voucher-section {
            border: 1px solid #f2cedf;
            border-radius: 8px;
            margin-top: 11px;
            padding: 12px 14px;
            page-break-inside: avoid;
        }
        .section-title {
            color: #b9145d;
            font-size: 11px;
            font-weight: 800;
            margin: 0 0 10px;
            text-transform: uppercase;
        }
        .section-mark {
            background: #dc2777;
            border-radius: 4px;
            color: #fff;
            display: inline-block;
            font-size: 8px;
            margin-right: 7px;
            padding: 4px 6px;
            vertical-align: 1px;
        }
        .detail-grid { display: table; table-layout: fixed; width: 100%; }
        .detail-row { display: table-row; }
        .detail-item { display: table-cell; padding: 2px 12px 8px 0; vertical-align: top; width: 25%; }
        .detail-item:last-child { padding-right: 0; }
        .detail-item--wide { width: 50%; }
        .detail-label { color: #8994a6; font-size: 7px; font-weight: 700; text-transform: uppercase; }
        .detail-value { color: #111827; font-size: 12px; font-weight: 800; margin-top: 2px; }
        .detail-value--primary { color: #bd155f; font-size: 14px; }
        .detail-subvalue { color: #8490a1; font-size: 7px; margin-top: 1px; text-transform: uppercase; }
        .detail-divider { border-top: 1px solid #f0e1e8; margin: 2px 0 8px; }
        .passenger-table { border-collapse: collapse; width: 100%; }
        .passenger-table th {
            background: #fff8fb;
            border-bottom: 1px solid #f0d7e3;
            color: #8b96a7;
            font-size: 7px;
            padding: 7px 10px;
            text-align: left;
            text-transform: uppercase;
        }
        .passenger-table th:last-child, .passenger-table td:last-child { text-align: right; }
        .passenger-table td { border-bottom: 1px solid #f2e6ec; font-size: 10px; padding: 8px 10px; }
        .passenger-table tr:last-child td { border-bottom: 0; }
        .passenger-name { font-weight: 700; }
        .passenger-type { color: #8a95a5; display: block; font-size: 7px; margin-top: 1px; }
        .seat-badge {
            background: #fff4f9;
            border: 1px solid #efb6d0;
            border-radius: 4px;
            color: #c61966;
            display: inline-block;
            font-size: 10px;
            font-weight: 800;
            min-width: 34px;
            padding: 3px 7px;
            text-align: center;
        }
        .passenger-counts { border-collapse: separate; border-spacing: 8px 0; margin: 9px -8px 0; width: calc(100% + 16px); }
        .passenger-counts td { border: 1px solid #edbfd4; border-radius: 6px; padding: 7px 10px; width: 50%; }
        .count-label { color: #8994a6; font-size: 7px; text-transform: uppercase; }
        .count-value { color: #122037; font-size: 15px; font-weight: 800; }
        .payment-layout td { vertical-align: top; }
        .payment-totals { border-right: 1px solid #f0d7e3; padding-right: 16px; width: 53%; }
        .payment-meta { padding-left: 16px; width: 47%; }
        .money-row { width: 100%; }
        .money-row td { font-size: 9px; padding: 2px 0; }
        .money-row td:last-child { font-weight: 700; text-align: right; }
        .total-row { border: 1px solid #eeb9d2; border-radius: 5px; margin-top: 7px; padding: 7px 9px; }
        .total-row table { border-collapse: collapse; width: 100%; }
        .total-row td { font-size: 10px; font-weight: 800; }
        .total-row td:last-child { color: #d31f70; font-size: 16px; text-align: right; }
        .payment-meta-row { margin-bottom: 8px; }
        .payment-meta-row:last-child { margin-bottom: 0; }
        .payment-meta-label { color: #8994a6; font-size: 7px; text-transform: uppercase; }
        .payment-meta-value { font-size: 10px; font-weight: 800; overflow-wrap: anywhere; }
        .reminder-grid { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .reminder-grid td { color: #566174; font-size: 7px; line-height: 1.35; padding: 4px 9px 6px 0; vertical-align: top; width: 33.333%; }
        .reminder-grid td:last-child { padding-right: 0; }
        .reminder-title { color: #172033; display: block; font-size: 8px; font-weight: 800; margin-bottom: 2px; }
        .validation-note {
            background: #fff5fa;
            border-left: 3px solid #d41f70;
            color: #3f4b5e;
            font-size: 8px;
            margin-top: 9px;
            padding: 7px 9px;
        }
        .voucher-footer {
            background: #c71968;
            color: #fff;
            font-size: 8px;
            padding: 11px 18px;
            text-align: center;
        }
        .voucher-footer strong { font-weight: 800; }

        @media (max-width: 640px) {
            body { background: #fff; padding: 0; }
            .voucher { border-radius: 0; box-shadow: none; width: 100%; }
            .voucher-header { padding: 13px 14px; }
            .brand-logo { height: 38px; margin-right: 6px; width: 38px; }
            .brand-name { font-size: 12px; white-space: normal; }
            .voucher-label strong { font-size: 17px; }
            .voucher-body { padding: 10px; }
            .voucher-summary td { display: block; padding: 8px; text-align: center; width: 100% !important; }
            .voucher-summary .reference-cell, .voucher-summary .qr-cell { border-bottom: 1px solid #f4d4e2; border-right: 0; }
            .voucher-summary .status-cell { text-align: center; }
            .reference-number { font-size: 19px; }
            .voucher-section { padding: 11px; }
            .detail-grid, .detail-row { display: block; }
            .detail-item { display: inline-block; padding-right: 8px; width: 49%; }
            .detail-item--wide { width: 100%; }
            .payment-layout > tbody > tr > td { display: block; width: 100%; }
            .payment-totals { border-bottom: 1px solid #f0d7e3; border-right: 0; padding: 0 0 12px; }
            .payment-meta { padding: 12px 0 0; }
        }

        @media print {
            body { background: #fff; font-size: 8px; line-height: 1.22; padding: 0; }
            .voucher { border-radius: 0; box-shadow: none; max-width: none; width: 100%; }
            .voucher-header { padding: 9px 14px; }
            .brand-logo { height: 36px; margin-right: 7px; width: 36px; }
            .brand-name { font-size: 14px; }
            .voucher-label strong { font-size: 18px; }
            .voucher-body { padding: 8px 10px; }
            .voucher-summary { margin-bottom: 5px; padding: 5px; }
            .voucher-summary td { padding: 2px 8px; }
            .reference-number { font-size: 18px; }
            .qr-code { height: 78px; width: 78px; }
            .issued-label { margin-top: 6px; }
            .voucher-section { margin-top: 5px; padding: 6px 9px; }
            .section-title { font-size: 9px; margin-bottom: 5px; }
            .section-mark { font-size: 6px; margin-right: 5px; padding: 3px 5px; }
            .detail-item { padding-bottom: 4px; }
            .detail-value { font-size: 9px; }
            .detail-value--primary { font-size: 11px; }
            .detail-divider { margin-bottom: 5px; }
            .passenger-table th { padding: 4px 7px; }
            .passenger-table td { font-size: 8px; padding: 4px 7px; }
            .seat-badge { font-size: 8px; padding: 2px 6px; }
            .passenger-counts { margin-top: 5px; }
            .passenger-counts td { padding: 4px 8px; }
            .count-value { font-size: 12px; }
            .money-row td { font-size: 8px; padding: 1px 0; }
            .total-row { margin-top: 4px; padding: 4px 7px; }
            .total-row td { font-size: 8px; }
            .total-row td:last-child { font-size: 12px; }
            .payment-meta-row { margin-bottom: 4px; }
            .payment-meta-value { font-size: 8px; }
            .reminder-grid td { font-size: 6px; line-height: 1.2; padding: 2px 7px 3px 0; }
            .reminder-title { font-size: 7px; margin-bottom: 1px; }
            .validation-note { font-size: 7px; margin-top: 5px; padding: 4px 7px; }
            .voucher-footer { font-size: 7px; padding: 6px 12px; }
        }
    </style>
</head>
<body>
    <main class="voucher">
        <header class="voucher-header">
            <table>
                <tr>
                    <td class="brand-cell">
                        @if ($logo)
                            <img class="brand-logo" src="{{ $logo }}" alt="GV Florida Transport logo">
                        @endif
                        <span class="brand-copy">
                            <span class="brand-name">GV FLORIDA TRANSPORT, INC.</span>
                            <span class="brand-tagline">Your Journey. Our Priority.</span>
                        </span>
                    </td>
                    <td class="voucher-label">
                        <small>ONLINE BOOKING</small>
                        <strong>E-VOUCHER</strong>
                    </td>
                </tr>
            </table>
        </header>

        <div class="voucher-body">
            <section class="voucher-summary">
                <table>
                    <tr>
                        <td class="reference-cell">
                            <div class="eyebrow">Booking Reference / PNR</div>
                            <div class="reference-number">{{ $ticket->pnr_number }}</div>
                        </td>
                        <td class="qr-cell">
                            <img class="qr-code" src="data:image/svg+xml;base64,{{ $qr }}" alt="Booking QR code">
                            <div class="qr-help">Scan for terminal validation</div>
                        </td>
                        <td class="status-cell">
                            <span class="status-badge {{ $paymentStatusClass }}">{{ $paymentStatus }}</span>
                            <div class="issued-label">Issued</div>
                            <div class="issued-value">{{ showDateTime($issuedAt, 'M d, Y, h:i A') }}</div>
                        </td>
                    </tr>
                </table>
            </section>

            <section class="voucher-section">
                <h2 class="section-title"><span class="section-mark">01</span>Trip Information</h2>
                <div class="detail-grid">
                    <div class="detail-row">
                        <div class="detail-item">
                            <div class="detail-label">Departure Date</div>
                            <div class="detail-value detail-value--primary">{{ showDateTime($ticket->date_of_journey, 'D, M d, Y') }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Departure Time</div>
                            <div class="detail-value detail-value--primary">{{ date('h:i A', strtotime($ticket->trip->schedule->start_from)) }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Origin</div>
                            <div class="detail-value">{{ $ticket->pickup?->name ?: $ticket->trip?->startFrom?->name ?: '-' }}</div>
                            @if ($showPickupCity)<div class="detail-subvalue">{{ $pickupCity }}</div>@endif
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Destination</div>
                            <div class="detail-value">{{ $ticket->drop?->name ?: $ticket->trip?->endTo?->name ?: '-' }}</div>
                            @if ($showDropCity)<div class="detail-subvalue">{{ $dropCity }}</div>@endif
                        </div>
                    </div>
                </div>
                <div class="detail-divider"></div>
                <div class="detail-grid">
                    <div class="detail-row">
                        <div class="detail-item detail-item--wide">
                            <div class="detail-label">Bus Class</div>
                            <div class="detail-value">{{ $ticket->trip?->fleetType?->name ?: $ticket->trip?->class ?: '-' }}</div>
                        </div>
                        <div class="detail-item detail-item--wide">
                            <div class="detail-label">Trip / Bus No.</div>
                            <div class="detail-value">{{ $vehicleNumber ?: ($ticket->trip?->route?->name ?: $ticket->trip?->title ?: '-') }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="voucher-section">
                <h2 class="section-title"><span class="section-mark">02</span>Passenger &amp; Seat Details</h2>
                <table class="passenger-table">
                    <thead>
                        <tr><th>Passenger Name</th><th>Seat No.</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($passengers as $passenger)
                            <tr>
                                <td>
                                    <span class="passenger-name">{{ $passenger['name'] }}</span>
                                    <span class="passenger-type">{{ $passenger['type'] }}</span>
                                </td>
                                <td><span class="seat-badge">{{ $passenger['seat'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <table class="passenger-counts">
                    <tr>
                        <td><div class="count-label">Total Passengers</div><div class="count-value">{{ $passengers->count() }}</div></td>
                        <td><div class="count-label">Total Seats</div><div class="count-value">{{ $seats->count() ?: $passengers->count() }}</div></td>
                    </tr>
                </table>
            </section>

            <section class="voucher-section">
                <h2 class="section-title"><span class="section-mark">03</span>Payment Details</h2>
                <table class="payment-layout">
                    <tr>
                        <td class="payment-totals">
                            <table class="money-row">
                                <tr><td>Fare</td><td>{{ $money($fareAmount) }}</td></tr>
                                <tr><td>Discount</td><td>-{{ $money($discountAmount) }}</td></tr>
                                <tr><td>Payment fee</td><td>{{ $money($paymentFee) }}</td></tr>
                            </table>
                            <div class="total-row">
                                <table><tr><td>{{ $isPaid ? 'Total Paid' : 'Total Amount' }}</td><td>{{ $money($totalPaid) }}</td></tr></table>
                            </div>
                        </td>
                        <td class="payment-meta">
                            <div class="payment-meta-row">
                                <div class="payment-meta-label">Payment Method</div>
                                <div class="payment-meta-value">{{ $paymentMethod }}</div>
                            </div>
                            <div class="payment-meta-row">
                                <div class="payment-meta-label">Payment Status</div>
                                <div><span class="status-badge {{ $paymentStatusClass }}">{{ $paymentStatus }}</span></div>
                            </div>
                            <div class="payment-meta-row">
                                <div class="payment-meta-label">Transaction Reference</div>
                                <div class="payment-meta-value">{{ $payment?->trx ?: '-' }}</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </section>

            <section class="voucher-section">
                <h2 class="section-title"><span class="section-mark">04</span>Before You Board</h2>
                <table class="reminder-grid">
                    @foreach ($boardingReminders->chunk(3) as $reminderRow)
                        <tr>
                            @foreach ($reminderRow as $reminder)
                                <td><span class="reminder-title">{{ $reminder[0] }}</span>{{ $reminder[1] }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>
                <div class="validation-note"><strong>Validation required:</strong> This e-voucher confirms the online reservation but is not the official boarding ticket.</div>
            </section>
        </div>

        <footer class="voucher-footer">
            <strong>This e-voucher is not an official ticket or receipt.</strong> Present it with a valid ID for online validation before boarding.
        </footer>
    </main>
</body>
</html>
