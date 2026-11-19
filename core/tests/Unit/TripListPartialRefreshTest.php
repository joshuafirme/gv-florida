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
        $this->assertMatchesRegularExpression('/\.passenger-reminders-content\s*\{[^}]*max-height:\s*min\(720px, calc\(100dvh - 32px\)\);/s', $reminders);
        $this->assertMatchesRegularExpression('/\.passenger-reminders-content\s*>\s*footer\s*\{[^}]*flex:\s*0 0 auto;[^}]*padding:\s*14px 28px 20px;/s', $reminders);
        $this->assertStringContainsString("dropdownParent: routeSelect.closest('.journey-point-selector')", $ticketView);
    }

    public function test_kiosk_seat_refresh_clears_stale_priority_seat_restrictions(): void
    {
        $bookingView = file_get_contents(resource_path('views/templates/basic/book_ticket.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/SiteController.php'));

        $this->assertStringContainsString("'prioritySeatIds' => \$prioritySeatIds", $controller);
        $this->assertStringContainsString('function synchronizePrioritySeatAccess', $bookingView);
        $this->assertStringContainsString("seatCell.removeClass('is-walk-in-only')", $bookingView);
        $this->assertStringContainsString("seatNode.attr('aria-disabled', 'false')", $bookingView);
        $this->assertStringContainsString('response.priorityRestrictedSeats || []', $bookingView);
    }

    public function test_kiosk_origin_is_static_and_enforced_throughout_booking(): void
    {
        $ticketView = file_get_contents(resource_path('views/templates/basic/ticket.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/SiteController.php'));

        $this->assertStringContainsString('journey-point-selector__origin-static', $ticketView);
        $this->assertStringContainsString('name="pickup" id="ticket-pickup"', $ticketView);
        $this->assertStringContainsString("filter('select')", $ticketView);
        $this->assertStringContainsString("enforceKioskOrigin(\$request, ['pickup', 'counter_id'])", $controller);
        $this->assertStringContainsString("enforceKioskOrigin(\$request, ['pickup', 'start_from', 'counter_id'])", $controller);
        $this->assertStringContainsString("enforceKioskOrigin(\$request, ['source_id', 'pickup_point'])", $controller);
        $this->assertSame(2, substr_count(
            $controller,
            "enforceKioskOrigin(\$request, ['pickup_point'])"
        ));
    }

    public function test_ticket_list_dropdowns_use_scoped_alignment_and_positioning(): void
    {
        $ticketView = file_get_contents(resource_path('views/templates/basic/ticket.blade.php'));

        $this->assertStringContainsString('journey-point-dropdown--destination', $ticketView);
        $this->assertStringContainsString('text-align: right;', $ticketView);
        $this->assertStringContainsString("dropdownParent: filter.closest('.trip-results-filters')", $ticketView);
        $this->assertStringContainsString('top: calc(100% + 6px) !important;', $ticketView);
        $this->assertStringContainsString(".attr('data-result-filter', filter.attr('name'));", $ticketView);
        $this->assertMatchesRegularExpression(
            '/\.trip-results-toolbar\s*\{[^}]*position:\s*relative;[^}]*z-index:\s*20;/s',
            $ticketView
        );
        $this->assertMatchesRegularExpression(
            '/\.trip-list-region\s*\{[^}]*position:\s*relative;[^}]*z-index:\s*1;/s',
            $ticketView
        );
    }
}
