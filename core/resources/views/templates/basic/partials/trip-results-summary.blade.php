<p class="trip-results-summary">
    @if ($trips->total() > 0)
        <strong>{{ $trips->total() }} {{ trans_choice('departure|departures', $trips->total()) }}</strong>
        @if ($selectedDestinationCounter)
            @lang('to') <span>{{ $selectedDestinationCounter->name }}</span>
        @else
            @lang('available')
        @endif
    @else
        <strong>@lang('No departures available')</strong>
    @endif
</p>
