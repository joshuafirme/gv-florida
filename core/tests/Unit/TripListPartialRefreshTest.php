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

    public function test_ticket_page_uses_a_scoped_skeleton_and_partial_refresh(): void
    {
        $ticketView = file_get_contents(resource_path('views/templates/basic/ticket.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/SiteController.php'));

        $this->assertStringContainsString('id="tripListRegion"', $ticketView);
        $this->assertStringContainsString('trip-list-skeleton', $ticketView);
        $this->assertStringContainsString('async function refreshTripList', $ticketView);
        $this->assertStringContainsString("'X-Requested-With': 'XMLHttpRequest'", $ticketView);
        $this->assertStringContainsString("asset('assets/global/js/dropping-points.js')", $ticketView);
        $this->assertStringContainsString("status === 'fully_booked'", $ticketView);
        $this->assertStringContainsString('isInvalidDate:', $ticketView);
        $this->assertStringContainsString('tripListRealtime.scheduleEvent', $ticketView);
        $this->assertStringContainsString("data-default-option=\"@lang('Select Destination')\"", $ticketView);
        $this->assertStringContainsString("view('Template::partials.trip-list'", $controller);
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
        $this->assertStringContainsString('minmax(230px, .85fr)', $ticketView);
        $this->assertStringContainsString('overflow-wrap: anywhere;', $ticketView);
        $this->assertMatchesRegularExpression('/\\.trip-time-main\\s*\\{[^}]*white-space:\\s*nowrap;/s', $ticketView);
        $this->assertStringContainsString('font-size: clamp(26px, 2.8vw, 32px);', $ticketView);
        $this->assertStringContainsString('@media screen and (min-width: 768px) and (max-width: 900px)', $ticketView);
    }
}
