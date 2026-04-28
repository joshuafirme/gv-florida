<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $ticket->pnr_number }}</title>

    <style>
        @page {
            margin: 10px;
        }

        @font-face {
            font-family: 'Poppins';
            src: url('/fonts/Poppins-Regular.woff2') format('woff2');
            font-weight: 400;
        }


        body {
            font-family: "Roboto", sans-serif;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .voucher {
            width: 100%;
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .logo-area {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .logo-area img {
            width: 55px;
            height: auto;
        }

        .company h2 {
            margin: 0;
            font-size: 16px;
            letter-spacing: 1px;
        }

        .company p {
            margin: 2px 0 0;
            font-size: 10px;
            font-style: italic;
        }

        .right-header {
            text-align: right;
            font-size: 10px;
        }

        .right-header .pnr {
            font-weight: bold;
            font-size: 11px;
        }

        .paid {
            color: red;
            font-weight: bold;
            margin-top: 5px;
        }

        /* SECTION TITLE */
        .section-title {
            background: #e6e6e6;
            padding: 5px;
            font-weight: bold;
            margin-top: 8px;
        }

        /* TABLE STYLE */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .table td {
            padding: 3px 4px;
            vertical-align: top;
        }

        .label {
            width: 40%;
            color: #333;
            font-weight: 700;
        }

        .value {
            font-weight: 300;
        }

        /* TRIP GRID */
        .grid {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .grid .col {
            flex: 1;
            min-width: 0;
            /* IMPORTANT: prevents overflow breaking layout */
        }

        /* QR + SIDE */
        .mid-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .qr-box {
            text-align: center;
            width: 120px;
        }

        .qr-box img {
            width: 90px;
            height: 90px;
        }

        /* INFO */
        .info {
            font-size: 10px;
            line-height: 14px;
            margin-top: 5px;
        }

        /* IMPORTANT BOX */
        .notice {
            border: 2px solid #000;
            padding: 8px;
            margin-top: 10px;
        }

        .notice-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>

    <div class="voucher">

        <div style="text-align: center">
            <i style="font-size:10px; margin-bottom: 15px;">This is your e-voucher. Please print it and present it at the
                cashier for verification.</i>
        </div>
        <!-- HEADER -->
        <div class="header">

            <table style="width:100%; border-collapse:collapse; font-size: 12px;">
                <tr>

                    <!-- LEFT: LOGO + COMPANY -->
                    <td style="vertical-align:middle;">
                        <table>
                            <tr>
                                <td style="vertical-align:middle;">
                                    <img src="{{ env('APP_URL') }}assets/images/logo_icon/logo.png"
                                        style="width:85px; height:auto;">
                                </td>
                                <td style="vertical-align:middle; padding-left:10px;">
                                    <div class="company">
                                        <h2 style="margin:0; font-size:18px; letter-spacing:1px;">
                                            FLORIDA TRANSPORT INC.
                                        </h2>
                                        <p style="margin:2px 0 0; font-size:12px; font-style:italic;">
                                            Your Journey, Our Priority
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>

                    <!-- RIGHT: PNR -->
                    <td style="text-align:right; vertical-align:top; font-size:12px;">
                        <div>PNR</div>
                        <div style="font-weight: 700">{{ $ticket->pnr_number }}</div>

                        <div style="margin-top:5px;">
                            DATE ISSUED<br>
                            {{ showDateTime($ticket->created_at, 'M d, Y | h:i A') }}
                        </div>

                        <div style="color:red; font-weight:bold; margin-top:5px;">
                            Voucher PAID
                        </div>
                    </td>

                </tr>
            </table>

        </div>

        <!-- PAYMENT -->
        <div class="section-title">Payment Details</div>

        <table style="width:100%; border-collapse:collapse; font-size: 12px;">
            <tr>

                <!-- LEFT: PAYMENT INFO -->
                <td style="vertical-align:top;">

                    <table class="table">
                        <tr>
                            <td class="label">Mode of Payment :</td>
                            <td >
                                @if ($ticket->deposit->gateway->name == 'Paynamics')
                                    {{ getPaynamicsPChannel($ticket->deposit->pchannel, true) }}
                                @else
                                    {{ $ticket->deposit->gateway->name }}
                                @endif
                            </td>
                        </tr>
                    </table>

                </td>

                <!-- RIGHT: QR CODE -->
                <td style="width:140px; text-align:center; vertical-align:middle;">

                    @php
                        $qr = base64_encode(QrCode::format('svg')->size(120)->generate($ticket->pnr_number));
                    @endphp

                    <img src="data:image/svg+xml;base64,{{ $qr }}"
                        style="width:90px; height:90px; margin-top:10px;;" alt="QR">

                    <div style="font-size:9px; margin-top:5px;">
                        Show this QR code at the cashier.
                    </div>

                </td>

            </tr>
        </table>

        <!-- MID QR + RIGHT INFO -->
        <div class="mid-section">

            <div style="flex:1;">

                <!-- TRIP -->
                <div class="section-title">Trip Details</div>

                <div class="grid" style="font-size: 12px;">

                    <div class="col">
                        <table class="table">
                            <tr>
                                <td class="label">Starting Point:</td>
                                <td>{{ $ticket->pickup->name }}</td>
                            </tr>
                            <tr>
                                <td class="label">Destination:</td>
                                <td>{{ $ticket->drop->name }}</td>
                            </tr>
                            <tr>
                                <td class="label">Date:</td>
                                <td>{{ showDateTime($ticket->date_of_journey, 'M d, Y') }}</td>
                            </tr>
                            <tr>
                                <td class="label">Departure Time:</td>
                                <td>{{ date('h:i A', strtotime($ticket->trip->schedule->start_from)) }}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="col">
                        <table class="table">
                            <tr>
                                <td class="label">Class:</td>
                                <td>{{ $ticket->trip->class ?? 'Executive Sleeper' }}</td>
                            </tr>
                        </table>
                    </div>

                </div>

                <!-- PASSENGERS -->
                <div class="section-title">Passengers</div>

                <table class="table" style=" font-size: 12px;">
                    @foreach ($ticket->passengers ?? [$ticket->user] as $p)
                        <tr>
                            <td>{{ $p->fullname ?? $p->name }}</td>
                            <td style="text-align:right;">{{ implode(',', $ticket->seats) }}</td>
                        </tr>
                    @endforeach
                </table>

            </div>

            {{-- <!-- QR -->
        <div class="qr-box">
            @php
                $qr = base64_encode(QrCode::format('svg')->size(120)->generate($ticket->pnr_number));
            @endphp

            <img src="data:image/svg+xml;base64,{{ $qr }}" alt="QR">
            <div style="font-size:9px; margin-top:5px;">
                Show this QR code at the cashier.
            </div>
        </div> --}}

        </div>

        <!-- INFO -->
        <div class="section-title">Information</div>

        <div class="info">
            • This e-voucher is not an official ticket. Proceed to cashier for validation.<br>
            • Arrive at least 45 minutes before departure.<br>
            • Valid only for the indicated schedule.<br>
            • Failure to arrive may forfeit reservation.<br>
            • Refunds are subject to company policy.<br>
            • Lost vouchers will not be honored.
        </div>

        <!-- IMPORTANT NOTICE -->
        <div class="notice" style="font-size: 12px; text-align: center; margin-top: 30px;">
            <div class="notice-title">IMPORTANT NOTICE</div>
            <div style="font-weight: 700">This e-voucher is NOT an official ticket or receipt.</div><br>
            Present this voucher or QR code at the cashier for verification.<br>
            Official ticket will be issued before boarding.
        </div>

    </div>

</body>

</html>
