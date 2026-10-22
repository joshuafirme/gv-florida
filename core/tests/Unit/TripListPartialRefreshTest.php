<?php

namespace Tests\Unit;

use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class TripListPartialRefreshTest extends TestCase
{
    public function test_trip_list_partial_renders_the_empty_state(): void
    {
        $html = view('templates.basic.partials.trip-list', [
            'trips' => new LengthAwarePaginator([], 0, 15),
            'emptyMessage' => 'No departures available',
            'ticketPrices' => collect(),
        ])->render();

        $this->assertStringContainsString('class="ticket-wrapper"', $html);
        $this->assertStringContainsString('No departures available', $html);
    }

    public function test_trip_results_summary_uses_the_filtered_total(): void
    {
        $withResults = view('templates.basic.partials.trip-results-summary', [
            'trips' => new LengthAwarePaginator([], 5, 15),
            'selectedDestinationCounter' => null,
        ])->render();
        $withoutResults = view('templates.basic.partials.trip-results-summary', [
            'trips' => new LengthAwarePaginator([], 0, 15),
            'selectedDestinationCounter' => null,
        ])->render();

        $this->assertStringContainsString('5 departures', $withResults);
        $this->assertStringNotContainsString('No departures available', $withResults);
        $this->assertStringContainsString('No departures available', $withoutResults);
    }

    public function test_ticket_page_uses_a_scoped_skeleton_and_partial_refresh(): void
    {
        $ticketView = file_get_contents(resource_path('views/templates/basic/ticket.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/SiteController.php'));

        $this->assertStringContainsString('id="tripListRegion"', $ticketView);
        $this->assertStringContainsString('trip-list-skeleton', $ticketView);
        $this->assertStringContainsString('async function refreshTripList', $ticketView);
        $this->assertStringContainsString('payload.summary_html', $ticketView);
        $this->assertStringContainsString("'X-Requested-With': 'XMLHttpRequest'", $ticketView);
        $this->assertStringContainsString("asset('assets/global/js/dropping-points.js')", $ticketView);
        $this->assertStringContainsString("status === 'fully_booked'", $ticketView);
        $this->assertStringContainsString('isInvalidDate:', $ticketView);
        $this->assertStringContainsString('tripListRealtime.scheduleEvent', $ticketView);
        $this->assertStringContainsString("data-default-option=\"@lang('Select Destination')\"", $ticketView);
        $this->assertStringContainsString("view('Template::partials.trip-list'", $controller);
        $this->assertStringContainsString("view('Template::partials.trip-results-summary'", $controller);
    }

    public function test_dynamic_trip_cards_keep_the_gvf_226_layout_structure(): void
    {
        $partial = file_get_contents(resource_path('views/templates/basic/partials/trip-list.blade.php'));
        $ticketView = file_get_contents(resource_path('views/templates/basic/ticket.blade.php'));

        $this->assertStringContainsString('class="trip-card-heading"', $partial);
        $this->assertStringContainsString('class="trip-card-main"', $partial);
        $this->assertStringContainsString('class="trip-card-departure"', $partial);
        $this->assertStringContainsString('class="route-details__stops d-none"', $partial);
        $this->assertStringNotContainsString('class="trip-card-top"', $partial);
        $this->assertStringContainsString('padding: 13px 18px 2px;', $ticketView);
        $this->assertStringContainsString('margin: 10px 18px 0;', $ticketView);
        $this->assertStringContainsString('minmax(235px, .8fr)', $ticketView);
        $this->assertStringContainsString('grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr) minmax(0, 1fr);', $ticketView);
        $this->assertMatchesRegularExpression('/\\.trip-time-main\\s*\\{[^}]*white-space:\\s*nowrap;/s', $ticketView);
        $this->assertStringContainsString('journey-point-selector__field--destination', $ticketView);
        $this->assertStringNotContainsString('journey-point-selector__field:last-child', $ticketView);
        $this->assertStringContainsString('body.kiosk-interface .trip-card-main .trip-time-main', $ticketView);
        $this->assertStringContainsString("@lang('Travel date')", $partial);
        $this->assertStringNotContainsString("@lang('Approx.')", $partial);
    }

    public function test_booking_flow_responsive_controls_are_scoped_and_dismissible(): void
    {
        $appLayout = file_get_contents(resource_path('views/templates/basic/layouts/app.blade.php'));
        $frontendLayout = file_get_contents(resource_path('views/templates/basic/layouts/frontend.blade.php'));
        $masterLayout = file_get_contents(resource_path('views/templates/basic/layouts/master.blade.php'));
        $stepper = file_get_contents(resource_path('views/templates/basic/partials/booking_stepper.blade.php'));
        $reminders = file_get_contents(resource_path('views/templates/basic/partials/passenger-reminders-modal.blade.php'));
        $ticketView = file_get_contents(resource_path('views/templates/basic/ticket.blade.php'));

        $this->assertStringContainsString("session()->has('kiosk_id')", $appLayout);
        $this->assertStringContainsString('user-scalable=no', $appLayout);
        $this->assertStringContainsString('event.touches.length > 1', $appLayout);
        $this->assertStringContainsString('request()->routeIs([', $frontendLayout);
        $this->assertStringContainsString('request()->routeIs([', $masterLayout);
        $this->assertStringContainsString("'ticket.seats'", $frontendLayout);
        $this->assertStringContainsString('.header-bottom .menu.active', $stepper);
        $this->assertStringContainsString('data-bs-dismiss="modal"', $reminders);
        $this->assertStringContainsString('backdrop: true', $reminders);
        $this->assertStringContainsString('height: calc(100dvh - 20px);', $reminders);
        $this->assertStringContainsString("dropdownParent: routeSelect.closest('.journey-point-selector')", $ticketView);
    }
}
