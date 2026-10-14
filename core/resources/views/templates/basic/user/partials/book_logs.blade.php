<div class="account-tickets-heading">
    <h2>@lang('My Tickets')</h2>
    <p>
        @lang('Hi') {{ auth()->user()->firstname ?: auth()->user()->username }},
        @lang("here's everything you've booked with") {{ __(gs('site_name')) }}.
    </p>
</div>

<div class="ticket-summary-grid">
    <div class="ticket-summary ticket-summary--booked">
        <div>
            <span>@lang('Total Booked Tickets')</span>
            <strong>{{ $widget['booked'] }}</strong>
        </div>
        <i class="las la-check-circle" aria-hidden="true"></i>
    </div>
    <div class="ticket-summary ticket-summary--pending">
        <div>
            <span>@lang('Total Pending Tickets')</span>
            <strong>{{ $widget['pending'] }}</strong>
        </div>
        <i class="las la-clock" aria-hidden="true"></i>
    </div>
    <div class="ticket-summary ticket-summary--rejected">
        <div>
            <span>@lang('Total Rejected Tickets')</span>
            <strong>{{ $widget['rejected'] }}</strong>
        </div>
        <i class="las la-times-circle" aria-hidden="true"></i>
    </div>
</div>

<div class="account-ticket-list">
    <h3>@lang('All Tickets') <span>{{ $bookedTickets->total() }}</span></h3>

    @if ($bookedTickets->isEmpty())
        <div class="ticket-empty-state">
            <i class="las la-ticket-alt" aria-hidden="true"></i>
            <p>{{ __($emptyMessage) }}</p>
        </div>
    @else
        <div class="ticket-table-shell d-none d-lg-block">
            <div class="table-responsive">
                <table class="account-ticket-table">
                    <thead>
                        <tr>
                            <th>@lang('PNR')</th>
                            <th>@lang('Origin')</th>
                            <th>@lang('Destination')</th>
                            <th>@lang('Travel Date')</th>
                            <th>@lang('Departure')</th>
                            <th>@lang('Bus Class')</th>
                            <th>@lang('Seats')</th>
                            <th>@lang('Ticket Status')</th>
                            <th>@lang('Fare')</th>
                            <th>@lang('Payment Method')</th>
                            <th>@lang('Payment Status')</th>
                            <th class="text-end">@lang('Actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookedTickets as $ticket)
                            <tr class="js-ticket-card" role="button" tabindex="0"
                                aria-label="@lang('View details for ticket') {{ $ticket['pnr'] }}"
                                data-ticket-id="{{ $ticket['id'] }}">
                                <td><strong>{{ $ticket['pnr'] }}</strong></td>
                                <td class="ticket-route-point">{{ $ticket['origin'] }}</td>
                                <td class="ticket-route-point">{{ $ticket['destination'] }}</td>
                                <td>{{ $ticket['travel_date'] }}</td>
                                <td class="ticket-time"><i class="las la-clock"></i> {{ $ticket['departure_time'] }}</td>
                                <td>{{ $ticket['bus_class'] }}</td>
                                <td><strong>{{ $ticket['seats'] }}</strong></td>
                                <td><span class="ticket-status ticket-status--{{ $ticket['ticket_tone'] }}">{{ __($ticket['ticket_status']) }}</span></td>
                                <td class="ticket-fare">{{ $ticket['fare'] }}</td>
                                <td>{{ $ticket['payment_method'] }}</td>
                                <td><span class="ticket-status ticket-status--{{ $ticket['payment_tone'] }}">{{ __($ticket['payment_status']) }}</span></td>
                                <td>
                                    <div class="ticket-actions justify-content-end">
                                        <button type="button" class="ticket-icon-action js-ticket-details-button" data-bs-toggle="modal" data-bs-target="#infoModal" title="@lang('View details')" aria-label="@lang('View details')"><i class="las la-eye"></i></button>
                                        @if ($ticket['can_pay'])
                                            <a class="ticket-pay-action" href="{{ $ticket['pay_url'] }}">@lang('Pay Now')</a>
                                        @endif
                                        @if ($ticket['can_access_voucher'])
                                            <a class="ticket-icon-action" href="{{ $ticket['print_url'] }}" target="_blank" rel="noopener" title="@lang('Print ticket')" aria-label="@lang('Print ticket')"><i class="las la-print"></i></a>
                                            <a class="ticket-icon-action" href="{{ $ticket['download_url'] }}" title="@lang('Download ticket')" aria-label="@lang('Download ticket')"><i class="las la-download"></i></a>
                                            <a class="ticket-icon-action" href="{{ $ticket['voucher_url'] }}" target="_blank" rel="noopener" title="@lang('Open e-voucher')" aria-label="@lang('Open e-voucher')"><i class="las la-receipt"></i></a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ticket-mobile-list d-lg-none">
            @foreach ($bookedTickets as $ticket)
                <article class="mobile-ticket-card js-ticket-card" role="button" tabindex="0"
                    aria-label="@lang('View details for ticket') {{ $ticket['pnr'] }}"
                    data-ticket-id="{{ $ticket['id'] }}">
                    <div class="mobile-ticket-card__summary">
                        <div class="mobile-ticket-card__topline">
                            <div class="mobile-ticket-card__pnr"><span>@lang('PNR')</span> <strong>{{ $ticket['pnr'] }}</strong></div>
                            <div class="mobile-ticket-card__status">
                                <span class="ticket-status ticket-status--{{ $ticket['ticket_tone'] }}">{{ __($ticket['ticket_status']) }}</span>
                                <button type="button" class="mobile-ticket-toggle js-ticket-expand" aria-expanded="false" aria-label="@lang('Expand ticket details')">
                                    <i class="las la-angle-down"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mobile-ticket-card__route">
                            <strong>{{ $ticket['origin'] }}</strong>
                            <i class="las la-arrow-right" aria-hidden="true"></i>
                            <strong>{{ $ticket['destination'] }}</strong>
                            <span>{{ $ticket['fare'] }}</span>
                        </div>
                        <div class="mobile-ticket-card__schedule">
                            <span><i class="las la-calendar"></i> {{ $ticket['travel_date'] }}</span>
                            <span><i class="las la-clock"></i> {{ $ticket['departure_time'] }}</span>
                        </div>
                    </div>
                    <div class="mobile-ticket-card__details" hidden>
                        <div><span>@lang('Bus Class')</span><strong>{{ $ticket['bus_class'] }}</strong></div>
                        <div><span>@lang('Booked Seats')</span><strong class="text--base">{{ $ticket['seats'] }}</strong></div>
                        <div><span>@lang('Payment Method')</span><strong>{{ $ticket['payment_method'] }}</strong></div>
                        <div><span>@lang('Payment Status')</span><span class="ticket-status ticket-status--{{ $ticket['payment_tone'] }}">{{ __($ticket['payment_status']) }}</span></div>
                        <div>
                            <span>@lang('Action')</span>
                            <div class="ticket-actions">
                                <button type="button" class="ticket-icon-action js-ticket-details-button" data-bs-toggle="modal" data-bs-target="#infoModal" title="@lang('View details')" aria-label="@lang('View details')"><i class="las la-eye"></i></button>
                                @if ($ticket['can_pay'])
                                    <a class="ticket-pay-action" href="{{ $ticket['pay_url'] }}">@lang('Pay Now')</a>
                                @endif
                                @if ($ticket['can_access_voucher'])
                                    <a class="ticket-icon-action" href="{{ $ticket['print_url'] }}" target="_blank" rel="noopener" title="@lang('Print ticket')" aria-label="@lang('Print ticket')"><i class="las la-print"></i></a>
                                    <a class="ticket-icon-action" href="{{ $ticket['download_url'] }}" title="@lang('Download ticket')" aria-label="@lang('Download ticket')"><i class="las la-download"></i></a>
                                    <a class="ticket-icon-action" href="{{ $ticket['voucher_url'] }}" target="_blank" rel="noopener" title="@lang('Open e-voucher')" aria-label="@lang('Open e-voucher')"><i class="las la-receipt"></i></a>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>

@once
    @push('style')
        <style>
            .account-tickets-heading{margin-bottom:20px}.account-tickets-heading h2{color:#162033;font-size:26px;font-weight:700;letter-spacing:0;margin:0 0 3px}.account-tickets-heading p{color:#6f7887;font-size:13px;margin:0}.ticket-summary-grid{display:grid;gap:12px;grid-template-columns:repeat(3,minmax(0,1fr));margin-bottom:28px}.ticket-summary{align-items:center;background:#fff;border-left:4px solid;border-radius:8px;box-shadow:0 1px 3px rgba(22,32,51,.08);display:flex;justify-content:space-between;min-height:92px;padding:16px 18px}.ticket-summary span{color:#596474;display:block;font-size:12px;margin-bottom:5px}.ticket-summary strong{color:#142039;display:block;font-size:26px;line-height:1}.ticket-summary>i{align-items:center;border-radius:8px;color:#fff;display:flex;font-size:21px;height:42px;justify-content:center;width:42px}.ticket-summary--booked{border-color:#25bd6b}.ticket-summary--booked>i{background:#25bd6b}.ticket-summary--pending{border-color:#f4ba25}.ticket-summary--pending>i{background:#f4ba25}.ticket-summary--rejected{border-color:#e84a4f}.ticket-summary--rejected>i{background:#e84a4f}.account-ticket-list>h3{color:#667080;font-size:12px;font-weight:700;letter-spacing:0;margin:0 0 10px;text-transform:uppercase}.account-ticket-list>h3 span{color:#9aa2ad;margin-left:4px}.ticket-table-shell{background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(22,32,51,.12);overflow:hidden}.account-ticket-table{border-collapse:collapse;min-width:1180px;width:100%}.account-ticket-table thead{background:var(--main-color)}.account-ticket-table th{color:#fff;font-size:11px;font-weight:600;padding:11px 10px;white-space:nowrap}.account-ticket-table td{border-bottom:1px solid #edf0f3;color:#364052;font-size:11px;padding:12px 10px;vertical-align:middle;white-space:nowrap}.account-ticket-table tbody tr{cursor:pointer;transition:background-color .15s ease}.account-ticket-table tbody tr:hover,.account-ticket-table tbody tr:focus{background:#f5f8f9;outline:0}.account-ticket-table tbody tr:last-child td{border-bottom:0}.ticket-route-point,.ticket-time,.ticket-fare{color:var(--main-color)!important}.ticket-fare{font-weight:700}.ticket-status{border:1px solid;border-radius:999px;display:inline-flex;font-size:10px;font-weight:600;line-height:1;padding:5px 9px;white-space:nowrap}.ticket-status--success{background:#e5faed;border-color:#bdeccb;color:#18864a}.ticket-status--warning{background:#fff7dc;border-color:#f3dda1;color:#9a6a00}.ticket-status--danger{background:#ffebec;border-color:#f3c2c5;color:#b82d35}.ticket-status--info{background:#e9f5fb;border-color:#c2e3f1;color:#21789a}.ticket-actions{align-items:center;display:flex;gap:6px}.ticket-icon-action{align-items:center;background:#fff;border:1px solid var(--main-color);border-radius:6px;color:var(--main-color);display:inline-flex;font-size:17px;height:32px;justify-content:center;padding:0;width:32px}.ticket-icon-action:hover,.ticket-icon-action:focus{background:var(--main-color);color:#fff}.ticket-pay-action{background:var(--main-color);border-radius:6px;color:#fff;font-size:10px;font-weight:600;padding:7px 9px;white-space:nowrap}.ticket-pay-action:hover{color:#fff}.ticket-empty-state{background:#fff;border-radius:8px;color:#7b8491;padding:45px 20px;text-align:center}.ticket-empty-state i{color:var(--main-color);font-size:38px}.ticket-empty-state p{margin:8px 0 0}.ticket-mobile-list{display:grid;gap:10px}.mobile-ticket-card{background:#fff;border:1px solid #edf0f3;border-radius:8px;box-shadow:0 1px 4px rgba(22,32,51,.1);cursor:pointer;overflow:hidden}.mobile-ticket-card:focus{border-color:var(--main-color);outline:0}.mobile-ticket-card__summary{padding:14px}.mobile-ticket-card__topline,.mobile-ticket-card__route,.mobile-ticket-card__schedule,.mobile-ticket-card__status{align-items:center;display:flex}.mobile-ticket-card__topline{justify-content:space-between}.mobile-ticket-card__pnr{color:#8b94a1;font-size:10px}.mobile-ticket-card__pnr span{font-weight:600;margin-right:5px;text-transform:uppercase}.mobile-ticket-card__pnr strong{color:var(--main-color);font-size:14px}.mobile-ticket-card__status{gap:5px}.mobile-ticket-toggle{background:transparent;border:0;color:#8993a0;font-size:16px;height:28px;padding:0;width:28px}.mobile-ticket-toggle i{transition:transform .2s ease}.mobile-ticket-toggle[aria-expanded=true] i{transform:rotate(180deg)}.mobile-ticket-card__route{gap:7px;margin-top:8px}.mobile-ticket-card__route strong{color:#202b3b;font-size:13px}.mobile-ticket-card__route i{color:var(--main-color)}.mobile-ticket-card__route>span{color:#d72979;font-size:14px;font-weight:700;margin-left:auto}.mobile-ticket-card__schedule{color:#596574;font-size:11px;gap:12px;margin-top:8px}.mobile-ticket-card__schedule i{color:var(--main-color);font-size:14px}.mobile-ticket-card__details{border-top:1px solid #edf0f3;padding:0 14px 10px}.mobile-ticket-card__details>div{align-items:center;border-bottom:1px solid #edf0f3;color:#667080;display:flex;font-size:12px;justify-content:space-between;min-height:42px;padding:8px 0}.mobile-ticket-card__details>div:last-child{border-bottom:0}.mobile-ticket-card__details strong{color:#263142;font-weight:500;max-width:60%;text-align:right}.ticket-account-section{background:#f3f5f7}.ticket-account-section .dashboard-wrapper{max-width:none}.ticket-account-section .custom-pagination{margin-top:24px}
            @media(max-width:767px){.ticket-summary-grid{grid-template-columns:1fr}.ticket-summary{min-height:76px}.account-tickets-heading h2{font-size:24px}}
            @media(max-width:575px){.ticket-account-section{padding-bottom:50px;padding-top:35px}.account-tickets-heading{margin-bottom:18px}.account-tickets-heading h2{font-size:22px}.account-tickets-heading p{font-size:11px}.ticket-summary-grid{gap:9px;margin-bottom:25px}.ticket-summary{padding:13px 14px}.ticket-summary span{font-size:11px}.ticket-summary strong{font-size:22px}.ticket-summary>i{height:38px;width:38px}.mobile-ticket-card__route strong{font-size:12px;min-width:0;overflow-wrap:anywhere}.mobile-ticket-card__route>span{font-size:13px;white-space:nowrap}}
        </style>
    @endpush
@endonce
