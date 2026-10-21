<?php

namespace Tests\Unit;

use App\Support\BusinessTime;
use Carbon\Carbon;
use Tests\TestCase;

class BusinessTimeTest extends TestCase
{
    public function test_business_dates_and_request_ids_do_not_follow_a_utc_php_default(): void
    {
        $previousTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        Carbon::setTestNow(Carbon::parse('2026-10-20 00:15:30', BusinessTime::TIMEZONE));

        try {
            $this->assertSame('2026-10-20', BusinessTime::today()->toDateString());
            $this->assertStringStartsWith('GVF-20261020001530-', generateReqID());
        } finally {
            Carbon::setTestNow();
            date_default_timezone_set($previousTimezone);
        }
    }

    public function test_database_connections_preserve_legacy_wall_clock_timestamps(): void
    {
        $this->assertSame('+00:00', config('database.connections.mysql.timezone'));
        $this->assertSame('+00:00', config('database.connections.mariadb.timezone'));
    }
}
