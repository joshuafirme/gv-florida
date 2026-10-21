<?php

namespace Tests\Unit;

use Tests\TestCase;

class ScheduleBoardConfigurationTest extends TestCase
{
    public function test_schedule_board_feed_applies_configuration_and_scheduled_content(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Admin/CounterController.php'));

        $this->assertStringContainsString('departure_grace_minutes', $controller);
        $this->assertStringContainsString('lessThanOrEqualTo($departure->addMinutes($graceMinutes))', $controller);
        $this->assertStringContainsString("where('is_visible', true)->pluck('trip_id')", $controller);
        $this->assertStringContainsString('$configuredOrder->get($trip->id, PHP_INT_MAX)', $controller);
        $this->assertStringContainsString('->currentlyVisible()', $controller);
    }

    public function test_schedule_board_refreshes_live_and_at_time_boundaries(): void
    {
        $view = file_get_contents(resource_path('views/admin/counter/board.blade.php'));

        $this->assertStringContainsString("channel.bind('schedule-data-updated'", $view);
        $this->assertStringContainsString('setFallbackRefreshInterval(60000)', $view);
        $this->assertStringContainsString('setFallbackRefreshInterval(5000)', $view);
        $this->assertStringContainsString("event.key === scheduleBoardStorageKey", $view);
        $this->assertStringContainsString('renderBoardContent()', $view);
        $this->assertStringContainsString('boardAdvertisementImage', $view);
        $this->assertStringContainsString('boardNoticeList', $view);
    }

    public function test_management_routes_require_the_schedule_board_permission(): void
    {
        $routes = file_get_contents(base_path('routes/admin.php'));

        $this->assertStringContainsString("role:admin.counter.schedule-board.manage", $routes);
        $this->assertStringContainsString("->name('counter.schedule-board.')", $routes);
    }

    public function test_trip_selection_saves_the_submitted_display_order(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Admin/ScheduleBoardController.php'));

        $this->assertStringContainsString('DB::transaction(function () use ($counter, $orderedIds, $selectedIds)', $controller);
        $this->assertStringContainsString('$orderedIds->each(function (int $tripId, int $index)', $controller);
        $this->assertStringContainsString("'is_visible' => \$selectedIds->contains(\$tripId)", $controller);
        $this->assertStringContainsString('$selectedIds->count() < $orderedIds->count()', $controller);
        $this->assertStringContainsString("'show_all_trips' => false", $controller);
        $this->assertStringContainsString("scheduleBoardTrips()->update(['is_visible' => true])", $controller);
        $this->assertStringContainsString("with('schedule_board_refresh_counter', \$counter->id)", $controller);

        $view = file_get_contents(resource_path('views/admin/counter/schedule-board-manage.blade.php'));
        $this->assertStringContainsString('name="trip_order[]"', $view);
        $this->assertStringContainsString('@checked($selected?->is_visible)', $view);
        $this->assertStringContainsString('schedule-board-refresh:${refreshCounterId}', $view);

        $model = file_get_contents(app_path('Models/ScheduleBoardTrip.php'));
        $this->assertStringContainsString("'is_visible' => 'boolean'", $model);
    }
}
