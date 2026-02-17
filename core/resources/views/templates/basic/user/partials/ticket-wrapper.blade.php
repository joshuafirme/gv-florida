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
