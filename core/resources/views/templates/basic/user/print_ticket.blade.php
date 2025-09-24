<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $ticket->pnr_number }}</title>
    <style>
        @page {
            margin: 20px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 13px;
            margin: 0;
            padding: 0;
        }

        .page {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
        }

        .ticket-wrapper {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .08);
            padding: 25px;
        }

        .ticket-inner {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
        }

        .ticket-header {
            text-align: center;
            margin-bottom: 15px;
        }

        .ticket-logo img {
            width: 90px;
            margin-bottom: 8px;
        }

        .ticket-header h4 {
            font-size: 18px;
            margin: 4px 0;
            color: #222;
        }

        .ticket-header p {
            font-size: 13px;
            color: #666;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        td {
            padding: 4px 6px;
            vertical-align: top;
        }

        .title {
            font-weight: bold;
            color: #444;
            font-size: 13px;
        }

        .value {
            font-weight: bold;
            color: #444;
            font-size: 13px;
        }

        .status-paid {
            background: #28a745;
            color: #fff;
            font-size: 12px;
            padding: 3px 6px;
            border-radius: 3px;
            display: inline-block;
        }

        .qr {
            text-align: center;
            margin-top: 15px;
        }

        /* Avoid cutting content across pages */
        .ticket-wrapper,
        .ticket-inner {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="ticket-wrapper">
            <div class="ticket-inner">
                <div class="ticket-header">
                    <div class="ticket-logo">
                        <img src="https://gvflorida-stg.makopa.tech/assets/images/logo_icon/logo.png" alt="Logo">
                    </div>
                    <h4>{{ __(@$ticket->trip->assignedVehicle->vehicle->nick_name) }}</h4>
                    <p>@lang('E-Ticket / Reservation Voucher')</p>
                </div>

                <table>
                    <tr>
                        <td class="title">@lang('PNR Number')</td>
                        <td>:</td>
                        <td class="value">{{ __($ticket->pnr_number) }}</td>
                    </tr>
                    @if ($ticket->user)
                        <tr>
                            <td class="title">@lang('Name')</td>
                            <td>:</td>
                            <td class="value">{{ __($ticket->user->fullname) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="title">@lang('Journey Date')</td>
                        <td>:</td>
                        <td class="value">{{ showDateTime($ticket->date_of_journey, 'F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="title">@lang('Journey Day')</td>
                        <td>:</td>
                        <td class="value">{{ showDateTime($ticket->date_of_journey, 'l') }}</td>
                    </tr>
                    <tr>
                        <td class="title">@lang('Total Seats')</td>
                        <td>:</td>
                        <td class="value">{{ sizeof($ticket->seats) }}</td>
                    </tr>
                    <tr>
                        <td class="title">@lang('Seat Numbers')</td>
                        <td>:</td>
                        <td class="value">{{ implode(',', $ticket->seats) }}</td>
                    </tr>
                    <tr>
                        <td class="title">@lang('Total Amount')</td>
                        <td>:</td>
                        <td class="value">{{ number_format($ticket->deposit->amount, 2) }} PHP</td>
                    </tr>
                    <tr>
                        <td class="title">@lang('Payment Method')</td>
                        <td>:</td>
                        <td class="value">
                            @if ($ticket->deposit->gateway->name == 'Paynamics')
                                {{ getPaynamicsPChannel($ticket->deposit->pchannel, true) }}
                            @else
                                {{ $ticket->deposit->gateway->name }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="title">@lang('Payment Status')</td>
                        <td>:</td>
                        <td><span class="status-paid">{!! paymentStatus($ticket->deposit->status) !!}</span></td>
                    </tr>
                </table>

                <div class="qr">
                    @php
                        $url = route('admin.vehicle.ticket.search', [
                            'scope' => 'list',
                            'search' => $ticket->pnr_number,
                        ]);

                        // Generate QR as SVG (no Imagick required)
                        $qr = base64_encode(QrCode::format('svg')->size(130)->generate($url));
                    @endphp
                    <img src="data:image/svg+xml;base64,{{ $qr }}" width="130" height="130"
                        alt="QR Code">
                </div>
            </div>
        </div>
    </div>
</body>

</html>
