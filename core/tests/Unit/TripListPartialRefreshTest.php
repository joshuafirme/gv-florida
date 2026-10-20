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
        $this->assertStringContainsString("view('Template::partials.trip-list'", $controller);
    }
}
