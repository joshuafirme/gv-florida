<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservation Slip</title>
    <style>
        @page {
            margin: 7px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #121c2b;
            font-family: "DejaVu Sans", Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .slip {
            font-size: 7px;
            line-height: 1.25;
            width: 100%;
        }

        .company-name {
            font-size: 10px;
            font-weight: 800;
            line-height: 1.1;
            margin: 0;
            text-align: center;
            text-transform: uppercase;
        }

        .company-address {
            color: #4b5360;
            font-size: 6px;
            margin-top: 2px;
            text-align: center;
            text-transform: uppercase;
        }

        .pnr {
            margin: 7px 0 6px;
            text-align: center;
        }

        .pnr-label,
        .reference-label {
            color: #69717e;
            display: block;
            font-size: 5.5px;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .pnr-value {
            display: block;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .04em;
            margin-top: 1px;
        }

        .document-title {
            border-bottom: .7px solid #202936;
            border-top: .7px solid #202936;
            font-size: 9.5px;
            font-weight: 800;
            line-height: 1;
            padding: 4px 0 3px;
            text-align: center;
        }

        .section {
            margin-top: 7px;
        }

        .section-title {
            border-bottom: .5px solid #cfd3d9;
            color: #bf2e6b;
            font-size: 6.5px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 3px;
            padding-bottom: 2px;
            text-transform: uppercase;
        }

        .details {
            border-collapse: collapse;
            width: 100%;
        }

        .details td {
            line-height: 1.2;
            padding: 1.2px 0;
            vertical-align: top;
        }

        .details .label {
            color: #59616d;
            width: 46%;
        }

        .details .value {
            font-weight: 500;
            text-align: right;
            width: 54%;
        }

        .details .value.strong {
            font-weight: 800;
        }

        .fare {
            border-bottom: .8px solid #202936;
            border-top: .8px solid #202936;
            margin-top: 7px;
            padding: 4px 0;
        }

        .fare table {
            border-collapse: collapse;
            width: 100%;
        }

        .fare-label {
            font-size: 9px;
            font-weight: 800;
        }

        .fare-value {
            font-size: 11px;
            font-weight: 800;
            text-align: right;
        }

        .terms {
            margin-top: 7px;
        }

        .terms-title {
            color: #3f4754;
            font-size: 5.8px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .terms-content,
        .terms-content * {
            color: #555d68 !important;
            font-family: "DejaVu Sans", Arial, sans-serif !important;
            font-size: 5px !important;
            line-height: 1.25 !important;
        }

        .terms-content {
            margin-top: 2px;
        }

        .terms-content p,
        .terms-content div {
            margin: 0 0 2px !important;
        }

        .terms-content ol,
        .terms-content ul {
            margin: 2px 0 0 !important;
            padding-left: 12px !important;
        }

        .terms-content li {
            margin-bottom: 1.5px !important;
            padding-left: 1px !important;
        }

        .signature {
            margin: 10px auto 0;
            text-align: center;
            width: 90%;
        }

        .signature-name {
            font-size: 6.5px;
            min-height: 9px;
        }

        .signature-line {
            border-top: .7px solid #202936;
            font-size: 5.5px;
            padding-top: 2px;
        }

        .reference {
            margin-top: 9px;
            text-align: center;
        }

        .reference-value {
            display: block;
            font-size: 18px;
            font-weight: 800;
            line-height: 1;
            margin-top: 2px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    @php
        $payment = $ticket->deposit;
        $isPendingPayment = (int) ($payment?->status) === \App\Constants\Status::PAYMENT_PENDING;
        $isPaid = (int) ($payment?->status) === \App\Constants\Status::PAYMENT_SUCCESS;
        $displaySlips = $isPendingPayment
            ? collect($ticket->seats ?: [])->map(fn ($seat) => (object) ['seat' => $seat, 'id' => null])
            : $ticket->activeSlipSeriesNumbers;
        $slipCount = max($displaySlips->count(), $ticket->slipSeriesNumbers->count(), 1);
        $fallbackFare = (float) ($ticket->unit_price
            ?: (($payment?->final_amount ?? $ticket->sub_total) / $slipCount));
        $manifest = collect($ticket->passenger_manifest ?: ($payment?->userDiscount?->passenger_manifest ?: []))
            ->keyBy(fn ($passenger) => (string) ($passenger['seat'] ?? ''));
        $bookingSource = $ticket->kiosk_id ? 'Kiosk' : ($ticket->user_id ? 'Online' : 'Counter');
        $paymentChannel = $payment?->pchannel
            ? (getPaynamicsPChannel($payment->pchannel, true) ?: readPaymentChannel($payment->pchannel))
            : null;
        $paymentMethod = strtolower((string) $paymentChannel) === 'cash'
            ? 'Cash'
            : ($paymentChannel ?: ($payment?->gateway?->name ?: ($payment?->methodName() ?: 'Cash')));
        $paymentStatus = match ((int) ($payment?->status)) {
            \App\Constants\Status::PAYMENT_SUCCESS => 'PAID',
            \App\Constants\Status::PAYMENT_PENDING => 'PENDING',
            \App\Constants\Status::PAYMENT_REJECT => 'REJECTED',
            \App\Constants\Status::PAYMENT_EXPIRED => 'EXPIRED',
            default => 'INITIATED',
        };
        $companyName = trim((string) ($content->heading ?? '')) ?: 'GV FLORIDA TRANSPORT, INC.';
        $companyAddress = trim((string) ($content->subheading ?? '')) ?: ($ticket->pickup?->name ?? '');
        $terms = trim((string) ($content->terms_and_conditions ?? ''));
    @endphp

    @foreach ($displaySlips as $slipSeries)
        @php
            $passenger = $manifest->get((string) $slipSeries->seat, []);
            $fare = (float) ($passenger['fare'] ?? $fallbackFare);
            $passengerName = trim((string) ($passenger['name'] ?? '')) ?: 'Guest';
            $passengerType = ($passenger['passenger_type'] ?? 'regular') === 'discounted'
                ? ($passenger['discount_name'] ?? 'Discounted')
                : 'Regular';
            $authorizedBy = $isPendingPayment
                ? ''
                : ($payment?->processed_by_name ?: auth('admin')->user()?->name);
        @endphp

        <main class="slip">
            <h1 class="company-name">{{ $companyName }}</h1>
            <div class="company-address">{{ $companyAddress }}</div>

            <div class="pnr">
                <span class="pnr-label">PNR</span>
                <span class="pnr-value">{{ $ticket->pnr_number }}</span>
            </div>

            <div class="document-title">RESERVATION SLIP</div>

            <section class="section">
                <div class="section-title">Trip Details</div>
                <table class="details">
                    <tr><td class="label">Destination</td><td class="value strong">{{ $ticket->drop?->name ?: '-' }}</td></tr>
                    <tr><td class="label">Drop-off Point</td><td class="value strong">{{ $ticket->drop?->km_post ? 'KM ' . $ticket->drop->km_post : '-' }}</td></tr>
                    <tr><td class="label">Departure Date</td><td class="value">{{ date('M j, Y', strtotime($ticket->date_of_journey)) }}</td></tr>
                    <tr><td class="label">Departure Time</td><td class="value">{{ date('g:i A', strtotime($ticket->trip->schedule->start_from)) }}</td></tr>
                    <tr><td class="label">Bus Type</td><td class="value">{{ $ticket->trip?->fleetType?->name ?: '-' }}</td></tr>
                </table>
            </section>

            <section class="section">
                <div class="section-title">Passenger &amp; Payment</div>
                <table class="details">
                    <tr><td class="label">Passenger</td><td class="value">{{ $passengerName }}</td></tr>
                    <tr><td class="label">Type</td><td class="value strong">{{ $passengerType }}</td></tr>
                    <tr><td class="label">Seat No.</td><td class="value strong">{{ formatSeatLabel($slipSeries->seat) }}</td></tr>
                    <tr><td class="label">Source</td><td class="value">{{ $bookingSource }}</td></tr>
                    <tr><td class="label">Mode of Payment</td><td class="value">{{ $paymentMethod }}</td></tr>
                    <tr><td class="label">Status</td><td class="value strong">{{ $paymentStatus }}</td></tr>
                </table>
            </section>

            <section class="fare">
                <table>
                    <tr>
                        <td class="fare-label">FARE</td>
                        <td class="fare-value">{{ number_format($fare, 2) }} PHP</td>
                    </tr>
                </table>
            </section>

            @if ($terms !== '')
                <section class="terms">
                    <div class="terms-title">Terms &amp; Conditions</div>
                    <div class="terms-content">{!! $terms !!}</div>
                </section>
            @endif

            <section class="signature">
                <div class="signature-name">{{ $authorizedBy }}</div>
                <div class="signature-line">Cashier / Authorized Signature</div>
            </section>

            <section class="reference">
                <span class="reference-label">Reference No.</span>
                <span class="reference-value">{{ $isPaid ? 'No. ' . $slipSeries->id : '' }}</span>
            </section>
        </main>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>
