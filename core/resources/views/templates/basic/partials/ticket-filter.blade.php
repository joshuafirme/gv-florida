<form action="{{ route('search') }}" id="filterForm">
    @if (request()->kiosk_id)
        <input type="hidden" name="kiosk_id" value="{{ request()->kiosk_id }}">
    @endif
    <input type="hidden" name="counter_id" value="{{ $selected_counter }}">
    <input type="hidden" name="selected_destination" value="{{ $selected_destination }}">
    <div class="ticket-filter">
        <div class="filter-header filter-item">
            <h4 class="title mb-0">@lang('Filter')</h4>

            <div class="d-flex gap-1">
                <button class="btn btn-sm btn--base w-100 mt-3">
                    Apply
                </button>
                
                <button style="white-space: nowrap;" class="btn btn-sm btn--base-outline w-100 mt-3 reset-button">
                    @lang('Reset All')
                </button>
            </div>
        </div>

        {{-- @if ($schedules)
            <div class="filter-item">
                <h5 class="title">@lang('Schedules')</h5>
                <select class="form-control select2 search search-multiple" name="schedules[]" multiple="multiple">
                    @foreach ($schedules as $schedule)
                        @php
                            $selected = '';
                            if (request()->schedules) {
                                foreach (request()->schedules as $item) {
                                    if ($item == $schedule->id) {
                                        $selected = 'selected';
                                    }
                                }
                            }
                        @endphp
                        <option value="{{ $schedule->id }}" id="schedule.{{ $schedule->id }}" {{ $selected }}>
                            {{ showDateTime($schedule->start_from, 'h:i a') . ' - ' . showDateTime($schedule->end_at, 'h:i a') }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif --}}
        @if ($fleetType)
            <div class="filter-item">
                <h5 class="title">@lang('Vehicle Type')</h5>
                <ul class="bus-type">
                    @foreach ($fleetType as $fleet)
                        <li class="custom--checkbox">
                            <input name="fleetType[]" class="search" value="{{ $fleet->id }}"
                                id="{{ $fleet->name }}" type="checkbox"
                                @if (request()->fleetType) @foreach (request()->fleetType as $item)
                                                @if ($item == $fleet->id)
                                                checked @endif
                                @endforeach
                    @endif >
                    <label for="{{ $fleet->name }}"><span><i
                                class="las la-bus"></i>{{ __($fleet->name) }}</span></label>
                    </li>
        @endforeach
        </ul>
    </div>
    @endif
    </div>
</form>
