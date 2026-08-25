@extends('admin.layouts.app')

@section('panel')
    <div class="online-validation-toolbar">
        <div class="online-validation-tabs" role="tablist" aria-label="Ticket validation status">
            @foreach (['all' => 'All', 'to_validate' => 'To Validate', 'validated' => 'Validated'] as $key => $label)
                <a href="{{ route('admin.online.ticket.validation.index', array_filter(['status' => $key, 'search' => $search])) }}"
                    class="online-validation-tab {{ $status === $key ? 'is-active' : '' }}">
                    {{ $label }} <span>{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </div>
        <form action="{{ route('admin.online.ticket.validation.index') }}" method="GET" class="online-validation-search">
            <input type="hidden" name="status" value="{{ $status }}">
            <i class="las la-search"></i>
            <input type="search" name="search" value="{{ $search }}" placeholder="Search PNR, passenger, ref. or request no.">
        </form>
    </div>

    <div class="card online-validation-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table--light style--two online-validation-table">
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>PNR</th>
                            <th>Reference No.</th>
                            <th>Journey</th>
                            <th>Trip</th>
                            <th>Seat</th>
                            <th>Fare</th>
                            <th>Passenger</th>
                            <th>Payment</th>
                            <th>Validated</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td data-label="Source"><strong>Online</strong><span class="validation-meta">{{ $ticket['request_no'] }}</span></td>
                                <td data-label="PNR"><strong class="text--primary">{{ $ticket['pnr'] }}</strong></td>
                                <td data-label="Reference No."><strong>{{ $ticket['reference_no'] }}</strong></td>
                                <td data-label="Journey"><strong>{{ $ticket['journey_date'] }}</strong><span class="validation-meta">{{ $ticket['departure_time'] }}</span></td>
                                <td data-label="Trip"><strong>{{ $ticket['trip_class'] }}</strong><span class="validation-meta">{{ $ticket['trip_route'] }}</span></td>
                                <td data-label="Seat"><strong>{{ $ticket['seat'] }}</strong></td>
                                <td data-label="Fare" class="text-end">
                                    <strong>{{ showAmount($ticket['net_fare']) }}</strong>
                                    @if ($ticket['discount_amount'] > 0)
                                        <span class="validation-meta validation-discount">Override -{{ showAmount($ticket['discount_amount']) }}</span>
                                    @endif
                                </td>
                                <td data-label="Passenger"><strong>{{ $ticket['passenger_name'] }}</strong><span class="validation-meta">{{ $ticket['passenger_type'] }}{{ $ticket['passenger_id'] ? ' - ID ' . $ticket['passenger_id'] : '' }}</span></td>
                                <td data-label="Payment">{{ $ticket['payment_method'] }}</td>
                                <td data-label="Validated">
                                    <strong>{{ $ticket['validated_by'] ?: '-' }}</strong>
                                    @if ($ticket['validated_at'])<span class="validation-meta">{{ $ticket['validated_at'] }}</span>@endif
                                </td>
                                <td data-label="Status"><span class="validation-status {{ $ticket['validated'] ? 'is-validated' : 'is-pending' }}">{{ $ticket['validated'] ? 'Validated' : 'To Validate' }}</span></td>
                                <td data-label="Action">
                                    <div class="validation-actions">
                                        <button type="button" class="btn btn-sm btn-outline--primary validation-details-btn"
                                            data-url="{{ $ticket['details_url'] }}" title="View and validate ticket">
                                            <i class="las {{ $ticket['validated'] ? 'la-eye' : 'la-check-circle' }}"></i>
                                            {{ $ticket['validated'] ? 'Details' : 'Validate' }}
                                        </button>
                                        <a href="{{ $ticket['rebook_url'] }}" class="btn btn-sm btn-outline--primary" title="Rebook ticket"><i class="las la-exchange-alt"></i></a>
                                        <a href="{{ $ticket['refund_url'] }}" class="btn btn-sm btn-outline--warning" title="Refund ticket"><i class="las la-undo-alt"></i></a>
                                        <a href="{{ $ticket['cancel_url'] }}" class="btn btn-sm btn-outline--danger" title="Cancel ticket"><i class="las la-ban"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="text-center text-muted py-5">No paid online tickets match the selected filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($tickets->hasPages())
            <div class="card-footer py-4">{{ paginateLinks($tickets) }}</div>
        @endif
    </div>

    <div class="modal fade" id="onlineValidationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered validation-dialog">
            <div class="modal-content validation-modal-content">
                <div class="modal-header">
                    <div><h5 class="modal-title" id="validationModalTitle">Ticket</h5><span class="validation-modal-badge" id="validationModalBadge">To Validate</span></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="validationLoading" class="text-center py-5"><div class="spinner-border text-primary"></div></div>
                    <div id="validationDetails" class="d-none"></div>
                </div>
                <div class="modal-footer validation-modal-footer">
                    <a href="#" target="_blank" rel="noopener" class="validation-print-link d-none" id="validationPrint"><i class="las la-eye"></i> View / Print</a>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn--warning d-none" id="openDiscountBtn"><i class="las la-percentage"></i> Apply Discount</button>
                        <button type="button" class="btn btn--success d-none" id="validateTicketBtn"><i class="las la-check-circle"></i> Validate Ticket</button>
                        <button type="button" class="btn btn-light d-none" data-bs-dismiss="modal" id="validationDoneBtn">Done</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="discountOverrideModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered validation-dialog">
            <form class="modal-content validation-modal-content" id="discountOverrideForm">
                <div class="modal-header"><h5 class="modal-title">Apply Discount Override</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <p class="validation-help" id="discountPassenger"></p>
                    <label class="validation-label">Discount Type</label>
                    <div class="discount-options" id="discountOptions"></div>
                    <div class="mb-3">
                        <label class="validation-label" for="discountPassengerId">ID Number</label>
                        <input type="text" class="form-control" id="discountPassengerId" name="passenger_id" placeholder="Enter passenger ID number" required>
                    </div>
                    <div class="discount-computation">
                        <div><span>Original fare (collected online)</span><strong id="discountOriginalFare">-</strong></div>
                        <div><span id="discountFareLabel">Discounted fare</span><strong id="discountedFare">-</strong></div>
                        <div class="discount-credit"><span>Override credit</span><strong id="discountCredit">-</strong></div>
                    </div>
                    <div class="mb-3">
                        <label class="validation-label" for="discountReason">Reason</label>
                        <textarea class="form-control" id="discountReason" name="reason" rows="2" placeholder="Reason for applying the discount"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="validation-label" for="discountApprovalRemarks">Approval Remarks <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control" id="discountApprovalRemarks" name="approval_remarks" placeholder="Optional approval remarks">
                    </div>
                    <div>
                        <label class="validation-label" for="discountAuthorizationCode"><i class="las la-key text--primary"></i> Authorization Code</label>
                        <input type="password" class="form-control" id="discountAuthorizationCode" name="authorization_code" placeholder="Authorized personnel code" autocomplete="new-password" required>
                        <div class="invalid-feedback" id="discountError"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" id="discountBackBtn"><i class="las la-arrow-left"></i> Back</button><button type="submit" class="btn btn--primary" id="applyDiscountBtn">Apply Discount</button></div>
            </form>
        </div>
    </div>
@endsection

@push('style')
<style>
    .online-validation-toolbar{align-items:center;display:flex;gap:14px;justify-content:space-between;margin-bottom:14px}.online-validation-tabs{background:#f1f2f5;border:1px solid #dfe2e7;border-radius:7px;display:flex;padding:3px}.online-validation-tab{border-radius:5px;color:#4d5561;font-size:12px;font-weight:600;padding:8px 12px}.online-validation-tab span{background:#e2e5e9;border-radius:999px;font-size:10px;margin-left:3px;padding:2px 6px}.online-validation-tab.is-active{background:var(--primary-color,#df2a82);color:#fff!important}.online-validation-tab.is-active span{background:rgba(255,255,255,.22);color:#fff}.online-validation-search{max-width:360px;position:relative;width:100%}.online-validation-search i{color:#8b929c;font-size:18px;left:12px;position:absolute;top:12px}.online-validation-search input{background:#f7f8fa;border:1px solid #d4d8df;border-radius:7px;height:42px;padding:0 12px 0 38px;width:100%}.online-validation-table{min-width:1580px}.online-validation-table th{white-space:nowrap}.online-validation-table td{font-size:12px;vertical-align:top}.validation-meta{color:#7d8490;display:block;font-size:10px;margin-top:2px}.validation-discount{color:#c56b00}.validation-status{border:1px solid;border-radius:999px;display:inline-flex;font-size:10px;font-weight:700;padding:4px 9px;white-space:nowrap}.validation-status.is-validated{background:#eaf8f0;border-color:#b5e3c7;color:#168044}.validation-status.is-pending{background:#eaf7fc;border-color:#afe0ef;color:#19738d}.validation-actions{display:flex;gap:5px}.validation-dialog{max-width:610px}.validation-modal-content{border:0;border-radius:10px;box-shadow:0 24px 65px rgba(0,0,0,.3);overflow:hidden}.validation-modal-content .modal-header{align-items:flex-start;border-color:#e7e9ed;padding:18px 20px}.validation-modal-content .modal-title{font-size:17px;font-weight:700;margin-bottom:7px}.validation-modal-content .modal-body{max-height:68vh;overflow-y:auto;padding:16px 20px}.validation-modal-badge{background:#eaf7fc;border:1px solid #afe0ef;border-radius:999px;color:#19738d;font-size:10px;font-weight:700;padding:4px 9px}.validation-modal-badge.is-validated{background:#eaf8f0;border-color:#b5e3c7;color:#168044}.validation-detail-group{border:1px solid #e1e4e9;border-radius:8px;overflow:hidden}.validation-detail-row{align-items:flex-start;border-bottom:1px solid #e9ebef;display:flex;gap:18px;justify-content:space-between;padding:10px 12px}.validation-detail-row:last-child{border-bottom:0}.validation-detail-row span{color:#747d8b;font-size:11px}.validation-detail-row strong{color:#222c3a;font-size:12px;max-width:68%;text-align:right}.validation-detail-row .negative{color:#c16900}.validation-modal-footer{align-items:center}.validation-print-link{color:#525b68;font-size:12px}.validation-help{color:#687180;font-size:12px}.validation-label{color:#4b5360;display:block;font-size:12px;font-weight:600;margin-bottom:6px}.discount-options{display:flex;flex-wrap:wrap;gap:7px;margin-bottom:16px}.discount-option{background:#fff;border:1px solid #d5d9df;border-radius:999px;color:#4d5561;font-size:11px;padding:7px 12px}.discount-option.is-active{background:#fff5fa;border-color:var(--primary-color,#df2a82);color:var(--primary-color,#df2a82);font-weight:700}.discount-computation{background:#f7f8fa;border:1px solid #e1e4e9;border-radius:8px;margin-bottom:16px;padding:10px 12px}.discount-computation div{display:flex;font-size:12px;justify-content:space-between;padding:5px 0}.discount-computation .discount-credit{border-top:1px solid #e1e4e9;color:#bd6500;margin-top:4px;padding-top:9px}@media(max-width:767px){.online-validation-toolbar{align-items:stretch;flex-direction:column}.online-validation-search{max-width:none}.online-validation-tabs{overflow-x:auto}.validation-modal-footer{align-items:stretch;flex-direction:column}.validation-modal-footer .ms-auto{margin-left:0!important;width:100%}.validation-modal-footer .btn{flex:1}}
</style>
@endpush

@push('script')
<script>
(function($){
    const detailsModal=new bootstrap.Modal(document.getElementById('onlineValidationModal'));
    const discountModal=new bootstrap.Modal(document.getElementById('discountOverrideModal'));
    const money=new Intl.NumberFormat('en-PH',{style:'currency',currency:'PHP'});
    let current=null;
    let selectedDiscount=null;
    const escape=value=>$('<div>').text(value??'-').html();
    const row=(label,value,className='')=>`<div class="validation-detail-row"><span>${escape(label)}</span><strong class="${className}">${escape(value)}</strong></div>`;
    function errorMessage(xhr){return xhr.responseJSON?.errors?Object.values(xhr.responseJSON.errors).flat()[0]:(xhr.responseJSON?.message||'Unable to complete the request.');}
    function render(ticket){
        current=ticket;
        $('#validationModalTitle').text(`Ticket - ${ticket.pnr}`);
        $('#validationModalBadge').text(ticket.validated?'Validated':'Paid - To Validate').toggleClass('is-validated',ticket.validated);
        const details=[
            row('Reference No.',ticket.reference_no),row('Payment Request No.',ticket.request_no),row('PNR',ticket.pnr),
            row('Passenger',ticket.passenger_name),row('Passenger Type',ticket.passenger_type+(ticket.passenger_id?` - ID ${ticket.passenger_id}`:'')),
            row('Trip',`${ticket.trip_class} - ${ticket.trip_route}`),row('Journey',`${ticket.journey_date} - ${ticket.departure_time}`),
            row('Seat No.',ticket.seat),row('Drop-Off',`${ticket.drop_off}${ticket.km_post?` - KM ${ticket.km_post}`:''}`),
            row('Fare Paid Online',money.format(ticket.original_fare)),
        ];
        if(ticket.discount_amount>0){details.push(row('Discount Override',`-${money.format(ticket.discount_amount)} (${ticket.discount_name})`,'negative'),row('Net After Override',money.format(ticket.net_fare)),row('Authorized By',ticket.discount_authorized_by||'-'),row('Authorization Date & Time',ticket.discount_authorized_at||'-'),row('Approval Remarks',ticket.approval_remarks||'-'));}
        details.push(row('Payment',`${ticket.payment_method} - ${ticket.payment_status}`),row('Validation',ticket.validated?`Validated by ${ticket.validated_by} - ${ticket.validated_at}`:'Not yet validated'));
        $('#validationDetails').html(`<div class="validation-detail-group">${details.join('')}</div>`).removeClass('d-none');
        $('#validationLoading').addClass('d-none');
        $('#validationPrint').attr('href',ticket.print_url).toggleClass('d-none',!ticket.validated);
        $('#openDiscountBtn').toggleClass('d-none',ticket.validated||ticket.discount_amount>0);
        $('#validateTicketBtn').toggleClass('d-none',ticket.validated).data('url',ticket.validate_url);
        $('#validationDoneBtn').toggleClass('d-none',!ticket.validated);
    }
    function load(url){
        $('#validationLoading').removeClass('d-none');$('#validationDetails').addClass('d-none').empty();
        $('#openDiscountBtn,#validateTicketBtn,#validationDoneBtn,#validationPrint').addClass('d-none');detailsModal.show();
        $.getJSON(url).done(render).fail(xhr=>{notify('error',errorMessage(xhr));detailsModal.hide();});
    }
    $(document).on('click','.validation-details-btn',function(){load($(this).data('url'));});
    $('#openDiscountBtn').on('click',function(){
        selectedDiscount=null;$('#discountPassenger').text(`${current.passenger_name} - PNR ${current.pnr} - Ref. ${current.reference_no}`);
        $('#discountPassengerId,#discountReason,#discountApprovalRemarks,#discountAuthorizationCode').val('');$('#discountAuthorizationCode').removeClass('is-invalid');$('#discountError').text('');
        $('#discountOriginalFare').text(money.format(current.original_fare));$('#discountedFare,#discountCredit').text('-');
        $('#discountOptions').html((current.discounts||[]).map(d=>`<button type="button" class="discount-option" data-id="${d.id}" data-name="${escape(d.name)}" data-percentage="${d.percentage}">${escape(d.name)} - ${Number(d.percentage)}%</button>`).join(''));
        detailsModal.hide();discountModal.show();
    });
    $(document).on('click','.discount-option',function(){
        $('.discount-option').removeClass('is-active');$(this).addClass('is-active');selectedDiscount={id:$(this).data('id'),name:$(this).data('name'),percentage:Number($(this).data('percentage'))};
        const amount=current.original_fare*(selectedDiscount.percentage/100);$('#discountFareLabel').text(`Discounted fare (${selectedDiscount.name})`);$('#discountedFare').text(money.format(current.original_fare-amount));$('#discountCredit').text(`-${money.format(amount)}`);
    });
    $('#discountBackBtn').on('click',function(){discountModal.hide();detailsModal.show();});
    $('#discountOverrideModal').on('hidden.bs.modal',function(){$('#discountAuthorizationCode').val('').attr('type','password');});
    $('#discountOverrideForm').on('submit',function(event){
        event.preventDefault();if(!selectedDiscount){notify('error','Select a discount type.');return;}
        const button=$('#applyDiscountBtn').prop('disabled',true);$('#discountError').text('');
        $.post(current.discount_url,{_token:'{{ csrf_token() }}',discount_id:selectedDiscount.id,passenger_id:$('#discountPassengerId').val(),reason:$('#discountReason').val(),approval_remarks:$('#discountApprovalRemarks').val(),authorization_code:$('#discountAuthorizationCode').val()})
            .done(result=>{notify('success',result.message);current=result.ticket;discountModal.hide();detailsModal.show();render(current);})
            .fail(xhr=>{$('#discountError').text(errorMessage(xhr));$('#discountAuthorizationCode').addClass('is-invalid').val('');notify('error',errorMessage(xhr));})
            .always(()=>button.prop('disabled',false));
    });
    $('#validateTicketBtn').on('click',function(){
        const button=$(this).prop('disabled',true);$.post($(this).data('url'),{_token:'{{ csrf_token() }}'})
            .done(result=>{notify('success',result.message);window.open(result.print_url,'_blank','noopener');setTimeout(()=>window.location.reload(),500);})
            .fail(xhr=>{notify('error',errorMessage(xhr));button.prop('disabled',false);});
    });
})(jQuery);
</script>
@endpush
