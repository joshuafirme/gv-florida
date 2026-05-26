<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservation Slip</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            padding: 0px;
            margin: 0 font-family: Arial, sans-serif;
        }

        .slip-container {
            font-size: 7px;
        }

        .slip-title {
            font-weight: bold;
            text-align: center;
            font-size: 11px;
            margin-bottom: 5px;
        }

        .slip-subtitle {
            text-align: center;
            font-size: 10px;
            margin-bottom: 15px;
        }

        .section-title {
            font-weight: bold;
            text-align: center;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .terms font,
        .terms pre,
        .terms p,
        .terms span,
        .terms div,
        .terms li {
            font-size: 8px !important;
        }

        .terms {
            font-size: 8px !important;
        }


        .ticket {
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        td {
            padding: 2px 0;
        }

        /* Key fix */
        .label {
            font-weight: bold;
            width: 50%;
        }

        .value {
            width: 50%;
            text-align: right;
        }

        .section-gap td {
            padding-top: 10px;
        }

        .divider {
            border-top: 2px solid #000;
            text-align: center;
            position: relative;
        }

        .divider span {
            position: relative;
            background: #fff;
            padding: 0 10px;
        }

        .ticket-no {
            font-size: 18px;
            text-align: center;
            margin-top: 10px;
        }


        @page {
            margin: 8px;
        }
    </style>
</head>

<body>
    @php
        $fare = $ticket->deposit->final_amount / count($ticket->seats);
    @endphp
    @foreach ($ticket->slipSeriesNumbers as $slip_series)
        <div class="slip-container">

            <div class="slip-title">{{ isset($content->heading) ? $content->heading : '' }}</div>
            <div class="slip-subtitle">{{ isset($content->subheading) ? $content->subheading : '' }}</div>

            <div class="section-title">RESERVATION SLIP</div>

            <div class="terms">
                <strong>TERMS & CONDITIONS:</strong>
                <div style="line-height: 1;">{!! isset($content->terms_and_conditions) ? $content->terms_and_conditions : '' !!}</div>
            </div>

            <div class="ticket">
                <table>
                    <tr>
                        <td class="label">PNR Number:</td>
                        <td class="value">{{ $ticket->pnr_number }}</td>
                    </tr>
                    <tr>
                        <td class="label">Destination:</td>
                        <td class="value">{{ $ticket->drop->name }}</td>
                    </tr>

                    <tr>
                        <td class="label">KM Post:</td>
                        <td class="value">{{ $ticket->trip?->route?->distance }}</td>
                    </tr>

                    <tr>
                        <td class="label">Departure Date:</td>
                        <td class="value">
                            {{ date('M. d, Y', strtotime($ticket->date_of_journey)) }}
                        </td>
                    </tr>

                    <tr>
                        <td class="label">Departure Time:</td>
                        <td class="value">
                            {{ date('h:i A', strtotime($ticket->trip->schedule->start_from)) }}
                        </td>
                    </tr>

                    <tr class="section-gap">
                        <td class="label">Fare:</td>
                        <td class="value">
                            {{ number_format($fare, 2) }} PHP
                        </td>
                    </tr>

                    <tr>
                        <td class="label">Seat No.:</td>
                        <td class="value">
                            {{ $slip_series->seat }}
                        </td>
                    </tr>

                    <tr class="section-gap">
                        <td class="label">Type of Passenger:</td>
                        <td class="value">
                            {{ $ticket->deposit?->userDiscount?->description ?: 'Regular' }}
                        </td>
                    </tr>

                    <tr>
                        <td class="label">Bus Type:</td>
                        <td class="value">{{ $ticket->trip?->fleetType?->name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Source</td>
                        <td class="value">{{ $ticket->deposit?->pchannel ?: 'Kiosk' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Mode of Payment</td>
                        <td class="value">{{ strtoupper(decodeSlug($ticket->deposit?->pchannel)) ?: 'CASH' }}</td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            <div style="text-align: center; margin-top: 10px;">{{ $ticket->deposit->processed_by_name }}</div>
                            <div class="divider">
                                <span>Authorized Signature</span>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2" class="ticket-no">
                            <strong>No.: {{ $slip_series->id }}</strong>
                        </td>
                    </tr>
                </table>
            </div>

        </div>
        @if (!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach


</body>

</html>
