<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Travel Manifest</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        h2 {
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background: #f2f2f2;
        }

        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }

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
                    <td>{{ __(implode(',', $item->seats)) }}</td>
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
