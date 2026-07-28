@php
    $isSeatList = $column === 'seat';
    $extraCount = max($ticketRows->count() - 4, 0);
@endphp

<div class="deposit-ticket-list {{ $isSeatList ? 'deposit-ticket-list--seats' : 'deposit-ticket-list--passengers' }}"
    data-deposit-list-content="{{ $depositListKey }}">
    @forelse ($ticketRows as $ticketRow)
        @php
            $isExtra = $loop->index >= 4;
            $passengerMeta = collect([
                $ticketRow['type'],
                $ticketRow['id_number'] ? 'ID ' . $ticketRow['id_number'] : null,
            ])->filter()->implode(' - ');
        @endphp
        <div class="deposit-ticket-list__row {{ $isExtra ? 'is-extra d-none' : '' }}">
            @if ($isSeatList)
                <strong title="{{ __('Seat') }} {{ formatSeatLabel($ticketRow['seat']) }}">
                    {{ formatSeatLabel($ticketRow['seat']) }}
                </strong>
            @else
                <strong title="{{ $ticketRow['name'] }}">{{ $ticketRow['name'] }}</strong>
                <span title="{{ $passengerMeta }}">{{ $passengerMeta }}</span>
            @endif
        </div>
    @empty
        <div class="deposit-ticket-list__row">
            <span>&mdash;</span>
        </div>
    @endforelse
</div>

@if ($extraCount > 0)
    <button type="button" class="deposit-ticket-list__toggle"
        data-deposit-list-toggle="{{ $depositListKey }}" aria-expanded="false">
        <span data-deposit-list-label>@lang('View more')</span>
        <span class="deposit-ticket-list__count">+{{ $extraCount }}</span>
        <i class="las la-angle-down"></i>
    </button>
@endif
