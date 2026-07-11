<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Cancellation Acknowledgment</title>
    <style>
        @page { margin: 8px; }
        body { color: #111; font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; margin: 0; }
        .title { font-size: 11px; font-weight: bold; text-align: center; }
        .subtitle { font-size: 9px; margin: 3px 0 10px; text-align: center; }
        .section { border-top: 1px dashed #222; margin-top: 8px; padding-top: 8px; }
        table { border-collapse: collapse; width: 100%; }
        td { padding: 2px 0; vertical-align: top; }
        .label { font-weight: bold; width: 45%; }
        .value { text-align: right; width: 55%; }
        .notice { border: 1px solid #111; font-size: 8px; line-height: 1.35; margin-top: 10px; padding: 6px; text-align: center; }
        .reason { line-height: 1.35; text-align: right; }
    </style>
</head>

<body>
    @php
        $ticket = $cancellation->bookedTicket;
        $slip = $cancellation->slipSeriesNumber;
        $passenger = $ticket->deposit?->userDiscount?->passenger_name ?: ($ticket->user?->fullname ?: 'Guest');
        $payment = '-';
        if ($ticket?->deposit && $ticket->deposit->pchannel) {
            $payment = readPaymentChannel($ticket->deposit->pchannel);
        } elseif ($ticket?->deposit) {
            $payment = $ticket->deposit->gatewayCurrency()->name;
        }
    @endphp

    <div class="title">GV FLORIDA TRANSPORT INC.</div>
    <div class="subtitle">CANCELLATION ACKNOWLEDGMENT</div>

    <table>
        <tr><td class="label">PNR</td><td class="value">{{ $ticket->pnr_number }}</td></tr>
        <tr><td class="label">Ticket / Ref.</td><td class="value">{{ $slip->id }}</td></tr>
        <tr><td class="label">Passenger</td><td class="value">{{ $passenger }}</td></tr>
        <tr><td class="label">Passenger Type</td><td class="value">{{ getPassengerType($ticket->deposit) }}</td></tr>
        <tr><td class="label">Seat</td><td class="value">{{ $slip->seat }}</td></tr>
        <tr><td class="label">Fare Forfeited</td><td class="value">{{ showAmount($cancellation->original_fare) }}</td></tr>
    </table>

    <div class="section">
        <table>
            <tr><td class="label">Trip</td><td class="value">{{ $ticket->pickup->name }} via {{ $ticket->drop->name }}</td></tr>
            <tr><td class="label">Bus Type</td><td class="value">{{ $ticket->trip->fleetType->name }}</td></tr>
            <tr><td class="label">Travel Date</td><td class="value">{{ showDateTime($ticket->date_of_journey, 'M d, Y') }}</td></tr>
            <tr><td class="label">Departure</td><td class="value">{{ date('h:i A', strtotime($ticket->trip->schedule->start_from)) }}</td></tr>
            <tr><td class="label">Payment</td><td class="value">{{ $payment }}</td></tr>
        </table>
    </div>

    <div class="section">
        <table>
            <tr><td class="label">Reason</td><td class="value reason">{{ $cancellation->reason }}</td></tr>
            <tr><td class="label">Remarks</td><td class="value reason">{{ $cancellation->remarks }}</td></tr>
            <tr><td class="label">Processed By</td><td class="value">{{ $cancellation->processedBy->name }}</td></tr>
            <tr><td class="label">Authorized By</td><td class="value">{{ $cancellation->authorizedBy->name }}</td></tr>
            <tr><td class="label">Cancelled At</td><td class="value">{{ showDateTime($cancellation->created_at) }}</td></tr>
        </table>
    </div>

    <div class="notice">
        This paid ticket was cancelled with no money returned. The seat was released immediately.
    </div>
</body>

</html>
