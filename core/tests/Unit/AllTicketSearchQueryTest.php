<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Http\Controllers\Admin\VehicleTicketController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class AllTicketSearchQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('booked_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('series_number')->nullable();
            $table->string('pnr_number')->nullable();
            $table->unsignedTinyInteger('status');
            $table->date('date_of_journey')->nullable();
            $table->unsignedBigInteger('trip_id')->nullable();
            $table->unsignedBigInteger('payment_source_deposit_id')->nullable();
        });

        Schema::create('slip_series_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booked_ticket_id');
            $table->string('seat')->nullable();
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booked_ticket_id')->nullable();
            $table->string('trx')->nullable();
        });

        Schema::create('user_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deposit_id');
            $table->string('passenger_name')->nullable();
            $table->string('id_number')->nullable();
        });
    }

    public function test_exact_pnr_uses_the_direct_booking_join(): void
    {
        DB::table('booked_tickets')->insert([
            'id' => 42,
            'series_number' => 100042,
            'pnr_number' => 'ALLFAST123',
            'status' => Status::BOOKED_APPROVED,
        ]);

        $query = $this->allTicketRows('ALLFAST123');
        $sql = strtolower($query->toSql());

        $this->assertStringContainsString('join "booked_tickets" as "all_ticket_bookings"', $sql);
        $this->assertStringContainsString('"slip_series_numbers"."booked_ticket_id" in (?)', $sql);
        $this->assertStringNotContainsString('exists (select * from "booked_tickets"', $sql);
        $this->assertContains(42, $query->getBindings());
    }

    public function test_broad_details_use_one_materialized_ticket_lookup(): void
    {
        $query = $this->allTicketRows('Sampaloc');
        $sql = strtolower($query->toSql());

        $this->assertStringContainsString('from "booked_tickets" as "ticket_search"', $sql);
        $this->assertStringContainsString('left join "deposits" as "ticket_deposits"', $sql);
        $this->assertStringContainsString('left join "vehicle_routes" as "ticket_routes"', $sql);
        $this->assertStringNotContainsString('exists (select * from "booked_tickets"', $sql);
    }

    public function test_date_route_and_booking_status_filter_the_joined_columns(): void
    {
        $query = $this->allTicketRows('', '2026-10-16', 4, 'pending');
        $sql = strtolower($query->toSql());

        $this->assertStringContainsString('"all_ticket_bookings"."date_of_journey" = ?', $sql);
        $this->assertStringContainsString('"all_ticket_bookings"."trip_id" in (select "id" from "trips"', $sql);
        $this->assertStringContainsString('"all_ticket_bookings"."status" = ?', $sql);
        $this->assertContains('2026-10-16', $query->getBindings());
        $this->assertContains(Status::BOOKED_PENDING, $query->getBindings());
    }

    private function allTicketRows(
        string $search = '',
        ?string $travelDate = null,
        ?int $routeId = null,
        ?string $status = null
    ) {
        $method = new ReflectionMethod(VehicleTicketController::class, 'allTicketRows');
        $method->setAccessible(true);

        return $method->invoke(
            new VehicleTicketController(),
            $search,
            $travelDate,
            $routeId,
            $status
        );
    }
}
