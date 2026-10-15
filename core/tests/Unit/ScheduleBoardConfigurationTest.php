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
        $this->assertStringContainsString('whereIn(\'id\', $selectedTrips->pluck(\'trip_id\'))', $controller);
        $this->assertStringContainsString('->currentlyVisible()', $controller);
    }

    public function test_schedule_board_refreshes_live_and_at_time_boundaries(): void
    {
        $view = file_get_contents(resource_path('views/admin/counter/board.blade.php'));

        $this->assertStringContainsString("channel.bind('schedule-data-updated'", $view);
        $this->assertStringContainsString('setInterval(scheduleBoard, 60000)', $view);
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
}
