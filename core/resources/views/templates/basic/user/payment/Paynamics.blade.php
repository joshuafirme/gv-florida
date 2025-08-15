@extends($activeTemplate . 'layouts.frontend')

@php
    $pmethod = json_decode(file_get_contents('assets/admin/paynamics_pmethod.json'));
@endphp

@section('content')
    <div class="container padding-top padding-bottom">

        <form action="{{ route('user.paynamics.redirect') }}" method="post">
            @csrf
            <div class="payment-container row">
                <!-- Payment Options -->
                <div class="col-lg-6">
                    <div class="payment-box">
                        <h5 class="mb-3">How would you like to pay?</h5>
                        <p class="text-muted" style="font-size: 0.9rem;">Please select your preferred mode of
                            payment on the list below</p>

                        <div class="accordion mt-4" id="paymentAccordion">
                            @foreach ($pmethod->pmethod as $key => $item)
                                <div class="accordion-item border-0">
                                    <h2 class="accordion-header" id="heading{{ $item->value }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse{{ $item->value }}" aria-expanded="false"
                                            aria-controls="collapse{{ $item->value }}">
                                            {{ $item->name }}
                                        </button>
                                    </h2>
                                    <div id="collapse{{ $item->value }}" class="accordion-collapse collapse"
                                        aria-labelledby="heading{{ $item->value }}" data-bs-parent="#paymentAccordion">
                                        <div class="accordion-body">
                                            @foreach ($item->types as $type)
                                                <div class="payment-option">
                                                    <input type="radio" name="pchannel" data-pmethod="{{ $item->value }}"
                                                        value="{{ $type->value }}" required name="{{ $type->value }}"
                                                        id="{{ $type->value }}">
                                                    {{-- <img src="https://upload.wikimedia.org/wikipedia/commons/3/36/GCash_Logo.svg"
                                                                alt="GCash"> --}}
                                                    <label for="{{ $type->value }}"
                                                        class="mb-0">{{ $type->name }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach


                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a target="_blank" href="#">Terms and Conditions</a>.
                            </label>
                        </div>

                        <button class="btn btn--base mt-3 w-100">PAY {{ showAmount($deposit->final_amount) }}</button>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-3">You are about to pay</h6>
                            <h3 class="text--base">{{ showAmount($deposit->final_amount) }}</h3>
                            <hr>
                            <div>
                                <div class="title">REQUEST ID</div>
                                <div class="text-muted" style="font-size: 0.85rem;">{{ $deposit->trx }}</div>
                            </div>
                            <ul class="nav nav-tabs mt-3" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="order-tab" data-bs-toggle="tab"
                                        data-bs-target="#order" type="button">ORDER INFO</button>
                                </li>
                                {{-- <li class="nav-item" role="presentation">
                            <button class="nav-link" id="other-tab" data-bs-toggle="tab" data-bs-target="#other"
                                type="button">OTHER INFO</button>
                        </li> --}}
                            </ul>
                            <div class="tab-content mt-3">
                                <div class="tab-pane fade show active" id="order" role="tabpanel">
                                    <table class="table">
                                        <tr>
                                            <td>
                                                <span class="font-weight-bold"> {{ __($ticket->trip->startFrom->name) }} -
                                                    {{ __($ticket->trip->endTo->name) }}</span>
                                                <span
                                                    class="badge bg-success">{{ __($ticket->trip->fleetType->name) }}</span>
                                            </td>
                                            <td>
                                                <span>{{ 'Seats: ' . implode(', ', $ticket->seats) }}</span>
                                                <span>{{ "PNR: $ticket->pnr_number " }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span>{{ showAmount($deposit->amount) }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="text-end">Charge</td>
                                            <td class="text-end">{{ showAmount($deposit->charge) }}</td>
                                        </tr>
                                        <tr class="fw-bolder">
                                            <td colspan="2" class="text-end">Total</td>
                                            <td>{{ showAmount($deposit->final_amount) }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="tab-pane fade" id="other" role="tabpanel">
                                    <p class="text-muted">Other payment-related information here.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        {{-- <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="mb-3">Customer Information</h4>
                <form>
                    <!-- Customer Info -->
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="fname" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="fname" name="fname" value="CISS">
                        </div>
                        <div class="col-md-4">
                            <label for="lname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lname" name="lname" value="CISSs">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="ciss@paynamics.net">
                        </div>
                        <div class="col-md-3">
                            <label for="mobile" class="form-label">Mobile</label>
                            <input type="text" class="form-control" id="mobile" name="mobile"
                                value="09664817707">
                        </div
                    </div>

                    <hr class="my-4">

                    <!-- Billing Info -->
                    <h4 class="mb-3">Billing Information</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="billing_address1" class="form-label">Address Line 1</label>
                            <input type="text" class="form-control" id="billing_address1" name="billing_address1"
                                value="First Street">
                        </div>
                        <div class="col-md-6">
                            <label for="billing_address2" class="form-label">Address Line 2</label>
                            <input type="text" class="form-control" id="billing_address2" name="billing_address2"
                                value="H.V. dela Costa Street">
                        </div>
                        <div class="col-md-4">
                            <label for="billing_city" class="form-label">City</label>
                            <input type="text" class="form-control" id="billing_city" name="billing_city"
                                value="Makati">
                        </div>
                        <div class="col-md-4">
                            <label for="billing_state" class="form-label">State/Province</label>
                            <input type="text" class="form-control" id="billing_state" name="billing_state"
                                value="PH-00">
                        </div>
                        <div class="col-md-4">
                            <label for="billing_country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="billing_country" name="billing_country"
                                value="PH">
                        </div>
                        <div class="col-md-4">
                            <label for="billing_zip" class="form-label">Zip Code</label>
                            <input type="text" class="form-control" id="billing_zip" name="billing_zip"
                                value="1227">
                        </div>
                    </div>

                    <hr class="my-4">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div> --}}
    </div>
@endsection
