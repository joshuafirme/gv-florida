@section('content')
    @php
        $layout = 'layouts.kiosk';
    @endphp
    @include('templates.basic.partials.kiosk_nav')
    @extends($activeTemplate . $layout)

    <style>
        .ticket-wrapper {
            width: 50%;
            margin: 0 auto;
            /* centers horizontally */
        }

        .ticket-header {
            text-align: center;
            margin-bottom: 6px;
        }

        .ticket-logo img {
            width: 60px;
            margin-bottom: 4px;
        }

        .ticket-header h4 {
            margin: 2px 0;
        }

        .ticket-header p {
            margin: 0;
        }

        .details-container {}

        table {
            border-collapse: collapse;
            margin-bottom: 6px;
            word-wrap: break-word;
        }

        td {
            padding: 2px 0;
            vertical-align: top;
        }

        .title {
            font-weight: bold;
        }

        .value {
            word-break: break-word;
        }



        .qr {
            text-align: center;
            margin-top: 6px;
        }

        .qr img {
            width: 50px;
            height: 50px;
        }

        .ticket-wrapper {
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

                <tr>
                    <td class="title">Amount</td>
                    <td class="value"> {{ number_format($ticket->deposit->amount, 2) }} PHP</td>
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

        <div class="alert alert-info">
            Transaction complete. Please collect and bring your printed Reservation Voucher to the Cashier.
        </div>

        <div class="d-flex">
            <img class="m-auto" src="{{ asset('assets/admin/images/atm.png') }}" alt="">
        </div>
        <div class="d-flex mt-4">
            <a class="btn btn-outline-success btn-lg m-auto" href="{{ url("/tickets?kiosk_id=$ticket->kiosk_id") }}">
                Proceed to new transaction
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


@push('script')
    <script src="{{ asset('assets/admin/js/vendor/qz-tray.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/qz-printer.js') }}"></script>
    <script>
        $(document).ready(function() {

            const BASE_URL = "{{ url('/') }}/";
            const id = "{{ $ticket->id }}";

            printVouch()

            function printVouch() {

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

                        fetch(BASE_URL + 'api/ticket/download/reservation-slip/' + id)
                            .then(res => res.json())
                            .then(data => {
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


        });
    </script>
@endpush
