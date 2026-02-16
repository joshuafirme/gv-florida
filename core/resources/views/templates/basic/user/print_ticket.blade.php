<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $ticket->pnr_number }}</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 10px;
            margin: 0 auto;
            padding: 5px; /* ONLY 5px padding */
            width: 114px;
            line-height: 17px;
        }

        .ticket-wrapper {
            width: 100%;
        }

        .ticket-header {
            text-align: center;
            margin-bottom: 6px;
        }

        .ticket-logo img {
            width: 60px;
            margin-bottom: 4px;
        }

        .ticket-header h4 {
            font-size: 12px;
            margin: 2px 0;
        }

        .ticket-header p {
            font-size: 10px;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            word-wrap: break-word;
        }

        td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 10px;
        }

        .title {
            font-weight: bold;
        }

        .value {
            word-break: break-word;
        }

      

        .qr {
            text-align: center;
            margin-top: 6px;
        }

        .qr img {
            width: 50px;
            height: 50px;
        }

        .ticket-wrapper {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    <div class="ticket-wrapper">

        <div class="ticket-header">
            <div class="ticket-logo">
                <img src="{{env('APP_URL')}}assets/admin/images/GV.png" alt="Logo">
            </div>
            <h4>{{ __(@$ticket->trip->assignedVehicle->vehicle->nick_name) }}</h4>
            <p>@lang('E-Ticket / Reservation Voucher')</p>
        </div>

        <table>
            <tr>
                <td class="title">PNR</td>
                <td class="value">{{ $ticket->pnr_number }}</td>
            </tr>

            @if ($ticket->user)
            <tr>
                <td class="title">Name</td>
                <td class="value">{{ $ticket->user->fullname }}</td>
            </tr>
            @endif

            <tr>
                <td class="title">Date</td>
                <td class="value">{{ showDateTime($ticket->date_of_journey, 'M d, Y') }}</td>
            </tr>

            <tr>
                <td class="title">Seats</td>
                <td class="value">{{ implode(',', $ticket->seats) }}</td>
            </tr>

            <tr>
                <td class="title">Amount</td>
                <td class="value">{{ number_format($ticket->deposit->amount, 2) }} PHP</td>
            </tr>

            <tr>
                <td class="title">Method</td>
                <td class="value">
                    @if ($ticket->deposit->gateway->name == 'Paynamics')
                        {{ getPaynamicsPChannel($ticket->deposit->pchannel, true) }}
                    @else
                        {{ $ticket->deposit->gateway->name }}
                    @endif
                </td>
            </tr>

            <tr>
                <td class="title">Status</td>
                <td><span class="status-paid">{!! paymentStatus($ticket->deposit->status) !!}</span></td>
            </tr>
        </table>

        <div class="qr">
            @php
                $qr = base64_encode(QrCode::format('svg')->size(100)->generate($ticket->pnr_number));
            @endphp
            <img src="data:image/svg+xml;base64,{{ $qr }}" alt="QR Code">
        </div>

    </div>
</body>

<script></script>

</html>
