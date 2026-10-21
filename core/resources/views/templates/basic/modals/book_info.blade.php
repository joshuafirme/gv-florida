<div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="ticketDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ticket-details-dialog">
        <div class="modal-content ticket-details-modal">
            <div class="modal-header ticket-details-modal__header">
                <div>
                    <span>@lang('Transaction Details')</span>
                    <h5 class="modal-title" id="ticketDetailsTitle">-</h5>
                </div>
                <button type="button" class="ticket-modal-close" data-bs-dismiss="modal" aria-label="@lang('Close')">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="modal-body ticket-details-modal__body">
                <section class="ticket-detail-section">
                    <h6>@lang('Trip Details')</h6>
                    <div class="ticket-detail-rows">
                        <div><span>@lang('Travel Date')</span><strong data-ticket-detail="travel_date_long">-</strong></div>
                        <div><span>@lang('Departure Time')</span><strong data-ticket-detail="departure_time">-</strong></div>
                        <div><span>@lang('Bus Class')</span><strong data-ticket-detail="bus_class">-</strong></div>
                        <div><span>@lang('PNR')</span><strong data-ticket-detail="pnr">-</strong></div>
                        <div><span>@lang('Origin')</span><strong data-ticket-detail="origin">-</strong></div>
                        <div><span>@lang('Destination')</span><strong data-ticket-detail="destination">-</strong></div>
                        <div><span>@lang('Seat(s)')</span><strong data-ticket-detail="seats">-</strong></div>
                        <div><span>@lang('Ticket Status')</span><strong><span id="ticketModalTicketStatus" class="ticket-status">-</span></strong></div>
                    </div>
                </section>
                <section class="ticket-detail-section">
                    <h6>@lang('Payment Details')</h6>
                    <div class="ticket-detail-rows">
                        <div><span>@lang('Transaction ID')</span><strong data-ticket-detail="transaction_id">-</strong></div>
                        <div><span>@lang('Payment Channel')</span><strong data-ticket-detail="payment_method">-</strong></div>
                        <div><span>@lang('Fare Amount')</span><strong data-ticket-detail="fare_amount">-</strong></div>
                        <div><span>@lang('Payment Fee')</span><strong data-ticket-detail="payment_fee">-</strong></div>
                        <div class="ticket-detail-total"><span>@lang('Total Amount')</span><strong data-ticket-detail="total_amount">-</strong></div>
                        <div><span>@lang('Status')</span><strong><span id="ticketModalPaymentStatus" class="ticket-status">-</span></strong></div>
                    </div>
                </section>
            </div>
            <div class="modal-footer ticket-details-modal__footer">
                <button type="button" class="btn ticket-modal-secondary" data-bs-dismiss="modal">@lang('Close')</button>
                <a href="#" class="btn ticket-modal-primary d-none" id="ticketModalPay">
                    <i class="las la-wallet"></i> @lang('Pay Now')
                </a>
                <a href="#" target="_blank" rel="noopener" class="btn ticket-modal-outline d-none" id="ticketModalPrint">
                    <i class="las la-print"></i> @lang('Print')
                </a>
                <a href="#" target="_blank" rel="noopener" class="btn ticket-modal-primary d-none" id="ticketModalVoucher">
                    <i class="las la-download"></i> @lang('E-voucher')
                </a>
            </div>
        </div>
    </div>
</div>

@once
    @push('style')
        <style>
            .ticket-details-dialog{max-width:520px}.ticket-details-modal{border:0;border-radius:8px;box-shadow:0 20px 55px rgba(17,29,48,.28);overflow:hidden}.ticket-details-modal__header{align-items:center;background:var(--main-color);border:0;color:#fff;padding:17px 20px}.ticket-details-modal__header span{display:block;font-size:10px;font-weight:600;letter-spacing:0;text-transform:uppercase}.ticket-details-modal__header h5{color:#fff;font-size:17px;font-weight:700;letter-spacing:0;margin:2px 0 0}.ticket-modal-close{align-items:center;background:transparent;border:0;color:#fff;display:flex;font-size:22px;height:34px;justify-content:center;padding:0;width:34px}.ticket-modal-close:hover,.ticket-modal-close:focus-visible{background:rgba(255,255,255,.18);color:#fff;outline:2px solid rgba(255,255,255,.7);outline-offset:1px}.ticket-details-modal__body{background:#fff;max-height:65vh;overflow-y:auto;padding:16px 20px}.ticket-detail-section+section{margin-top:15px}.ticket-detail-section h6{color:var(--main-color);font-size:11px;font-weight:700;letter-spacing:0;margin:0 0 7px;text-transform:uppercase}.ticket-detail-rows{border:1px solid #e5e9ed;border-radius:8px;overflow:hidden}.ticket-detail-rows>div{align-items:center;border-bottom:1px solid #edf0f3;display:flex;gap:20px;justify-content:space-between;min-height:42px;padding:9px 12px}.ticket-detail-rows>div:last-child{border-bottom:0}.ticket-detail-rows span{color:#737d8b;font-size:12px}.ticket-detail-rows strong{color:#303a49;font-size:12px;font-weight:600;max-width:65%;text-align:right}.ticket-detail-rows .ticket-status{font-size:10px}.ticket-detail-rows .ticket-detail-total{background:#f2fbfb}.ticket-detail-total strong{color:#d72979;font-size:15px}.ticket-details-modal__footer{border-top:1px solid #edf0f3;gap:7px;padding:13px 20px}.ticket-details-modal__footer .btn{align-items:center;border-radius:6px;display:inline-flex;font-size:12px;font-weight:600;gap:5px;height:38px;justify-content:center;padding:0 14px;transition:background-color .16s ease,border-color .16s ease,color .16s ease,transform .16s ease;width:auto}.ticket-details-modal__footer .btn:hover{transform:translateY(-1px)}.ticket-details-modal__footer .btn:focus-visible{box-shadow:0 0 0 3px color-mix(in srgb,var(--main-color) 24%,transparent)!important;outline:2px solid transparent}.ticket-modal-secondary{background:#f3f5f7;border-color:#dfe4e9;color:#4f5968}.ticket-modal-secondary:hover,.ticket-modal-secondary:focus-visible{background:#e3e8ed;border-color:#cbd2da;color:#253044}.ticket-modal-primary{background:var(--main-color);border-color:var(--main-color);color:#fff}.ticket-modal-primary:hover,.ticket-modal-primary:focus-visible{background:color-mix(in srgb,var(--main-color) 84%,#000);border-color:color-mix(in srgb,var(--main-color) 84%,#000);color:#fff}.ticket-modal-outline{background:#fff;border-color:var(--main-color);color:var(--main-color)}.ticket-modal-outline:hover,.ticket-modal-outline:focus-visible{background:var(--main-color);border-color:var(--main-color);color:#fff}
            @media(max-width:575px){.ticket-details-dialog{margin:12px}.ticket-details-modal__header{padding:15px 16px}.ticket-details-modal__body{max-height:68vh;padding:14px}.ticket-details-modal__footer{padding:12px 14px}.ticket-details-modal__footer .btn{flex:1;padding:0 9px}.ticket-detail-rows>div{min-height:40px}.ticket-detail-rows strong{max-width:58%}}
        </style>
    @endpush
@endonce
