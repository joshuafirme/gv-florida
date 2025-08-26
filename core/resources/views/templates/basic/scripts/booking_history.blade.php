<script>
    "use strict"

    $('.checkinfo').on('click', function() {
        var info = $(this).data('info');
        console.log(info)
        var modal = $('#infoModal');
        var html = '';
        html += `
                    <p class="d-flex flex-wrap justify-content-between pt-0"><strong>@lang('Journey Date')</strong>  <span>${info.date_of_journey}</span></p>
                    <p class="d-flex flex-wrap justify-content-between"><strong>@lang('PNR Number')</strong>  <span>${info.pnr_number}</span></p>
                    <p class="d-flex flex-wrap justify-content-between"><strong>@lang('Route')</strong>  <span>${info.trip.start_from.name} @lang('to') ${info.trip.end_to.name}</span></p>
                    <p class="d-flex flex-wrap justify-content-between"><strong>@lang('Fare')</strong>  <span>${parseInt(info.sub_total).toFixed(2)} {{ __(gs('cur_text')) }}</span></p>
                    <p class="d-flex flex-wrap justify-content-between"><strong>@lang('Ticket Status')</strong>  <span>${info.status == 1 ? '<span class="badge badge--success">@lang('Successful')</span>' : info.status == 2 ? '<span class="badge badge--warning">@lang('Pending')</span>' : '<span class="badge badge--danger">@lang('Rejected')</span>'}</span></p>
                `;
        modal.find('.modal-body').html(html);

        $.get("{{ env('APP_URL') }}" + `user/paynamics/details/${info.deposit.trx}`, function(data, status) {
            if (status == 'success') {
                console.log(data)
                let pay_instruction = '';
                if (data.direct_otc_info) {
                    for (const info of data.direct_otc_info) {
                        pay_instruction += info.pay_instructions;
                    }
                }
                html += `
                    <h5 class="mt-3">Payment Details</h5>
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <tr>
                                <th>Transaction ID</th>
                                <td>${info.deposit.trx}</td>
                            </tr>
                            <tr>
                                <th>Payment Channel</th>
                                <td>${data.pchannel_name}</td>
                            </tr>`;

                if (data.direct_otc_info) {
                    html += `
                    <tr>
                        <th>Reference Number</th>
                        <td>${data.pay_reference}</td>
                    </tr>
                    <tr>
                        <th>Pay Before</th>
                        <td>${info.deposit.expiry_limit}</td>
                    </tr>`;
                }

                if (pay_instruction) {
                    html += `
                            <tr>
                                <th>Payment Instruction</th>
                                <td style="white-space: pre-line;">${pay_instruction}</td>
                            </tr>`;
                }

                let payment_status = paymentStatus(info.deposit.status);
                if (info.deposit.expiry_limit) {
                    const expiry = new Date(info.deposit.expiry_limit).getTime();
                    const today = Date.now();
                    if (expiry < today) {
                        console.log(expiry)
                        payment_status = paymentStatus(0, 'Expired');
                    }
                }

                html += `
                            <tr>
                                <th>Status</th>
                                <td>${payment_status}</td>
                            </tr>
                        </table>
                    </div>
                `;
                modal.find('.modal-body').html(html);
            }
        });
    })

    function paymentStatus(status, custom) {
        if (custom) {
            return '<span class="badge badge--danger">' + custom + '</span>';
        } else
        if (status == 1)
            return '<span class="badge badge--success">Paid</span>';
        else if (status == 2)
            return '<span class="badge badge--warning">Pending</span>';
        else if (status == 3)
            return '<span class="badge badge--danger">Rejected</span>';
        else
            return '<span class="badge badge--warning">Pending</span>';
    }
</script>
