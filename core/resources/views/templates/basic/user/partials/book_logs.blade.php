<div class="booking-table-wrapper">
    <table class="booking-table">
        <thead>
            <tr>
                <th>@lang('PNR Number')</th>
                <th>@lang('AC / Non-Ac')</th>
                <th>@lang('Starting Point')</th>
                <th>@lang('Dropping Point')</th>
                <th>@lang('Journey Date')</th>
                <th>@lang('Pickup Time')</th>
                <th>@lang('Booked Seats')</th>
                <th>@lang('Ticket Status')</th>
                <th>@lang('Fare')</th>
                <th>@lang('Payment Method')</th>
                <th>@lang('Payment Status')</th>
                <th>@lang('Action')</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bookedTickets as $item)
                <tr>
                    <td class="ticket-no" data-label="@lang('PNR Number')">{{ __($item->pnr_number) }}</td>
                    <td data-label="@lang('AC / Non-Ac')">
                        {{ $item->trip->fleetType->has_ac ? 'AC' : 'Non-Ac' }}</td>
                    <td class="pickup" data-label="@lang('Starting Point')">{{ __($item->pickup->name) }}</td>
                    <td class="drop" data-label="@lang('Dropping Point')">{{ __($item->drop->name) }}</td>
                    <td class="date" data-label="@lang('Journey Date')">
                        {{ __(showDateTime($item->date_of_journey, 'd M, Y')) }}</td>
                    <td class="time" data-label="@lang('Pickup Time')">
                        {{ __(showDateTime($item->trip->schedule->start_from, 'H:i a')) }}</td>
                    <td class="seats" data-label="@lang('Booked Seats')">{{ __(implode(',', $item->seats)) }}
                    </td>
                    <td data-label="@lang('Status')">
                        @if ($item->status == 1)
                            <span class="badge badge--success"> @lang('Booked')</span>
                        @elseif($item->status == 2)
                            <span class="badge badge--warning"> @lang('Pending')</span>
                        @else
                            <span class="badge badge--danger"> @lang('Rejected')</span>
                        @endif
                    </td>
                    <td class="fare" data-label="@lang('Fare')">
                        {{ __(showAmount($item->sub_total)) }}</td>
                    <td>
                        @if (@$item->deposit->gateway->name == 'Paynamics')
                            <div>{{ __(getPaynamicsPChannel(@$item->deposit->pchannel, true)) }}</div>
                        @else
                            {{ __(@$item->deposit->gateway->name) }}
                        @endif
                    </td>
                    <td>
                        @if (@$item->deposit->expiry_limit && strtotime(@$item->deposit->expiry_limit) < strtotime(date('Y-m-d H:i')))
                            <span class="badge badge--danger">{{ __('Expired') }}</span>
                        @else
                            {{ paymentStatus(@$item->deposit->status) }}
                        @endif
                    </td>
                    <td class="action" data-label="@lang('Action')">
                        <div class="action-button-wrapper gap-2">
                            {{-- @if ($item->date_of_journey >= \Carbon\Carbon::today()->format('Y-m-d') && $item->status == 1)
                                <a href="{{ route('user.ticket.print', $item->id) }}" target="_blank" class="print"><i
                                        class="las la-print"></i></a>
                            @else
                                <a href="javascript::void(0)" class="checkinfo" data-info="{{ $item }}"
                                    data-bs-toggle="modal" data-bs-target="#infoModal"><i
                                        class="las la-info-circle"></i></a>
                            @endif --}}
                            @if (@$item->status == App\Constants\Status::BOOKED_APPROVED || !isExpired(@$item->deposit->expiry_limit))
                                <a href="{{ route('user.ticket.print', $item->id) }}" target="_blank" class="print"><i
                                        class="las la-print"></i></a>
                            @endif
                            <a href="javascript::void(0)" class="checkinfo" data-info="{{ $item }}"
                                data-bs-toggle="modal" data-bs-target="#infoModal"><i
                                    class="las la-info-circle"></i></a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center" colspan="100%">{{ $emptyMessage }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
