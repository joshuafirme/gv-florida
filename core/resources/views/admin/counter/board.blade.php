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

        .clock-widget {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 500;
            color: #333;
            gap: 0.5rem;
        }

        .clock-icon {
            font-size: 1.8rem;
            color: #0d6efd;
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

            <div class="clock-widget justify-content-center">
                <span id="clock">--:-- --</span>
            </div>
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
                                        <th scope="col">Route</th>
                                        <th scope="col" class="text-center">Available seats</th>
                                        <th scope="col">Departure time</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>

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
    </main>

    <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>

    <script>
        scheduleBoard()

        setInterval(() => {
            scheduleBoard()
        }, 5000);

        var last_updated = null;

        function tickLastUpdate() {
            document.getElementById('lastUpdated').textContent =
                new Date().toLocaleString(undefined, {
                    year: 'numeric',
                    month: 'short',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
        }


        function scheduleBoard() {
            if (!last_updated) {
                tickLastUpdate()
            }
            const id = window.location.pathname.split('/').filter(Boolean).pop();
            $.ajax({
                url: "{{ env('APP_URL') }}" + 'admin/counter/schedule-board/json/' + id,
                type: 'GET',
                data: {
                    '_token': "{{ csrf_token() }}"
                },
                success: function(data) {
                    if (last_updated && last_updated != data.last_updated) {
                        console.log('tick!')
                        tickLastUpdate()
                    }
                    last_updated = data.last_updated;
                    $('#scheduleTable tbody').empty()
                    let html = '';
                    for (const item of data.res) {

                        if (isAfterNow(item.schedule.start_from) &&
                            item.trip_status == '{{ Status::TRIP_ON_TIME }}') {
                            item.trip_status = '{{ Status::TRIP_DELAYED }}';
                        }

                        html += `
                    <tr>
                        <td>${item.title}</td>
                        <td>${item.fleet_type.name}</td>
                        <td>${item.route.start_from.city} → ${item.route.end_to.city}
                        </td>
                        <td class="text-center">${item.available_seats}</td>
                        <td>${formatTime(item.schedule.start_from)}</td>
                        <td>${generateTripStatusHTML(item.trip_status)}</td>
                    </tr>
                    `
                    }
                    $('#scheduleTable tbody').append(html)
                },
                error: function(err) {},
            });
        }

        function isObject(v) {
            return v && typeof v === 'object' && !Array.isArray(v);
        }

        function hasChanged(prev, curr, path = '') {
            const diffs = {};

            const keys = new Set([...Object.keys(prev), ...Object.keys(curr)]);
            for (const k of keys) {
                if (prev[k] !== curr[k]) {
                    diffs[k] = {
                        from: prev[k],
                        to: curr[k]
                    };
                }
            }
            return diffs;
        }

        function isAfterNow(hhmm) {
            const [h, m] = hhmm.split(':').map(Number);
            const target = h * 60 + m;

            const now = new Date();
            const current = now.getHours() * 60 + now.getMinutes();
            console.log(target + ' ' + current)
            return target < current;
        }

        function generateTripStatusHTML(status) {
            let _class = status == '{{ Status::TRIP_ON_TIME }}' ? 'success' : '';
            _class = status == '{{ Status::TRIP_BOARDING }}' ? 'primary' : _class;
            _class = status == '{{ Status::TRIP_DELAYED }}' ? 'warning' : _class;
            _class = status == '{{ Status::TRIP_CANCELLED }}' ? 'danger' : _class;

            return `<span _class="status-dot status-${status}"></span>
            <span class="badge bg-${_class}-subtle text-${_class}">${reverseSlug(status)}</span>`;
        }

        function reverseSlug(slug) {
            return slug
                .replace(/_/g, ' ') // Replace underscores with spaces
                .replace(/\b\w/g, c => c.toUpperCase()); // Capitalize each word
        }

        function formatTime(time) {
            const [hours, minutes, seconds] = time.split(':');

            // Create a Date object (date is arbitrary)
            const date = new Date();
            date.setHours(hours, minutes, seconds);

            const formattedTime = date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
            return formattedTime;
        }

        function updateClock() {
            const now = new Date();
            let hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;

            document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds} ${ampm}`;
        }

        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>

</html>
