@php
    use Carbon\Carbon;

    $kiosk_id = request()->kiosk_id;
    $date_of_journey = request('date_of_journey')
        ? Carbon::parse(request('date_of_journey'))->format('Y-m-d')
        : now()->format('Y-m-d');
    $dateOfJourneyQuery = Carbon::parse($date_of_journey)->format('m/d/Y');
@endphp

<div class="ticket-wrapper" data-journey-date="{{ $date_of_journey }}">
    @forelse ($trips as $trip)
        @php
            $start = Carbon::parse($trip->schedule->start_from);
            $end = Carbon::parse($trip->schedule->end_at);

            if ($end->lt($start)) {
                $end->addDay();
            }

            $requestedPickupId = (string) (request('pickup') ?: request('counter_id') ?: $trip->start_from);
            $requestedDestinationId = (string) (request('destination') ?: request('selected_destination') ?: '');
            $requestedDropId = $requestedDestinationId ?: (string) $trip->end_to;
            $unavailableSeatIds = app(App\Services\SeatConflictService::class)->unavailableSeats(
                $trip,
                $date_of_journey,
                $requestedPickupId,
                $requestedDropId,
            );
            $available_seats_ctr = app(App\Services\SeatLayoutService::class)->availableSeatCount(
                $trip->fleetType,
                ['booked' => $unavailableSeatIds],
            );

            $stoppageArr = $trip->route->stoppages ?? [];
            $routeSequence = App\Models\Counter::routeStoppages($stoppageArr);
            $isFullyBooked = $available_seats_ctr < 1;
            $routeStopIds = $routeSequence->pluck('id')->map(fn($id) => (string) $id)->values();
            $displayDrop = $requestedDestinationId
                ? $routeSequence->first(fn($counter) => (string) $counter->id === $requestedDestinationId)
                : null;
            $displayDrop = $displayDrop ?: $trip->endTo;
            $displayDropId = (string) $displayDrop->id;

            $ticket_price = $ticketPrices->get($trip->vehicle_route_id . ':' . $trip->fleet_type_id);
            $prices = $ticket_price?->prices ?? collect();
            $segmentPrice = null;

            if ($requestedDestinationId) {
                $segmentPrice = $prices->first(function ($price) use ($requestedPickupId, $displayDropId) {
                    $segment = array_values(array_map('strval', (array) ($price->source_destination ?? [])));

                    return $segment === [$requestedPickupId, $displayDropId] ||
                        $segment === [$displayDropId, $requestedPickupId];
                });
            }

            $minPrice = $prices->where('price', '>', 0)->min('price') ?? 0;
            $maxPrice = $prices->max('price') ?? $minPrice;
            $displayPrice = $segmentPrice?->price;

            $tripStartIndex = $routeStopIds->search((string) $trip->start_from);
            $tripEndIndex = $routeStopIds->search((string) $trip->end_to);
            $pickupIndex = $routeStopIds->search($requestedPickupId);
            $dropIndex = $routeStopIds->search($displayDropId);
            $fullTripMinutes = max((int) round($start->diffInMinutes($end)), 0);
            $arrivalMinutes = $fullTripMinutes;
            $displayDurationMinutes = $fullTripMinutes;

            if ($tripStartIndex !== false && $tripEndIndex !== false && $dropIndex !== false) {
                $totalLegs = abs($tripEndIndex - $tripStartIndex);
                if ($totalLegs > 0) {
                    $arrivalMinutes = (int) round(
                        ($fullTripMinutes * abs($dropIndex - $tripStartIndex)) / $totalLegs,
                    );
                    if ($pickupIndex !== false) {
                        $displayDurationMinutes = (int) round(
                            ($fullTripMinutes * abs($dropIndex - $pickupIndex)) / $totalLegs,
                        );
                    }
                }
            }

            $durationHours = intdiv($displayDurationMinutes, 60);
            $durationRemainder = $displayDurationMinutes % 60;
            $displayDurationLabel = trim(
                ($durationHours ? $durationHours . ' ' . ($durationHours === 1 ? 'hr' : 'hrs') : '') .
                    ($durationRemainder ? ' ' . $durationRemainder . ' mins' : ''),
            ) ?: '0 mins';
            $departureAt = Carbon::parse($date_of_journey . ' ' . $trip->schedule->start_from);
            $estimatedArrivalAt = $departureAt->copy()->addMinutes($arrivalMinutes);
            $selectSeatUrl = route('ticket.seats', [
                $trip->id,
                slug($trip->title),
                'start_from' => $requestedPickupId,
                'end_to' => $trip->end_to,
                'dropping_point' => $requestedDestinationId ?: $trip->end_to,
                'kiosk_id' => $kiosk_id,
                'date_of_journey' => $dateOfJourneyQuery,
            ]);
            $routeId = uniqid('route_');
            $totalStops = $routeSequence?->count() ?? 0;
            $shouldCollapse = $totalStops >= 5;
            $availableSeatLabel = $available_seats_ctr === 1 ? 'Seat Available' : 'Seats Available';
        @endphp

        <div class="ticket-item js-trip-card {{ $isFullyBooked ? 'is-disabled' : '' }}"
            data-trip-id="{{ $trip->id }}"
            @unless ($isFullyBooked) data-href="{{ $selectSeatUrl }}" tabindex="0" role="link" @endunless
            aria-disabled="{{ $isFullyBooked ? 'true' : 'false' }}">
            <div class="trip-card-top">
                <div>
                    <h5 class="trip-route-title">{{ __($trip->route->name) }}</h5>
                    <p class="trip-time-main">{{ showDateTime($trip->schedule->start_from, 'h:i A') }}</p>
                    <div class="trip-duration">
                        <span class="trip-duration__item">
                            <i class="las la-calendar-alt"></i>
                            {{ Carbon::parse($date_of_journey)->format('D, M d, Y') }}
                        </span>
                        <span class="trip-duration__separator" aria-hidden="true"></span>
                        <span class="trip-duration__item">
                            <i class="las la-clock"></i>
                            {{ $displayDurationLabel }}
                        </span>
                        <span class="trip-duration__separator" aria-hidden="true"></span>
                        <span class="trip-duration__item">
                            @lang('Arrives') {{ $estimatedArrivalAt->format('h:i A') }}
                        </span>
                    </div>
                </div>
                <div class="trip-card-price">
                    <span class="fleet-pill">
                        <i class="las la-bus"></i> {{ __($trip->fleetType->name) }}
                    </span>
                    <p class="trip-price">
                        @if ($requestedDestinationId && $displayPrice !== null)
                            {{ showAmount($displayPrice) }}
                        @elseif ($requestedDestinationId)
                            <span class="trip-price-unavailable">@lang('Fare unavailable')</span>
                        @else
                            {{ showAmount($maxPrice) }}
                        @endif
                    </p>
                    @if (!$requestedDestinationId && $minPrice > 0 && $minPrice != $maxPrice)
                        <div class="trip-price-range">{{ showAmount($minPrice) }} - {{ showAmount($maxPrice) }}</div>
                    @endif
                </div>
            </div>

            <div class="trip-card-route">
                <div class="trip-point">
                    <small>@lang('Pickup')</small>
                    <strong>{{ __($trip->startFrom->name) }}</strong>
                </div>
                <div class="trip-route-arrow"><i class="las la-arrow-right"></i></div>
                <div class="trip-point trip-point--end">
                    <small>@lang('Drop-off')</small>
                    <strong>{{ __($displayDrop->name) }}</strong>
                </div>
            </div>

            <div class="trip-card-meta">
                <span><i class="las la-chair"></i>{{ __($trip->fleetType->seat_layout) }}</span>
                @if ($trip->fleetType->facilities)
                    @foreach (collect($trip->fleetType->facilities)->take(5) as $facility)
                        <span><i class="las la-check-circle"></i>{{ __($facility) }}</span>
                    @endforeach
                @endif
            </div>

            <div class="trip-card-actions">
                <div class="trip-availability {{ $isFullyBooked ? 'is-full' : 'is-available' }}">
                    <i class="las {{ $isFullyBooked ? 'la-times-circle' : 'la-couch' }}"></i>
                    @if ($isFullyBooked)
                        @lang('Fully Booked')
                    @else
                        {{ $available_seats_ctr }} {{ __($availableSeatLabel) }}
                    @endif
                </div>

                @if ($isFullyBooked)
                    <span class="trip-select-btn is-disabled">@lang('Unavailable')</span>
                @else
                    <a class="trip-select-btn" href="{{ $selectSeatUrl }}">@lang('Select Seat')</a>
                @endif
            </div>

            @if ($routeSequence && $routeSequence->count() > 0)
                <div class="route-details">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="d-block text-muted"
                            style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                            <i class="las la-map-marked-alt"></i> @lang('Route')
                        </span>

                        @if ($shouldCollapse)
                            <a href="javascript:void(0)" class="route-details__toggle"
                                onclick="toggleRouteStops('{{ $routeId }}')" data-trip-card-ignore>
                                <span id="text-{{ $routeId }}">@lang('View Route')</span>
                            </a>
                        @endif
                    </div>

                    <div class="d-flex align-items-center flex-wrap gap-2 user-select-none" style="font-size: 12px;">
                        @foreach ($routeSequence as $stop)
                            @if ($loop->first)
                                <span class="badge bg-success px-2 py-1">{{ $stop->name }}</span>

                                @if ($totalStops > 1)
                                    <i class="las la-long-arrow-alt-right text-muted fs-6"></i>
                                @endif

                                @if ($shouldCollapse)
                                    <span class="badge bg-light text-muted border px-2 py-1 dots-{{ $routeId }}">
                                        +{{ $totalStops - 2 }} @lang('Locations')
                                    </span>
                                    <i class="las la-long-arrow-alt-right text-muted fs-6 dots-{{ $routeId }}"></i>
                                @endif
                            @elseif ($loop->last)
                                <span class="badge bg-danger px-2 py-1">{{ $stop->name }}</span>
                            @else
                                <span class="badge bg-secondary px-2 py-1 stops-{{ $routeId }} {{ $shouldCollapse ? 'd-none' : '' }}">
                                    {{ $stop->name }}
                                </span>
                                <i class="las la-long-arrow-alt-right text-muted fs-6 stops-{{ $routeId }} {{ $shouldCollapse ? 'd-none' : '' }}"></i>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="ticket-item trip-item-empty">
            <h5>{{ __($emptyMessage) }}</h5>
        </div>
    @endforelse

    @if ($trips->hasPages())
        <div class="custom-pagination">
            {{ paginateLinks($trips) }}
        </div>
    @endif
</div>
