@section('content')
    @php
        $layout = 'layouts.kiosk';
    @endphp
    @include('templates.basic.partials.kiosk_nav')
    @extends($activeTemplate . $layout)

    <style>
        .e-vouch-wrapper {
            width: 100%;
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 10px;
            margin: 0 auto;
            padding: 5px;
            /* ONLY 5px padding */
            width: 114px;
            line-height: 17px;
        }

        .e-vouch-wrapper .ticket-header {
            text-align: center;
            margin-bottom: 6px;
        }

        .e-vouch-wrapper .ticket-logo img {
            width: 60px;
            margin-bottom: 4px;
        }

        .e-vouch-wrapper .ticket-header h4 {
            font-size: 12px;
            margin: 2px 0;
        }

        .e-vouch-wrapper .ticket-header p {
            font-size: 10px;
            margin: 0;
        }

        .e-vouch-wrapper table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            word-wrap: break-word;
        }

        .e-vouch-wrapper td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 10px;
        }

        .e-vouch-wrapper .title {
            font-weight: bold;
        }

        .e-vouch-wrapper .value {
            word-break: break-word;
        }



        .e-vouch-wrapper .qr {
            text-align: center;
            margin-top: 6px;
        }

        .e-vouch-wrapper .qr img {
            width: 50px;
            height: 50px;
        }

        .e-vouch-wrapper {
            page-break-inside: avoid;
        }
    </style>
    <div class="ticket-wrapper">

        <div class="ticket-header mt-5">
            <h4>{{ __(@$ticket->trip->assignedVehicle->vehicle->nick_name) }}</h4>
            <p>@lang('E-Ticket / Reservation Voucher')</p>
        </div>

        <div class="details-container">
            <table class="table table-bordered">
                <tr>
                    <td class="title">PNR</td>
                    <td class="value">{{ $ticket->pnr_number }}</td>
                </tr>

                @if ($ticket->user)
                    <tr>
                        <td class="title">Name</td>
                        <td class="value">{{ $ticket->user->fullname }}</td>
                    </tr>
                @endif

                <tr>
                    <td class="title">Date</td>
                    <td class="value">{{ showDateTime($ticket->date_of_journey, 'M d, Y') }}</td>
                </tr>

                <tr>
                    <td class="title">Seats</td>
                    <td class="value">{{ implode(',', $ticket->seats) }}</td>
                </tr>

                @if (isset($ticket->deposit->userDiscount))
                    <tr>
                        <td class="title">Discount</td>
                        <td class="value"> - {{ number_format($ticket->deposit->userDiscount->amount, 2) }} PHP</td>
                    </tr>
                @endif
                <tr>
                    <td class="title">Amount</td>
                    <td class="value"> {{ number_format($ticket->deposit->final_amount, 2) }} PHP</td>
                </tr>

                <tr>
                    <td class="title">Method</td>
                    <td class="value">
                        @if ($ticket->deposit->gateway->name == 'Paynamics')
                            {{ getPaynamicsPChannel($ticket->deposit->pchannel, true) }}
                        @else
                            {{ $ticket->deposit->gateway->name }}
                        @endif
                    </td>
                </tr>

                <tr>
                    <td class="title">Status</td>
                    <td><span class="status-paid">{!! paymentStatus($ticket->deposit->status) !!}</span></td>
                </tr>
            </table>
        </div>

        @if ($ticket->deposit->status != Status::PAYMENT_SUCCESS)
            <div class="alert alert-info">
                Transaction complete. Please collect and bring your printed Reservation Voucher to the Cashier.
            </div>
        @endif

        <div class="d-flex">
            <img class="m-auto" src="{{ asset('assets/admin/images/atm.png') }}" alt="">
        </div>
        {{-- <div class="e-vouch-wrapper" id="print-area">

            <div class="ticket-header">
                <div class="ticket-logo">
                    <img src="{{ env('APP_URL') }}assets/admin/images/GV.png" alt="Logo">
                </div>
                <h4>{{ __(@$ticket->trip->assignedVehicle->vehicle->nick_name) }}</h4>
                <p>@lang('E-Ticket / Reservation Voucher')</p>
            </div>

            <table>
                <tr>
                    <td class="title">PNR</td>
                    <td class="value">{{ $ticket->pnr_number }}</td>
                </tr>

                @if ($ticket->user)
                    <tr>
                        <td class="title">Name</td>
                        <td class="value">{{ $ticket->user->fullname }}</td>
                    </tr>
                @endif

                <tr>
                    <td class="title">Date</td>
                    <td class="value">{{ showDateTime($ticket->date_of_journey, 'M d, Y') }}</td>
                </tr>

                <tr>
                    <td class="title">Seats</td>
                    <td class="value">{{ implode(',', $ticket->seats) }}</td>
                </tr>

                <tr>
                    <td class="title">Amount</td>
                    <td class="value">{{ number_format($ticket->deposit->amount, 2) }} PHP</td>
                </tr>

                <tr>
                    <td class="title">Method</td>
                    <td class="value">
                        @if ($ticket->deposit->gateway->name == 'Paynamics')
                            {{ getPaynamicsPChannel($ticket->deposit->pchannel, true) }}
                        @else
                            {{ $ticket->deposit->gateway->name }}
                        @endif
                    </td>
                </tr>

                <tr>
                    <td class="title">Status</td>
                    <td><span class="status-paid">{!! paymentStatus($ticket->deposit->status) !!}</span></td>
                </tr>
            </table>

            <div class="qr">
                @php
                    $qr = base64_encode(QrCode::format('svg')->size(100)->generate($ticket->pnr_number));
                @endphp
                <img src="data:image/svg+xml;base64,{{ $qr }}" alt="QR Code">
            </div>

        </div> --}}
        <div class="d-flex mt-4">
            <a class="btn btn-outline-success btn-lg m-auto" href="{{ url("/tickets?kiosk_id=$ticket->kiosk_id") }}">
                Start a new transaction
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
        {{-- <div class="qr">
            @php
                $qr = base64_encode(QrCode::format('svg')->size(100)->generate($ticket->pnr_number));
            @endphp
            <img src="data:image/svg+xml;base64,{{ $qr }}" alt="QR Code">
        </div> --}}

    </div>
@endsection

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/global/css/reservartion-slip.css') }}">
@endpush

@push('script')
    <script src="{{ asset('assets/admin/js/vendor/qz-tray.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/qz-printer.js') }}"></script>
    <script>
        $(document).ready(function() {

            const BASE_URL = "{{ url('/') }}/";
            const id = "{{ $ticket->id }}";

            //

            //printDiv('print-area')

            var userAgent = navigator.userAgent;
            if (userAgent.indexOf("Android") > -1) {
                printVouchRawBT()
            } else {
                // Code for other devices
                console.log("This is not an Android device.");
                printVouch()
            }


            function printDiv(divId) {
                var divToPrint = document.getElementById(divId);
                // Create a new window or tab
                var newWin = window.open('');
                // Write the HTML content of the specific element to the new window
                newWin.document.write(divToPrint.outerHTML);

                // Optional: Add a link to your external CSS file(s) for styling in the new window
                // newWin.document.write('<link rel="stylesheet" href="style.css">');

                newWin.document.close();
                newWin.focus(); // Focus on the new window

                // Trigger the print dialog
                newWin.print();

                // Close the new window after printing
                newWin.close();
            }

            function printVouchRawBT() {
                let text = `
                        <CENTER><B>GV FLORIDA</B></CENTER>
                        <CENTER>E-Ticket / Reservation Voucher</CENTER>

                        PNR: {{ $ticket->pnr_number }}
                        Name: {{ $ticket->user->fullname ?? '' }}
                        Date: {{ showDateTime($ticket->date_of_journey, 'M d, Y') }}
                        Seats: {{ implode(',', $ticket->seats) }}
                        Amount: {{ number_format($ticket->deposit->amount, 2) }} PHP
                        Method: {{ $ticket->deposit->gateway->name }}
                        Status: {{ strip_tags(paymentStatus($ticket->deposit->status)) }}

                        ------------------------------

                        QR: {{ $ticket->pnr_number }}

                        Thank you!
                    `;

                console.log('Printing rawbt...')
                // Encode text
                let encoded = encodeURIComponent(text);

                // Trigger RawBT
                window.location.href = "rawbt:" + encoded;
            }

            function printVouch() {

                const data = {
                    pnr: "{{ $ticket->pnr_number }}",
                    name: "{{ $ticket->user->first_name ?? '' }}",
                    date: "{{ showDateTime($ticket->date_of_journey, 'M d, Y') }}",
                    seats: "{{ implode(',', $ticket->seats) }}",
                    amount: "{{ number_format($ticket->deposit->amount, 2) }}",
                    method: "{{ $ticket->deposit->gateway->name }}",
                    status: "{{ $ticket->deposit->status }}"
                };

                if (window.Android) {
                    console.log('Android bridge running...')
                    Android.printReceipt(JSON.stringify(data));
                } else {
                    console.log("Android bridge not available");


                    connectQZ()

                        .then(() => {


                            return getPrinter();
                        })

                        .then(printer => {
                            let btn = $('#printBtn');
                            let default_btn = btn.html();
                            btn.html("Printing...")
                            btn.prop('disabled', true)

                            let config = qz.configs.create(printer, {
                                scaleContent: true,
                                colorType: 'color'
                            });

                            fetch(BASE_URL + 'api/ticket/download/print-ticket/' + id)
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {

                                    }
                                    btn.html(default_btn)
                                    btn.prop('disabled', false)
                                    qz.print(config, [{
                                        type: 'pdf',
                                        format: 'file',
                                        data: data.file_url,
                                        options: {
                                            autoRotate: true
                                        }
                                    }]);
                                })
                                .catch(console.error);
                        })

                        .then(() => {

                            $('#status').text('✅ Printed successfully!');
                        })

                        .catch(err => {

                            console.error(err);

                            $('#status').text('❌ Error: ' + err);

                            alert('Print Error: ' + err);
                        });
                }
            }


        });
    </script>
@endpush
