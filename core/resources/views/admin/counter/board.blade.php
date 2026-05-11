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

    <link href="{{ asset('assets/global/css/bootstrap.min.css') }}" rel="stylesheet">
    
    <link href="{{ asset('assets/admin/css/vendor/datatables.min.2.3.4.css') }}" rel="stylesheet">

    <style>
        :root {
            --bg-main: #0B1423;         /* Deep navy background */
            --bg-secondary: #102442;    /* Slightly lighter navy for headers/footers */
            --border-color: #1B355A;    /* Border separating rows */
            --text-blue: #8AB4F8;       /* Light blue for table headers */
        }

        body {
            background-color: var(--bg-main);
            color: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Top Header Styling */
        .board-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 2px solid var(--text-blue);
        }

        .header-title {
            font-size: 2.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin: 0;
            text-transform: uppercase;
        }

        .clock-widget {
            text-align: right;
        }

        .clock-time {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.1;
        }

        .clock-date {
            font-size: 1rem;
            color: #A0AEC0;
        }

        /* Table Styling */
        .table-custom {
            width: 100%;
            margin-bottom: 0;
            color: #ffffff;
        }

        .table-custom th {
            background-color: var(--bg-secondary);
            color: var(--text-blue);
            font-size: 0.95rem;
            text-transform: uppercase;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            border-top: 1px solid var(--border-color);
            font-weight: 600;
        }

        .table-custom td {
            background-color: transparent;
            color: #ffffff;
            font-size: 1.15rem;
            font-weight: 500;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .table-custom tbody tr:nth-of-type(even) td {
            background-color: rgba(0, 0, 0, 0.15); 
        }

        .status-text {
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Pagination Dark Theme Overrides */
        .pagination-container {
            padding: 1rem;
            display: flex;
            justify-content: center;
            background-color: var(--bg-main);
        }

        .page-link {
            color: var(--text-blue);
            background-color: var(--bg-secondary);
            border-color: var(--border-color);
        }

        .page-link:hover {
            background-color: var(--border-color);
            color: #ffffff;
            border-color: var(--text-blue);
        }

        .page-item.active .page-link {
            background-color: var(--text-blue);
            border-color: var(--text-blue);
            color: var(--bg-main);
            font-weight: bold;
        }

        .page-item.disabled .page-link {
            background-color: var(--bg-main);
            border-color: var(--border-color);
            color: #4a5568;
        }

        /* Footer Banner */
        .footer-banner {
            background-color: var(--bg-secondary);
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .footer-banner > div {
            flex: 1;
            padding: 0.8rem 2rem;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .footer-left {
            text-align: left;
            color: #ffffff;
        }

        .footer-right {
            text-align: right;
            color: var(--text-blue);
            border-left: 2px solid var(--border-color);
        }

        .last-updated-text {
            color: #6c757d;
        }
    </style>
</head>

<body>
    <header class="board-header">
        <div style="width: 25%;">
            <img style="max-height: 60px;" src="{{ siteLogo('dark') }}" alt="Logo">
        </div>
        
        <div class="text-center" style="width: 50%;">
            <h1 class="header-title">Today's Departures</h1>
        </div>
        
        <div class="clock-widget" style="width: 25%;">
            <div id="clock-time" class="clock-time">--:-- --</div>
            <div id="clock-date" class="clock-date">--</div>
        </div>
    </header>

    <main class="container-fluid px-0">
        <div class="table-responsive">
            <table id="scheduleTable" class="table table-custom">
                <thead>
                    <tr>
                        <th scope="col" class="text-start ps-4">Time</th>
                        <th scope="col" class="text-center">Destination</th>
                        <th scope="col" class="text-center">Bus No.</th>
                        <th scope="col" class="text-center">Class</th>
                        <th scope="col" class="text-center">Available Seats</th>
                        <th scope="col" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    </tbody>
            </table>
        </div>

        <div id="paginationContainer" class="pagination-container">
            </div>

        <div class="footer-banner">
            <div class="footer-left">
                Thank you for choosing GV Florida. Have a safe trip!
            </div>
            <div class="footer-right">
                Please check your tickets and belongings
            </div>
        </div>

        <p class="text-center last-updated-text mt-3 small">
            Last updated: <span id="lastUpdated"></span>
        </p>
    </main>

    <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>

    <script>
        // Pagination Variables
        let currentPage = 1;
        const itemsPerPage = 10;
        let tableData = []; 
        let last_updated = null;

        // Initialize Fetch
        scheduleBoard();
        setInterval(() => {
            scheduleBoard();
        }, 5000);

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
                tickLastUpdate();
            }
            
            const id = window.location.pathname.split('/').filter(Boolean).pop();
            
            $.ajax({
                url: "{{ env('APP_URL') }}" + 'admin/counter/schedule-board/json/' + id,
                type: 'GET',
                data: {
                    '_token': "{{ csrf_token() }}"
                },
                success: function (data) {
                    if (last_updated && last_updated != data.last_updated) {
                        tickLastUpdate();
                    }
                    last_updated = data.last_updated;
                    
                    // Store data globally for pagination
                    tableData = data.res; 
                    
                    // Render current page
                    renderTable();
                },
                error: function (err) { 
                    console.error("Failed to fetch schedule data", err);
                },
            });
        }

        function renderTable() {
            $('#scheduleTable tbody').empty();
            let html = '';

            // Handle edge case: if polling removes items and the current page is now empty
            const totalPages = Math.ceil(tableData.length / itemsPerPage);
            if (currentPage > totalPages && totalPages > 0) {
                currentPage = totalPages;
            }

            // Calculate slicing indexes
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const paginatedItems = tableData.slice(startIndex, endIndex);

            // Generate rows
            for (const item of paginatedItems) {
                if (isAfterNow(item.schedule.start_from) &&
                    item.trip_status == '{{ Status::TRIP_ON_TIME }}') {
                    item.trip_status = '{{ Status::TRIP_DELAYED }}';
                }

                let bus_no = item.assigned_vehicle && item.assigned_vehicle.vehicle ? item.assigned_vehicle.vehicle.bus_no : 'N/A';

                html += `
                <tr>
                    <td class="text-start ps-4" data-order="${item.schedule.start_from}">${formatTime(item.schedule.start_from)}</td>
                    <td class="text-center text-uppercase">${item.route.end_to.city}</td>
                    <td class="text-center">${bus_no}</td>
                    <td class="text-center text-uppercase">${item.fleet_type.name}</td>
                    <td class="text-center">${item.available_seats > 0 ? item.available_seats : 'Full'}</td>
                    <td class="text-center">${generateTripStatusHTML(item.trip_status)}</td>
                </tr>`;
            }

            $('#scheduleTable tbody').append(html);
            
            // Re-draw pagination controls
            renderPaginationControls(tableData.length);
        }

        function renderPaginationControls(totalItems) {
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            
            // Hide pagination if 10 or fewer items exist
            if (totalPages <= 1) {
                $('#paginationContainer').html('');
                return;
            }

            let paginationHtml = '<ul class="pagination mb-0">';

            // Previous Button
            paginationHtml += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Previous</a>
                </li>`;

            // Page Numbers
            for (let i = 1; i <= totalPages; i++) {
                paginationHtml += `
                    <li class="page-item ${currentPage === i ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
                    </li>`;
            }

            // Next Button
            paginationHtml += `
                <li class="page-item ${currentPage === totalPages || totalPages === 0 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Next</a>
                </li>`;

            paginationHtml += '</ul>';

            $('#paginationContainer').html(paginationHtml);
        }

        // Triggered when a pagination button is clicked
        function changePage(page) {
            const totalPages = Math.ceil(tableData.length / itemsPerPage);
            if (page >= 1 && page <= totalPages) {
                currentPage = page;
                renderTable();
            }
        }

        function isAfterNow(hhmm) {
            const [h, m] = hhmm.split(':').map(Number);
            const target = h * 60 + m;

            const now = new Date();
            const current = now.getHours() * 60 + now.getMinutes();
            return target < current;
        }

        function generateTripStatusHTML(status) {
            return `<span class="status-text">${reverseSlug(status)}</span>`;
        }

        function reverseSlug(slug) {
            return slug
                .replace(/_/g, ' ')
                .replace(/\b\w/g, c => c.toUpperCase());
        }

        function formatTime(time) {
            const [hours, minutes, seconds] = time.split(':');
            const date = new Date();
            date.setHours(hours, minutes, seconds);

            return date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        }

        function updateClock() {
            const now = new Date();
            let hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;

            document.getElementById('clock-time').textContent = `${hours}:${minutes}:${seconds} ${ampm}`;

            const dateString = now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            const dayString = now.toLocaleDateString('en-US', { weekday: 'long' });
            document.getElementById('clock-date').textContent = `${dateString} | ${dayString}`;
        }

        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>