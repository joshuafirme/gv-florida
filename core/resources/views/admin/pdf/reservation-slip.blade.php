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

        .slip-container {}

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

        .terms {
            font-size: 7px;
            line-height: 1.3em;
        }

        .terms li {
            margin-bottom: 4px;
            color: red;
        }

        .line-input {
            border-bottom: 1px solid black;
            display: inline-block;
            width: 80px;
            height: 12px;
        }

        .signature-line {
            border-bottom: 1px solid black;
            width: 100px;
            height: 18px;
            margin-top: 30px;
        }

        .small-label {
            font-size: 8px;
        }

        .number-box {
            font-size: 20px;
            color: red;
            font-weight: bold;
        }

        .m-box {
            font-weight: bold;
            font-size: 14px;
            margin-left: 10px;
        }

        @page {
            margin: 8px;
        }
    </style>
</head>

<body>

    <div class="slip-container">

        <div class="slip-title">G.V. FLORIDA TRANSPORT, INC.</div>
        <div class="slip-subtitle">Allacapan, Cagayan</div>

        <div class="section-title">RESERVATION SLIP</div>

        <div class="terms">
            <strong>TERMS & CONDITIONS:</strong>
            <div>{!! isset($content->terms_and_conditions) ? $content->terms_and_conditions : '' !!}</div>
        </div>

        <div class="mt-3 small-label">
            Destination <span class="line-input">{{ $ticket->drop->name }}</span> Km. Post
        </div>

        <div class="mt-2 small-label">
            Seat No. <span class="line-input" style="width:60px">
                @foreach ($ticket->seats as $seat)
                    {{ $seat }} &nbsp;
                @endforeach
            </span>
        </div>

        <div class="mt-2 small-label">
            Date <span class="line-input" style="width:60px">
                {{ $ticket->date_of_journey }}
        </div>

        <div class="mt-2 small-label">
            Fare <span class="line-input"
                style="width:80px">{{ number_format($ticket->deposit->final_amount, 2) }}</span> (Php)
        </div>

        <div class="mt-2 small-label">
            No. of Pass <span class="line-input" style="width:100px">
                {{ array_sum($ticket->trip->fleetType->deck_seats) }}
            </span>
        </div>

        <div class="signature-line mt-4"></div>
        <div class="small-label">Authorized Signature</div>

        <div class="mt-4 small-label">
            TIME <span class="line-input" style="width:90px">{{ date('h:i A', strtotime($ticket->trip->schedule->start_from)) }}</span>
        </div>
{{-- 
        <div class="d-flex align-items-center mt-4">
            <span class="small-label">No. :</span>
            <span class="number-box ms-2">237951</span>
            <span class="m-box">M</span>
        </div> --}}

    </div>

</body>

</html>
