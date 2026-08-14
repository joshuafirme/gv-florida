<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Travel Manifest</title>
    <style>
        @page {
            margin: 7mm;
            size: legal portrait;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #000;
            margin: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 8px;
        }

        h2 {
            font-size: 15px;
            margin: 0;
            padding: 0;
        }

        .header p {
            margin: 3px 0 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #333;
            overflow-wrap: anywhere;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
            word-break: break-word;
        }

        th {
            background: #f2f2f2;
            font-size: 6.5px;
            line-height: 1.15;
        }

        .badge {
            display: inline-block;
            padding: 2px 3px;
            border-radius: 3px;
            font-size: 6.5px;
            font-weight: bold;
        }

        th:nth-child(1) { width: 10%; }
        th:nth-child(2) { width: 10%; }
        th:nth-child(3) { width: 12%; }
        th:nth-child(4) { width: 14%; }
        th:nth-child(5) { width: 12%; }
        th:nth-child(6) { width: 7%; }
        th:nth-child(7) { width: 10%; }
        th:nth-child(8) { width: 8%; }
        th:nth-child(9) { width: 10%; }
        th:nth-child(10) { width: 7%; }

        .badge--success {
            background-color: #28a745;
            color: #fff;
        }

        .badge--warning {
            background-color: #ffc107;
            color: #000;
        }

        .badge--danger {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Travel Manifest</h2>
        <p>Date: {{ now()->format('d M Y, h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>@lang('PNR number')</th>
                <th>@lang('Trip')</th>
                <th>@lang('Bus type')</th>
                <th>@lang('Route')</th>
                <th>@lang('Passenger')</th>
                <th>@lang('Seat No.')</th>
                <th>@lang('Booking date')</th>
                <th>@lang('Departure')</th>
                <th>@lang('Payment channel')</th>
                <th>@lang('Status')</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $item)
                <tr>
                    <td>{{ __($item->pnr_number) }}</td>
                    <td>{{ __($item->trip->title) }}</td>
                    <td>{{ __($item->trip->fleetType->name) }}</td>
                    <td>{{ __($item->pickup->name) }} -> {{ __($item->drop->name) }}</td>
                    <td>{{ __(@$item->user->firstname) }} {{ __(@$item->user->lastname) }}</td>
                    <td>{{ formatSeatLabel($item->seats) }}</td>
                    <td>{{ __(showDateTime($item->date_of_journey, 'd M, Y')) }}</td>
                    <td>{{ date('h:i A', strtotime($item->trip->schedule->start_from)) }}</td>
                    <td>
                        @if (@$item->deposit->gateway->name == 'Paynamics')
                            {{ __(getPaynamicsPChannel(@$item->deposit->pchannel, true)) }}
                        @else
                            {{ __(@$item->deposit->gateway->name) }}
                        @endif
                    </td>
                    <td>
                        @if ($item->status == 1)
                            <span class="badge badge--success">@lang('Booked')</span>
                        @elseif($item->status == 2)
                            <span class="badge badge--warning">@lang('Pending')</span>
                        @else
                            <span class="badge badge--danger">@lang('Rejected')</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-muted text-center" colspan="10">
                        {{ __($emptyMessage) }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
