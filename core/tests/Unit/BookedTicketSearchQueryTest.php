<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Http\Controllers\Admin\VehicleTicketController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class BookedTicketSearchQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('booked_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('series_number')->nullable();
            $table->string('pnr_number')->nullable();
            $table->unsignedTinyInteger('status');
        });

        Schema::create('slip_series_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booked_ticket_id');
            $table->string('seat')->nullable();
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booked_ticket_id')->nullable();
        });

        Schema::create('user_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deposit_id');
            $table->string('passenger_name')->nullable();
        });
    }

    public function test_exact_pnr_search_uses_the_join_and_identifier_filter(): void
    {
        DB::table('booked_tickets')->insert([
            'id' => 42,
            'series_number' => 100042,
            'pnr_number' => 'FASTPNR123',
            'status' => Status::BOOKED_APPROVED,
        ]);

        $query = $this->bookedTicketRows('FASTPNR123');
        $sql = strtolower($query->toSql());

        $this->assertStringContainsString('join "booked_tickets" as "booked_search_tickets"', $sql);
        $this->assertStringContainsString('"slip_series_numbers"."booked_ticket_id" in (?)', $sql);
        $this->assertNotContains('%FASTPNR123%', $query->getBindings());
        $this->assertContains(42, $query->getBindings());
    }

    public function test_passenger_search_is_materialized_in_one_subquery(): void
    {
        $query = $this->bookedTicketRows('Juan Dela Cruz');
        $sql = strtolower($query->toSql());

        $this->assertStringContainsString('from "deposits" inner join "user_discounts"', $sql);
        $this->assertStringContainsString('"booked_search_tickets"."pnr_number" like ?', $sql);
        $this->assertStringNotContainsString('exists (select * from "booked_tickets"', $sql);
        $this->assertContains('%Juan Dela Cruz%', $query->getBindings());
    }

    public function test_exact_passenger_search_avoids_the_wildcard_fallback(): void
    {
        DB::table('deposits')->insert([
            'id' => 7,
            'booked_ticket_id' => 42,
        ]);
        DB::table('user_discounts')->insert([
            'deposit_id' => 7,
            'passenger_name' => 'Juan Dela Cruz',
        ]);

        $query = $this->bookedTicketRows('Juan Dela Cruz');
        $sql = strtolower($query->toSql());

        $this->assertStringContainsString('from "deposits" inner join "user_discounts"', $sql);
        $this->assertStringNotContainsString('"booked_search_tickets"."pnr_number" like ?', $sql);
        $this->assertContains('Juan Dela Cruz', $query->getBindings());
        $this->assertNotContains('%Juan Dela Cruz%', $query->getBindings());
    }

    private function bookedTicketRows(string $search)
    {
        $method = new ReflectionMethod(VehicleTicketController::class, 'bookedTicketRows');
        $method->setAccessible(true);

        return $method->invoke(new VehicleTicketController(), $search);
    }
}
