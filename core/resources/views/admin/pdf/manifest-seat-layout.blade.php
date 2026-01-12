<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $trip->startFrom->name }} -> {{ $trip->endTo->name }}</title>

    <link rel="stylesheet" href="{{ asset('assets/global/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/templates/basic/css/main.css') }}">
    <style>
        /* Ensure A4 page size */
        body {
            background: #f0f0f0;
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
        }

        .seat-plan-inner {
            width: 100% !important;
        }

        .a4-container {
            width: 210mm;
            min-height: 297mm;
            background: #fff;
            margin: 0 auto;
            padding: 25px 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        /* Print button */
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            padding: 10px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            border: none;
        }

        /* Hide print button when printing */
        @media print {
            .print-btn {
                display: none !important;
            }

            body {
                background: none;
                padding: 0;
            }

            .a4-container {
                box-shadow: none;
                margin: 0;
                width: auto;
                min-height: auto;
                padding: 0;
            }
        }

        /* Simple layout styling */
        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .header h2 {
            margin: 0;
            font-size: 22px;
        }

        .header small {
            font-size: 14px;
        }

        .info-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
            font-size: 14px;
        }

        .info-table td {
            padding: 6px 0;
        }

        @media print {
            body * {
                visibility: hidden !important;
            }

            .a4-container,
            .a4-container * {
                visibility: visible !important;
            }

            /* Position printed area at top-left of the page */
            .a4-container {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                /* fit A4 width */
            }

            @page {
                size: A4;
                margin: 10mm;
                /* change as needed */
            }
        }


        .seat-wrapper .seat:not(.comfort-room) {
            width: 70px !important;
            height: 50px !important;
        }
    </style>
</head>

<body>

    <button class="print-btn" onclick="window.print()">Print</button>

    <div class="a4-container">

        <div class="header">
            <h2>G.V. FLORIDA TRANSPORT, INC.</h2>
            <small>{{ $trip->startFrom->name }}</small>
        </div>

        <table class="info-table">
            <tr>
                <td><strong>Destination:</strong> {{ $trip->endTo->name }}</td>
                <td><strong>Driver:</strong> ____________________</td>
            </tr>

            <tr>
                <td><strong>Bus No.:</strong>
                    {{ $trip->assignedVehicle ? $trip->assignedVehicle->vehicle->bus_no : '' }}</td>
                <td><strong>Conductor:</strong> ____________________</td>
            </tr>

            <tr>
                <td><strong>Time of Departure:</strong>
                    {{ $trip->schedule ? date('h:i A', strtotime($trip->schedule->start_from)) : '' }}</td>
                <td><strong>Helper:</strong> ____________________</td>
            </tr>

            <tr>
                <td><strong>Date:</strong> {{ date('M d, Y') }}</td>
            </tr>
        </table>

        <hr style="margin:20px 0;">

        <h5>Manifest ({{ $trip->route->name }})</h5>

        @include('templates.basic.partials.seat_layout', ['fleetType' => $trip->fleetType, 'from_manifest' => true])

    </div>

    <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>

    <script>
        showBookedSeat()

        function printA4() {
            const content = document.querySelector('.a4-container').innerHTML;
            const printWindow = window.open('', '_blank');

            printWindow.document.write(`
        <html>
        <head>
            <title>Print</title>
            <style>
                @page { size: A4; margin: 0; }
                body { margin: 20px; font-family: Arial, sans-serif; }
            </style>
        </head>
        <body>${content}</body>
        </html>
    `);

            printWindow.document.close();
            printWindow.focus();

            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 300);
        }

        function showBookedSeat() {
            var date = "{{ date('Y-m-d') }}";
            var sourceId = '{{ $trip->startFrom->id }}';
            var destinationId = '{{ $trip->endTo->id }}';
            console.log('sourceId', sourceId)
            console.log('destinationId', destinationId)
            var routeId = '{{ $trip->route->id }}';
            var fleetTypeId = '{{ $trip->fleetType->id }}';

            if (sourceId && destinationId) {
                getPrice(routeId, fleetTypeId, sourceId, destinationId, date)
            }
        }

        // check price, booked seat etc
        function getPrice(routeId, fleetTypeId, sourceId, destinationId, date) {
            var data = {
                "trip_id": "{{ $trip->id }}",
                "vehicle_route_id": routeId,
                "fleet_type_id": fleetTypeId,
                "source_id": sourceId,
                "destination_id": destinationId,
                "date": date,
            }
            $.ajax({
                type: "get",
                url: "{{ route('ticket.get-price') }}",
                data: data,
                success: function(response) {

                    if (response.error) {
                        var modal = $('#alertModal');
                        modal.find('.error-message').text(response.error);
                        modal.modal('show');
                        $('select[name="pickup_point"]').val('');
                        $('select[name="dropping_point"]').val('');
                    } else {
                        var stoppages = response.stoppages;

                        var reqSource = response.reqSource;
                        var reqDestination = response.reqDestination;

                        reqSource = stoppages.indexOf(reqSource.toString());
                        reqDestination = stoppages.indexOf(reqDestination.toString());

                        if (response.reverse == true) {
                            $.each(response.bookedSeats, function(i, v) {
                                var bookedSource = v.pickup_point; //Booked
                                var bookedDestination = v.dropping_point; //Booked

                                bookedSource = stoppages.indexOf(bookedSource.toString());
                                bookedDestination = stoppages.indexOf(bookedDestination
                                    .toString());

                                if (reqDestination >= bookedSource || reqSource <=
                                    bookedDestination) {
                                    $.each(v.seats, function(index, val) {
                                        if (v.gender == 1) {
                                            $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                .parent().removeClass(
                                                    'seat-condition selected-by-gents disabled'
                                                );
                                        }
                                        if (v.gender == 2) {
                                            $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                .parent().removeClass(
                                                    'seat-condition selected-by-ladies disabled'
                                                );
                                        }
                                        if (v.gender == 3) {
                                            $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                .parent().removeClass(
                                                    'seat-condition selected-by-others disabled'
                                                );
                                        }
                                    });
                                } else {
                                    $.each(v.seats, function(index, val) {
                                        if (v.gender == 1) {
                                            $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                .parent().addClass(
                                                    'seat-condition selected-by-gents disabled'
                                                );
                                        }
                                        if (v.gender == 2) {
                                            $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                .parent().addClass(
                                                    'seat-condition selected-by-ladies disabled'
                                                );
                                        }
                                        if (v.gender == 3) {
                                            $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                .parent().addClass(
                                                    'seat-condition selected-by-others disabled'
                                                );
                                        }
                                    });
                                }
                            });
                        } else {
                            $.each(response.bookedSeats, function(i, v) {

                                var bookedSource = v.pickup_point; //Booked
                                var bookedDestination = v.dropping_point; //Booked

                                bookedSource = stoppages.indexOf(bookedSource.toString());
                                bookedDestination = stoppages.indexOf(bookedDestination
                                    .toString());


                                if (reqDestination <= bookedSource || reqSource >=
                                    bookedDestination) {
                                    $.each(v.seats, function(index, val) {
                                        if (v.gender == 1) {
                                            $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                .parent().removeClass(
                                                    'seat-condition selected-by-gents disabled'
                                                );
                                        }
                                        if (v.gender == 2) {
                                            $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                .parent().removeClass(
                                                    'seat-condition selected-by-ladies disabled'
                                                );
                                        }
                                        if (v.gender == 3) {
                                            $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                                .parent().removeClass(
                                                    'seat-condition selected-by-others disabled'
                                                );
                                        }
                                    });
                                } else {
                                    $.each(v.seats, function(index, val) {
                                        $(`.seat-wrapper .seat[data-seat="${val}"]`)
                                            .parent().addClass(
                                                'seat-condition selected-by-gents disabled'
                                            );
                                        $(`.seat-wrapper .seat[data-seat="${val}"]`).text(
                                            `${val} Occupied`);
                                    });
                                }
                            });
                        }

                        if (response.price.error) {
                            var modal = $('#alertModal');
                            modal.find('.error-message').text(response.price.error);
                            modal.modal('show');
                        } else {
                            $('input[name=price]').val(response.price);
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>
