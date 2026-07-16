<!doctype html>
<html lang="en" data-theme="dark">

@php
    use App\Constants\Status;
@endphp

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bus Schedule Board</title>
    <link rel="shortcut icon" type="image/png" href="{{ siteFavicon() }}">

    <link href="{{ asset('assets/global/css/bootstrap.min.css') }}" rel="stylesheet">
    
    <style>
        /* Base Theme (Dark Mode) */
        :root {
            --bg-main: #0B1423;         
            --bg-secondary: #102442;    
            --border-color: #1B355A;    
            --text-blue: #8AB4F8;       
            --text-main: #ffffff;
            --text-muted: #A0AEC0;
            --row-hover: rgba(255, 255, 255, 0.05);
            --row-alt: rgba(0, 0, 0, 0.15);
            --btn-bg: #1B355A;
            --btn-hover: #2a4c80;
        }

        /* Light Theme Overrides */
        [data-theme="light"] {
            --bg-main: #f4f6f9;         
            --bg-secondary: #ffffff;    
            --border-color: #dee2e6;    
            --text-blue: #0d6efd;       
            --text-main: #212529;
            --text-muted: #6c757d;
            --row-hover: rgba(0, 0, 0, 0.03);
            --row-alt: rgba(0, 0, 0, 0.02);
            --btn-bg: #e9ecef;
            --btn-hover: #dee2e6;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Top Header Styling */
        .board-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 2px solid var(--text-blue);
            background-color: var(--bg-main);
        }

        .header-title {
            font-size: 2.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin: 0;
            text-transform: uppercase;
            color: var(--text-main);
        }

        .clock-widget {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .clock-time {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.1;
        }

        .clock-date {
            font-size: 1rem;
            color: var(--text-muted);
        }

        /* Theme Toggle Button */
        .theme-toggle-btn {
            background-color: var(--btn-bg);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .theme-toggle-btn:hover {
            background-color: var(--btn-hover);
        }

        /* Optional: Invert white logo to black in light mode */
    

        /* Table Styling */
        .table-custom {
            width: 100%;
            margin-bottom: 0;
            color: var(--text-main);
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
            color: var(--text-main);
            font-size: 1.15rem;
            font-weight: 500;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .table-custom tbody tr:hover td {
            background-color: var(--row-hover);
        }

        .table-custom tbody tr:nth-of-type(even) td {
            background-color: var(--row-alt); 
        }

        .status-text {
            text-transform: uppercase;
            font-weight: 600;
        }

        .status-text--departed {
            color: #f59e0b;
        }

        .seat-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 0.95rem;
            font-weight: 700;
            justify-content: center;
            min-width: 88px;
            padding: 0.35rem 0.75rem;
        }

        .seat-badge--available {
            background: rgba(34, 197, 94, 0.14);
            color: #22c55e;
        }

        .seat-badge--full {
            background: rgba(239, 68, 68, 0.16);
            color: #f87171;
            text-transform: uppercase;
        }

        /* Pagination Dark/Light Theme Support */
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
            color: var(--text-main);
            border-color: var(--text-blue);
        }

        .page-item.active .page-link {
            background-color: var(--text-blue);
            border-color: var(--text-blue);
            color: #ffffff; /* Always white text on active blue background */
            font-weight: bold;
        }

        .page-item.disabled .page-link {
            background-color: var(--bg-main);
            border-color: var(--border-color);
            color: var(--text-muted);
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
            color: var(--text-main);
        }

        .footer-right {
            text-align: right;
            color: var(--text-blue);
            border-left: 2px solid var(--border-color);
        }

        .last-updated-text {
            color: var(--text-muted);
        }

        .connection-status {
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.35rem;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <header class="board-header">
        <div style="width: 25%;">
            <img class="logo-img" style="max-height: 60px;" src="{{ siteLogo('dark') }}" alt="Logo">
        </div>
        
        <div class="text-center" style="width: 50%;">
            <h1 class="header-title">Today's Departures</h1>
        </div>
        
        <div class="clock-widget" style="width: 25%;">
            <button id="themeToggle" class="theme-toggle-btn">☀️ Light Mode</button>
            <div id="clock-time" class="clock-time">--:-- --</div>
            <div id="clock-date" class="clock-date">--</div>
            <div id="connectionStatus" class="connection-status">Connecting live updates</div>
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
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>

    <script>
        // --- Theme Toggle Logic ---
        const themeToggleBtn = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;

        function setTheme(theme) {
            htmlElement.setAttribute('data-theme', theme);
            localStorage.setItem('board_theme', theme);
            themeToggleBtn.innerHTML = theme === 'light' ? '🌙 Dark Mode' : '☀️ Light Mode';
        }

        // Check local storage for saved theme preference
        const savedTheme = localStorage.getItem('board_theme') || 'dark';
        setTheme(savedTheme);

        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            setTheme(currentTheme === 'light' ? 'dark' : 'light');
        });

        // --- Schedule & Pagination Logic ---
        let currentPage = 1;
        const itemsPerPage = 10;
        let tableData = []; 
        let last_updated = null;
        let refreshTimer = null;
        const counterId = window.location.pathname.split('/').filter(Boolean).pop();
        const scheduleBoardUrl = @json(route('admin.counter.scheduleBoardJSON', '__COUNTER_ID__')).replace('__COUNTER_ID__', counterId);
        const pusherKey = @json($pusherKey);
        const pusherCluster = @json($pusherCluster);

        scheduleBoard();
        connectScheduleBoardUpdates();

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

            $.ajax({
                url: scheduleBoardUrl,
                type: 'GET',
                data: {
                    '_token': "{{ csrf_token() }}"
                },
                success: function (data) {
                    tickLastUpdate();
                    last_updated = data.last_updated;
                    
                    tableData = data.res; 
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

            const totalPages = Math.ceil(tableData.length / itemsPerPage);
            if (currentPage > totalPages && totalPages > 0) {
                currentPage = totalPages;
            }

            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const paginatedItems = tableData.slice(startIndex, endIndex);

            if (!paginatedItems.length) {
                $('#scheduleTable tbody').append(`
                    <tr>
                        <td colspan="6" class="text-center py-5 text-uppercase">No departures available</td>
                    </tr>
                `);
                renderPaginationControls(tableData.length);
                return;
            }

            for (const item of paginatedItems) {
                const displayStatus = isDeparted(item.schedule.start_from) && item.trip_status === '{{ Status::TRIP_ON_TIME }}'
                    ? '{{ Status::TRIP_DEPARTED }}'
                    : item.trip_status;

                let bus_no = item.assigned_vehicle && item.assigned_vehicle.vehicle ? item.assigned_vehicle.vehicle.bus_no : 'N/A';
                let seatLabel = item.is_fully_booked
                    ? '<span class="seat-badge seat-badge--full">Fully Booked</span>'
                    : `<span class="seat-badge seat-badge--available">${item.available_seats}</span>`;

                html += `
                <tr>
                    <td class="text-start ps-4" data-order="${item.schedule.start_from}">${formatTime(item.schedule.start_from)}</td>
                    <td class="text-center text-uppercase">${item.route.end_to.city}</td>
                    <td class="text-center">${bus_no}</td>
                    <td class="text-center text-uppercase">${item.fleet_type.name}</td>
                    <td class="text-center">${seatLabel}</td>
                    <td class="text-center">${generateTripStatusHTML(displayStatus)}</td>
                </tr>`;
            }

            $('#scheduleTable tbody').append(html);
            renderPaginationControls(tableData.length);
        }

        function renderPaginationControls(totalItems) {
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            
            if (totalPages <= 1) {
                $('#paginationContainer').html('');
                return;
            }

            let paginationHtml = '<ul class="pagination mb-0">';

            paginationHtml += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Previous</a>
                </li>`;

            for (let i = 1; i <= totalPages; i++) {
                paginationHtml += `
                    <li class="page-item ${currentPage === i ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
                    </li>`;
            }

            paginationHtml += `
                <li class="page-item ${currentPage === totalPages || totalPages === 0 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Next</a>
                </li>`;

            paginationHtml += '</ul>';

            $('#paginationContainer').html(paginationHtml);
        }

        function changePage(page) {
            const totalPages = Math.ceil(tableData.length / itemsPerPage);
            if (page >= 1 && page <= totalPages) {
                currentPage = page;
                renderTable();
            }
        }

        function isDeparted(hhmm) {
            const [h, m] = hhmm.split(':').map(Number);
            const target = h * 60 + m;
            const now = new Date();
            const current = now.getHours() * 60 + now.getMinutes();
            return target < current;
        }

        function generateTripStatusHTML(status) {
            const className = status === '{{ Status::TRIP_DEPARTED }}'
                ? 'status-text status-text--departed'
                : 'status-text';

            return `<span class="${className}">${reverseSlug(status)}</span>`;
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

        function queueScheduleRefresh() {
            clearTimeout(refreshTimer);
            refreshTimer = setTimeout(scheduleBoard, 350);
        }

        function setConnectionStatus(message) {
            const status = document.getElementById('connectionStatus');
            if (status) {
                status.textContent = message;
            }
        }

        function connectScheduleBoardUpdates() {
            if (!pusherKey || typeof Pusher === 'undefined') {
                setConnectionStatus('Live updates unavailable');
                return;
            }

            Pusher.logToConsole = false;

            const pusher = new Pusher(pusherKey, {
                cluster: pusherCluster || 'ap1'
            });

            pusher.connection.bind('connected', function() {
                setConnectionStatus('Live updates connected');
            });

            pusher.connection.bind('unavailable', function() {
                setConnectionStatus('Live updates reconnecting');
            });

            pusher.connection.bind('failed', function() {
                setConnectionStatus('Live updates disconnected');
            });

            pusher.subscribe('schedule-board').bind('passenger-transaction', function() {
                queueScheduleRefresh();
            });
        }

        // --- Clock Logic ---
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
