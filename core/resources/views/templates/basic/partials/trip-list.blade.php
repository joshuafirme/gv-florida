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
            <div class="trip-card-heading">
                <h5 class="trip-route-title">{{ __($trip->route->name) }}</h5>
                <span class="fleet-pill">
                    <i class="las la-bus"></i> {{ __($trip->fleetType->name) }}
                </span>
            </div>

            <div class="trip-card-main">
                <div class="trip-card-departure">
                    <small>@lang('Departure Time')</small>
                    <p class="trip-time-main">{{ showDateTime($trip->schedule->start_from, 'h:i A') }}</p>
                    <span class="trip-duration-text">@lang('Approx.') {{ $displayDurationLabel }}</span>
                </div>

                <div class="trip-card-route">
                    <small class="trip-card-section-label">@lang('Route')</small>
                    <div class="trip-point">
                        <span class="trip-route-dot is-origin" aria-hidden="true"></span>
                        <div>
                            <strong>{{ __($trip->startFrom->name) }}</strong>
                            <small>@lang('Origin')</small>
                        </div>
                    </div>
                    <div class="trip-point">
                        <span class="trip-route-dot is-destination" aria-hidden="true"></span>
                        <div>
                            <strong>{{ __($displayDrop->name) }}</strong>
                            <small>@lang('Drop-off Point')</small>
                        </div>
                    </div>
                </div>

                <div class="trip-card-price">
                    <small>@lang('Fare')</small>
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

            <div class="trip-availability {{ $isFullyBooked ? 'is-full' : 'is-available' }}">
                <i class="las {{ $isFullyBooked ? 'la-times-circle' : 'la-couch' }}"></i>
                @if ($isFullyBooked)
                    @lang('Fully Booked')
                @else
                    {{ $available_seats_ctr }} {{ __($availableSeatLabel) }}
                    <span class="trip-availability-separator" aria-hidden="true">&middot;</span>
                    <span>@lang('Select Seat')</span>
                    <i class="las la-arrow-right"></i>
                @endif
            </div>

            <div class="trip-card-meta">
                <strong>@lang('Amenities')</strong>
                <span><i class="las la-chair"></i>{{ __($trip->fleetType->seat_layout) }}</span>
                @if ($trip->fleetType->facilities)
                    @foreach (collect($trip->fleetType->facilities)->take(5) as $facility)
                        <span><i class="las la-check-circle"></i>{{ __($facility) }}</span>
                    @endforeach
                @endif
            </div>

            @if ($routeSequence && $routeSequence->count() > 0)
                <div class="route-details">
                    <a href="javascript:void(0)" class="route-details__toggle"
                        onclick="toggleRouteStops('{{ $routeId }}')" data-trip-card-ignore>
                        <span id="text-{{ $routeId }}">@lang('View Route')</span>
                        <i class="las la-angle-right" id="icon-{{ $routeId }}"></i>
                    </a>

                    <div class="route-details__stops d-none" id="{{ $routeId }}">
                        @foreach ($routeSequence as $stop)
                            <span class="badge {{ $loop->first ? 'bg-success' : ($loop->last ? 'bg-danger' : 'bg-secondary') }} px-2 py-1">
                                {{ $stop->name }}
                            </span>
                            @unless ($loop->last)
                                <i class="las la-long-arrow-alt-right text-muted fs-6"></i>
                            @endunless
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
