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
            {{-- <h4>{{ __(@$ticket->trip->assignedVehicle->vehicle->nick_name) }}</h4> --}}
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
                    <td class="value">{{ formatSeatLabel($ticket->seats) }}</td>
                </tr>
                <tr>
                    <td class="title">Amount</td>
                    <td class="value"> {{ number_format($ticket->deposit->amount, 2) }} PHP</td>
                </tr>
                @if (isset($ticket->deposit->userDiscount))
                    <tr>
                        <td class="title">Discount</td>
                        <td class="value"> - {{ number_format($ticket->deposit->userDiscount->amount, 2) }} PHP</td>
                    </tr>
                @endif
                <tr>
                    <td class="title">Total Amount</td>
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
                Please be advised that this reservation is valid for 15 minutes only.
            </div>
        @endif

        <div class="d-flex">
            <img class="m-auto" src="{{ asset('assets/admin/images/atm.png') }}" alt="">
        </div>

        <div class="d-flex mt-4">
            <a class="btn btn-outline-success btn-lg m-auto"
                href="{{ url("/tickets?kiosk_id=$ticket->kiosk_id&counter_id=$ticket->pickup_point") }}">
                Start a new transaction
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>

    </div>
@endsection

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/global/css/reservartion-slip.css') }}">
@endpush

@push('script')
    @php
        $androidManifest = $ticket->passenger_manifest ?: [];
        $androidPassengerNames = collect($androidManifest)
            ->map(fn ($passenger) => trim((string) ($passenger['name'] ?? '')) ?: 'Guest')
            ->implode(', ');
        $androidPassengerTypes = collect($androidManifest)
            ->map(fn ($passenger) => ($passenger['passenger_type'] ?? 'regular') === 'discounted'
                ? ($passenger['discount_name'] ?? 'Discounted')
                : 'Regular')
            ->unique()
            ->implode(', ');
        $androidPickupAddress = collect([$ticket->pickup?->name, $ticket->pickup?->city])
            ->map(fn ($part) => trim((string) $part))
            ->filter()
            ->unique(fn ($part) => strtolower($part))
            ->implode(', ');
        $androidExpiresAt = $ticket->deposit->created_at->copy()->addMinutes(15);
        $androidIsPaid = (int) $ticket->deposit->status === \App\Constants\Status::PAYMENT_SUCCESS;
        $androidPaymentMethod = $ticket->deposit->pchannel
            ? getPaynamicsPChannel($ticket->deposit->pchannel, true)
            : ($ticket->deposit->gateway?->name ?? $ticket->deposit->methodName());
        $androidReceiptPayload = [
            'company_name' => 'GV FLORIDA TRANSPORT, INC.',
            'company_address' => strtoupper($androidPickupAddress),
            'pnr' => $ticket->pnr_number,
            'name' => $androidPassengerNames ?: 'Guest',
            'passenger_name' => $androidPassengerNames ?: 'Guest',
            'passenger_type' => $androidPassengerTypes ?: 'Regular',
            'date' => showDateTime($ticket->date_of_journey, 'M j, Y'),
            'destination' => $ticket->drop?->name ?? '',
            'dropoff_point' => $ticket->drop?->km_post ? 'KM ' . $ticket->drop->km_post : '',
            'source' => $ticket->kiosk_id ? 'Kiosk' : ($ticket->user_id ? 'Online' : 'Counter'),
            'updated_at' => formatDate($ticket->deposit->updated_at, true),
            'expired_at' => formatDate($androidExpiresAt, true),
            'valid_until' => showDateTime($androidExpiresAt, 'M j, Y, g:i A'),
            'seats' => formatSeatLabel($ticket->seats ?? []),
            'departure_time' => date('g:i A', strtotime($ticket->trip->schedule->start_from)),
            'bus_type' => $ticket->trip?->fleetType?->name ?? '',
            'amount' => number_format((float) $ticket->deposit->amount, 2),
            'discount_amount' => number_format((float) ($ticket->deposit->userDiscount?->amount ?? 0), 2),
            'discount_description' => $ticket->deposit->userDiscount?->description ?? '',
            'final_amount' => number_format((float) $ticket->deposit->final_amount, 2),
            'method' => $androidPaymentMethod ?: 'Cash',
            'status' => strip_tags($ticket->deposit->statusString),
            'payment_status' => $androidIsPaid ? 'PAID' : 'PENDING',
            'amount_label' => $androidIsPaid ? 'AMOUNT PAID' : 'AMOUNT TO BE PAID',
            'is_paid' => $androidIsPaid,
            'passengers' => $androidManifest,
        ];
    @endphp
    <script src="{{ asset('assets/admin/js/vendor/qz-tray.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/qz-printer.js') }}"></script>
    <script>
        // Push initial state
        window.history.pushState({ page: 1 }, "", window.location.href);

        // Push another state so back stays inside page first
        window.history.pushState({ page: 2 }, "", window.location.href);

        window.addEventListener('popstate', function (event) {

            if (!confirm("Are you sure you want to go back?")) {
                // Re-add state so user stays on page
                window.history.pushState({ page: 2 }, "", window.location.href);
            }
            else {
                window.location.href = '{{ url("/tickets?kiosk_id=$ticket->kiosk_id&counter_id=$ticket->pickup_point") }}';
            }
        });

        $(document).ready(function () {

            const BASE_URL = "{{ url('/') }}/";
            const id = "{{ $ticket->id }}";

           printVouch()

            function printVouch() {
                const data = @json($androidReceiptPayload);

                console.log('passing data to android: ', data)

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
