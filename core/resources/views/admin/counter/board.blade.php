<!doctype html>
<html lang="en">

@php
    use App\Constants\Status;
@endphp

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bus Schedule Board</title>
    <link rel="shortcut icon" type="image/png" href="{{ siteFavicon() }}">

    <!-- Bootstrap 5 -->
    <link href="{{ asset('assets/global/css/bootstrap.min.css') }}" rel="stylesheet">

    <style>
        body {
            background: #f7f8fa;
        }

        .board-card {
            border: 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .06);
            border-radius: 1rem;
        }

        .table> :not(caption)>*>* {
            vertical-align: middle;
        }

        .status-dot {
            display: inline-block;
            width: .6rem;
            height: .6rem;
            border-radius: 50%;
            margin-right: .4rem;
        }

        .status-on_time {
            background: #28a745;
        }

        .status-delayed {
            background: #ffc107;
        }

        .status-boarding {
            background: #0d6efd;
        }

        .status-cancelled {
            background: #dc3545;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-light bg-white border-bottom sticky-top">
        <div class="container">
            <img width="80px" src="{{ siteLogo('dark') }}" alt="image">
            <span class="navbar-brand fw-semibold"> Bus Schedule
                Board</span>
        </div>
    </nav>

    <main class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
                    <div>
                        <h1 class="h3 mb-1">Today’s Departures</h1>
                    </div>
                </div>

                <div class="card board-card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="scheduleTable" class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Trip</th>
                                        <th scope="col">Bus type</th>
                                        <th scope="col">Destination</th>
                                        <th scope="col" class="text-center">Available seats</th>
                                        <th scope="col">Departure time</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data as $item)
                                        <tr>
                                            <td>{{ $item->title }}</td>
                                            <td>{{ $item->fleetType->name }}</td>
                                            <td>{{ $item->route->startFrom->city }} → {{ $item->route->endTo->city }}
                                            </td>
                                            <td class="text-center">{{ $item->available_seats }}</td>
                                            <td>{{ date('h:i A', strtotime($item->schedule->start_from)) }}</td>
                                            <td>
                                                @php
                                                    if (
                                                        strtotime($item->schedule->start_from) <
                                                        strtotime(date('H:i:s'))
                                                    ) {
                                                        $item->trip_status = 'delayed';
                                                    }
                                                @endphp
                                                {!! generateTripStatusHTML($item->trip_status) !!}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer bg-white d-flex flex-wrap gap-2">
                        <span class="small text-secondary">Legend:</span>
                        <span class="small"><span
                                class="status-dot status-{{ Status::TRIP_ON_TIME }}"></span>{{ decodeSlug(Status::TRIP_ON_TIME) }}</span>
                        <span class="small"><span
                                class="status-dot status-{{ Status::TRIP_BOARDING }}"></span>{{ decodeSlug(Status::TRIP_BOARDING) }}</span>
                        <span class="small"><span
                                class="status-dot status-{{ Status::TRIP_DELAYED }}"></span>{{ decodeSlug(Status::TRIP_DELAYED) }}</span>
                        <span class="small"><span
                                class="status-dot status-{{ Status::TRIP_CANCELLED }}"></span>{{ decodeSlug(Status::TRIP_CANCELLED) }}</span>
                    </div>
                </div>

                <p class="text-center text-muted mt-4 small">
                    Last updated: <span id="lastUpdated"></span>
                </p>
            </div>
        </div>
    </main>

    <script>
        // Timestamp
        document.getElementById('lastUpdated').textContent =
            new Date().toLocaleString(undefined, {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
    </script>
</body>

</html>
