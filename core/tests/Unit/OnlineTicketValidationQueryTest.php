<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Http\Controllers\Admin\OnlineTicketValidationController;
use App\Services\CashierTransactionRecorder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class OnlineTicketValidationQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('booked_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('kiosk_id')->nullable();
            $table->unsignedTinyInteger('status');
            $table->unsignedBigInteger('payment_source_deposit_id')->nullable();
            $table->unsignedBigInteger('trip_id')->nullable();
            $table->date('date_of_journey')->nullable();
        });
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->time('start_from');
        });
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
        });
        Schema::create('slip_series_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booked_ticket_id');
            $table->string('seat');
        });
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booked_ticket_id')->nullable();
            $table->unsignedTinyInteger('status');
        });

        foreach (['ticket_refunds', 'ticket_cancellations', 'ticket_voids'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('slip_series_number_id')->unique();
            });
        }

        Schema::create('online_ticket_validations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('slip_series_number_id')->unique();
            $table->timestamp('validated_at')->nullable();
        });
    }

    public function test_eligible_query_handles_both_payment_links_and_excludes_inactive_tickets(): void
    {
        DB::table('schedules')->insert([
            ['id' => 1, 'start_from' => '07:30:00'],
            ['id' => 2, 'start_from' => '09:00:00'],
        ]);
        DB::table('trips')->insert([
            ['id' => 1, 'schedule_id' => 1],
            ['id' => 2, 'schedule_id' => 2],
        ]);

        foreach (range(1, 9) as $ticketId) {
            DB::table('booked_tickets')->insert([
                'id' => $ticketId,
                'user_id' => $ticketId === 8 ? null : 10,
                'kiosk_id' => $ticketId === 3 ? 1 : null,
                'status' => $ticketId === 9 ? Status::BOOKED_PENDING : Status::BOOKED_APPROVED,
                'payment_source_deposit_id' => $ticketId === 2 ? 20 : null,
                'trip_id' => $ticketId === 2 ? 2 : 1,
                'date_of_journey' => $ticketId === 2 ? '2026-10-15' : '2026-10-14',
            ]);
            DB::table('slip_series_numbers')->insert([
                'id' => 100 + $ticketId,
                'booked_ticket_id' => $ticketId,
                'seat' => 'D' . $ticketId,
            ]);
        }

        foreach ([1, 3, 5, 6, 7, 8, 9] as $ticketId) {
            DB::table('deposits')->insert([
                'id' => $ticketId,
                'booked_ticket_id' => $ticketId,
                'status' => Status::PAYMENT_SUCCESS,
            ]);
        }
        DB::table('deposits')->insert([
            'id' => 4,
            'booked_ticket_id' => 4,
            'status' => Status::PAYMENT_PENDING,
        ]);
        DB::table('deposits')->insert([
            'id' => 20,
            'booked_ticket_id' => null,
            'status' => Status::PAYMENT_SUCCESS,
        ]);

        DB::table('ticket_refunds')->insert(['slip_series_number_id' => 105]);
        DB::table('ticket_cancellations')->insert(['slip_series_number_id' => 106]);
        DB::table('ticket_voids')->insert(['slip_series_number_id' => 107]);
        DB::table('online_ticket_validations')->insert([
            'slip_series_number_id' => 102,
            'validated_at' => now(),
        ]);

        $controller = new OnlineTicketValidationController(
            $this->createMock(CashierTransactionRecorder::class)
        );
        $method = new ReflectionMethod($controller, 'eligibleTicketsQuery');
        $query = $method->invoke($controller);

        $this->assertSame([101, 102], $query->pluck('slip_series_numbers.id')->all());

        $counts = $method->invoke($controller)
            ->reorder()
            ->select([
                DB::raw('COUNT(*) AS total_count'),
                DB::raw('SUM(CASE WHEN validation_record.validated_at IS NULL THEN 1 ELSE 0 END) AS to_validate_count'),
                DB::raw('SUM(CASE WHEN validation_record.validated_at IS NOT NULL THEN 1 ELSE 0 END) AS validated_count'),
            ])
            ->first();

        $this->assertSame(2, (int) $counts->total_count);
        $this->assertSame(1, (int) $counts->to_validate_count);
        $this->assertSame(1, (int) $counts->validated_count);
    }

    public function test_eligible_query_filters_by_travel_date_and_departure_time(): void
    {
        DB::table('schedules')->insert([
            ['id' => 1, 'start_from' => '07:30:00'],
            ['id' => 2, 'start_from' => '09:00:00'],
        ]);
        DB::table('trips')->insert([
            ['id' => 1, 'schedule_id' => 1],
            ['id' => 2, 'schedule_id' => 2],
        ]);

        foreach ([
            ['id' => 1, 'trip_id' => 1, 'date' => '2026-10-14'],
            ['id' => 2, 'trip_id' => 2, 'date' => '2026-10-15'],
        ] as $ticket) {
            DB::table('booked_tickets')->insert([
                'id' => $ticket['id'],
                'user_id' => 10,
                'kiosk_id' => null,
                'status' => Status::BOOKED_APPROVED,
                'payment_source_deposit_id' => null,
                'trip_id' => $ticket['trip_id'],
                'date_of_journey' => $ticket['date'],
            ]);
            DB::table('slip_series_numbers')->insert([
                'id' => 100 + $ticket['id'],
                'booked_ticket_id' => $ticket['id'],
                'seat' => 'D' . $ticket['id'],
            ]);
            DB::table('deposits')->insert([
                'id' => $ticket['id'],
                'booked_ticket_id' => $ticket['id'],
                'status' => Status::PAYMENT_SUCCESS,
            ]);
        }

        $controller = new OnlineTicketValidationController(
            $this->createMock(CashierTransactionRecorder::class)
        );
        $method = new ReflectionMethod($controller, 'eligibleTicketsQuery');

        $this->assertSame(
            [101],
            $method->invoke($controller, '2026-10-14')->pluck('slip_series_numbers.id')->all()
        );
        $this->assertSame(
            [102],
            $method->invoke($controller, null, '09:00')->pluck('slip_series_numbers.id')->all()
        );
        $this->assertSame(
            [102],
            $method->invoke($controller, '2026-10-15', '09:00')->pluck('slip_series_numbers.id')->all()
        );
        $this->assertSame(
            [],
            $method->invoke($controller, '2026-10-14', '09:00')->pluck('slip_series_numbers.id')->all()
        );
    }
}
