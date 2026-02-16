@extends('admin.layouts.app')
@section('panel')
    <div class="row mb-none-30 justify-content-center">
        <div class="col-xl-4 col-md-6 mb-30">
            <div class="card overflow-hidden box--shadow1">
                <div class="card-body">
                    <h5 class="mb-20 text-muted">@lang('Deposit Via') @if ($deposit->method_code < 5000)
                            {{ __(@$deposit->gateway->name) }}
                        @else
                            @lang('Google Pay')
                        @endif
                    </h5>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('Date')
                            <span class="fw-bold">{{ showDateTime($deposit->created_at) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('Transaction Number')
                            <span class="fw-bold">{{ $deposit->trx }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('Username')
                            @if ($deposit->user)
                                <span class="fw-bold">
                                    <a
                                        href="{{ route('admin.users.detail', $deposit->user_id) }}"><span>@</span>{{ @$deposit->user->username }}</a>
                                </span>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('Method')
                            <span class="fw-bold">
                                @if ($deposit->method_code < 5000)
                                    {{ __(@$deposit->gateway->name) }}
                                @else
                                    @lang('Google Pay')
                                @endif
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('Amount')
                            <span class="fw-bold">{{ showAmount($deposit->amount) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('Charge')
                            <span class="fw-bold">{{ showAmount($deposit->charge) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('After Charge')
                            <span class="fw-bold">{{ showAmount($deposit->amount + $deposit->charge) }}</span>
                        </li>
                        {{-- <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('Rate')
                            <span class="fw-bold">1 {{ __(gs('cur_text')) }}
                                = {{ showAmount($deposit->rate, currencyFormat: false) }}
                                {{ __($deposit->baseCurrency()) }}</span>
                        </li> --}}
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('After Rate Conversion')
                            <span class="fw-bold">{{ showAmount($deposit->final_amount, currencyFormat: false) }}
                                {{ __($deposit->method_currency) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            @lang('Status')
                            @php echo $deposit->statusBadge @endphp
                        </li>
                        @if ($deposit->admin_feedback)
                            <li class="list-group-item">
                                <strong>@lang('Admin Response')</strong>
                                <br>
                                <p>{{ __($deposit->admin_feedback) }}</p>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
        @if ($details || $deposit->status == Status::PAYMENT_PENDING)
            <div class="col-xl-8 col-md-6 mb-30">
                <div class="card overflow-hidden box--shadow1">
                    <div class="card-body">
                        <h5 class="card-title border-bottom pb-2">@lang('Point of Sale')</h5>
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Amount</label>
                                    <input type="text" id="amount" class="form-control money"
                                        value="{{ number_format($deposit->final_amount, 2, '.') }}" placeholder="0.00">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Cash Received</label>
                                    <input type="text" id="cash" class="form-control money" placeholder="0.00">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Change</label>
                                    <input type="text" id="change" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-6">
                                <button data-id="{{ $deposit->bookedTicket->id }}" class="btn btn-outline--primary w-auto"
                                    id="printBtn"><i class="fa-solid fa-print"></i>
                                    Print</button>
                            </div>
                        </div>
                        {{-- @if ($details != null)
                            @foreach (json_decode($details) as $val)
                                @if ($deposit->method_code >= 1000)
                                    <div class="row mt-4">
                                        <div class="col-md-12">
                                            <h6>{{ __(@$val->name) }}</h6>
                                            @if ($val->type == 'checkbox')
                                                {{ implode(',', $val->value) }}
                                            @elseif($val->type == 'file')
                                                @if (@$val->value)
                                                    <a
                                                        href="{{ route('admin.download.attachment', encrypt(getFilePath('verify') . '/' . $val->value)) }}"><i
                                                            class="fa-regular fa-file"></i> @lang('Attachment') </a>
                                                @else
                                                    @lang('No File')
                                                @endif
                                            @else
                                                <p>{{ __(@$val->value) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                            @if ($deposit->method_code < 1000)
                                @include('admin.deposit.gateway_data', [
                                    'details' => json_decode($details),
                                ])
                            @endif
                        @endif
                        @if ($deposit->status == Status::PAYMENT_PENDING)
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <button class="btn btn-outline--success btn-sm ms-1 confirmationBtn"
                                        data-action="{{ route('admin.deposit.approve', $deposit->id) }}"
                                        data-question="@lang('Are you sure to approve this transaction?')"><i class="las la-check"></i>
                                        @lang('Approve')
                                    </button>

                                    <button class="btn btn-outline--danger btn-sm ms-1" data-bs-toggle="modal"
                                        data-bs-target="#rejectModal"><i class="las la-ban"></i> @lang('Reject')
                                    </button>
                                </div>
                            </div>
                        @endif --}}
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- REJECT MODAL --}}
    <div id="rejectModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Reject Deposit Confirmation')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.deposit.reject') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" value="{{ $deposit->id }}">
                    <div class="modal-body">
                        <p>@lang('Are you sure to') <span class="fw-bold">@lang('reject')</span> <span
                                class="fw-bold text--success">{{ showAmount($deposit->amount) }}</span> @lang('deposit of')
                            <span
                                class="fw-bold">{{ $deposit->user ? @$deposit->user->username : ' this transaciton' }}</span>?
                        </p>

                        <div class="form-group">
                            <label class="mt-2">@lang('Reason for Rejection')</label>
                            <textarea name="message" maxlength="255" class="form-control" rows="5" required>{{ old('message') }}</textarea>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection

@push('script')
    <script src="
        https://cdn.jsdelivr.net/npm/qz-tray@2.2.5/qz-tray.min.js
        "></script>
    <script>
        $(document).ready(function() {

            const BASE_URL = "{{ url('/') }}/";


            function printPDF(id) {

                $('#status').text('Connecting to QZ Tray...');

                connectQZ()

                    .then(() => {

                        $('#status').text('Getting printer...');

                        return getPrinter();
                    })

                    .then(printer => {

                        $('#status').text('Using printer: ' + printer);

                        let config = qz.configs.create(printer, {
                            scaleContent: true,
                            colorType: 'color'
                        });

                        fetch(BASE_URL + 'api/ticket/download/reservation-slip/' + id)
                            .then(res => res.json())
                            .then(data => {
                                return qz.print(config, [{
                                    type: 'pdf',
                                    format: 'file',
                                    data: data.file_url,
                                    options: {
                                        autoRotate: true
                                    }
                                }]);
                            })
                            .catch(console.error);

                        // let data = [{
                        //     type: 'pdf',
                        //     data: PDF_URL
                        // }];

                        // $('#status').text('Sending to printer...');

                        // return qz.print(config, data);
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

            $(document).on('keydown', function(e) {

                // F9 key
                if (e.key === 'F9') {
                    e.preventDefault();
                    $('#printBtn').click();
                }

            });

            $(document).on('click', '#printBtn', function(e) {
                let id = "{{ $deposit->bookedTicket->id }}";
                printPDF(id)
            });


            /* =====================================================
               CERTIFICATE
            ===================================================== */

            qz.security.setCertificatePromise(function(resolve, reject) {

                resolve(`-----BEGIN CERTIFICATE-----
MIIDxzCCAq+gAwIBAgIUL+1ANo6tvEBOu/vVHSeJaCPfUL4wDQYJKoZIhvcNAQEL
BQAwgYsxCzAJBgNVBAYTAlBIMREwDwYDVQQIDAhCYXRhbmdhczEQMA4GA1UEBwwH
TmFzdWdidTESMBAGA1UECgwJR1ZGTE9SSURBMQswCQYDVQQLDAJHVjELMAkGA1UE
AwwCR1YxKTAnBgkqhkiG9w0BCQEWGmpvc2h1YS5maXJtZUBtYWtvcGEub25saW5l
MB4XDTI2MDIxNjAyNTY1OFoXDTM2MDIxNDAyNTY1OFowgYsxCzAJBgNVBAYTAlBI
MREwDwYDVQQIDAhCYXRhbmdhczEQMA4GA1UEBwwHTmFzdWdidTESMBAGA1UECgwJ
R1ZGTE9SSURBMQswCQYDVQQLDAJHVjELMAkGA1UEAwwCR1YxKTAnBgkqhkiG9w0B
CQEWGmpvc2h1YS5maXJtZUBtYWtvcGEub25saW5lMIIBIjANBgkqhkiG9w0BAQEF
AAOCAQ8AMIIBCgKCAQEA6uUKna8o9SZ9mi17VZctefCsnxDJTH6RvFgc8Gi5o/XY
ASMSmLcUCwuY+GSUmZrOL9Re9dpHeXQPTHccgD7jfQje+zEMaHrHJCLS4J9YHlOj
HUA69v5Dciv/kyCUOBuzGpb4Cn1A1iIqMoihUP0IqqmvJgPwuIiAiPf9C3nw6s/t
u1ClgYEyfcBnvKuaQMAnz646VJSC/DT06uWb2C7+nZSZiTNjklXer6l5lRUuitXI
36isGwseXMSkyl8K4JOMcq06yNW5t2lVEnD0EJyT1IThMZhi9z4GpYuNPCkTxS8u
ecp4LU/wp3GzqXLJJYuzf70WkjiJKgqPAgjcuyzJWwIDAQABoyEwHzAdBgNVHQ4E
FgQUalAwII6KlkQBpnmQiddHA50y4MEwDQYJKoZIhvcNAQELBQADggEBAItS4N0q
drNrRSZ7sNns46DIpmcEx5/mWdu/UW+pXF2/SGH6vz3gPjVGRRsTAElLBIDjp8S0
x3rZH+WxiSKntWcT/DQAzyhZK/CfDQHYBzZsQHhJ/phAsM5/DKinIByfh1t+X4uK
yZWV7TXpH0Lnis4sgT0vFhz4Z6mOm+O9zo8hjXZg0twOCy3zuHjApzIcY6Gj6aRB
5AbQrHxt0GGHmbVYdcyxndJ1x/luid7J52/2QI6nze5r88rLxrXlLz80RHl0MqPO
KlLgxzWgQVeKww41g0gmwZZqydKDTayoHiml0V5/NNYio3rMilioDQCDc7t/e5KG
CIi4il8SxWsN43U=
-----END CERTIFICATE-----`);

            });


            /* =====================================================
               SIGNATURE
            ===================================================== */

            qz.security.setSignatureAlgorithm("SHA512"); // Since 2.1
            qz.security.setSignaturePromise(function(toSign) {
                return function(resolve, reject) {
                    fetch(BASE_URL + "api/qz/sign?data="+toSign, {
                            cache: 'no-store',
                            headers: {
                                'Content-Type': 'text/plain'
                            },
                        })
                        .then(function(data) {
                            data.ok ? resolve(data.text()) : reject(data.text());
                        });
                };
            });


            /* =====================================================
               CONNECTION
            ===================================================== */

            function connectQZ() {

                if (!qz.websocket.isActive()) {

                    return qz.websocket.connect();

                }

                return Promise.resolve();
            }


            /* =====================================================
               GET PRINTER
            ===================================================== */

            function getPrinter() {

                return qz.printers.getDefault();
            }

            function parseNumber(val) {
                return parseFloat(val.replace(/,/g, '')) || 0;
            }

            function formatNumber(num) {
                return num.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function computeChange() {
                let amount = parseNumber($('#amount').val());
                let cash = parseNumber($('#cash').val());
                let change = cash - amount;

                $('#change').val(formatNumber(change >= 0 ? change : 0));
            }

            $('.money').on('blur', function() {
                let num = parseNumber($(this).val());
                $(this).val(formatNumber(num));
                computeChange();
            });

            $('.money').on('input', computeChange);

        });
    </script>
@endpush
