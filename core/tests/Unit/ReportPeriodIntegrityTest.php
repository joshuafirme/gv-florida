<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Models\Admin;
use App\Models\Deposit;
use App\Services\CashierDashboardService;
use App\Services\CashierTransactionRecorder;
use App\Services\DailyReportService;
use App\Services\TicketPassengerResolver;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportPeriodIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('username')->nullable();
        });
        Schema::create('booked_tickets', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('processed_by_admin_id')->nullable();
            $table->unsignedBigInteger('booked_ticket_id')->nullable();
            $table->unsignedTinyInteger('status');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        foreach (['ticket_refunds', 'ticket_cancellations', 'ticket_voids'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('processed_by_admin_id')->nullable();
                $table->timestamps();
            });
        }
        Schema::create('online_ticket_validations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('validated_by_admin_id')->nullable();
            $table->unsignedBigInteger('discount_applied_by_admin_id')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('discount_authorized_at')->nullable();
        });
        Schema::create('cashier_transaction_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('booked_ticket_id')->nullable();
            $table->unsignedBigInteger('slip_series_number_id')->nullable();
            $table->unsignedBigInteger('deposit_id')->nullable();
            $table->string('event_key')->unique();
            $table->string('status');
            $table->timestamp('processed_at');
            $table->date('journey_date')->nullable();
            $table->string('source')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('base_fare', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('surcharge_amount', 14, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function test_october_13_backfill_uses_payment_completion_instead_of_later_updates(): void
    {
        $this->insertDeposit(1, '2026-10-12 20:00:00', '2026-10-13 05:49:00');
        $this->insertDeposit(2, '2026-10-13 08:00:00', '2026-10-13 08:00:00');
        $this->insertDeposit(3, '2026-10-13 09:00:00', '2026-10-14 10:00:00');

        $recorder = new CapturingCashierTransactionRecorder();
        $recorder->backfillAllForDate(Carbon::parse('2026-10-13'));

        $this->assertSame([2, 3], $recorder->recordedDepositIds);
    }

    public function test_october_13_daily_shift_personnel_and_channel_totals_are_consistent(): void
    {
        DB::table('admins')->insert([
            ['id' => 1, 'name' => 'Alice Cashier', 'username' => 'alice'],
        ]);

        $this->insertEvent(1, 1, 'Sold', 'Counter', 100, '2026-10-13 08:00:00');
        $this->insertEvent(2, 1, 'Refunded', 'Counter', -20, '2026-10-13 09:00:00');
        $this->insertEvent(3, null, 'Sold', 'Online', 200, '2026-10-13 10:00:00');
        $this->insertEvent(4, 1, 'Sold', 'Counter', 999, '2026-10-12 20:00:00', '2026-10-13 05:49:00');

        $recorder = new CapturingCashierTransactionRecorder();
        $dashboard = new CashierDashboardService($recorder);
        $daily = (new DailyReportService($recorder, $dashboard))->forDate(Carbon::parse('2026-10-13'));
        $shift = $dashboard->forDate(Admin::findOrFail(1), Carbon::parse('2026-10-13'));

        $this->assertCount(3, $daily['transactions']);
        $this->assertSame(2, $daily['summary']['tickets']);
        $this->assertSame(300.0, $daily['summary']['gross_sales']);
        $this->assertSame(20.0, $daily['summary']['refunds']);
        $this->assertSame(280.0, $daily['summary']['net_collection']);
        $this->assertSame(280.0, (float) $daily['cashier_collections']->sum('summary.net_collection'));
        $this->assertSame(300.0, (float) $daily['channel_collections']->sum('amount'));

        $this->assertSame(1, $shift['summary']['tickets']);
        $this->assertSame(100.0, $shift['summary']['gross_sales']);
        $this->assertSame(20.0, $shift['summary']['refunds']);
        $this->assertSame(80.0, $shift['summary']['net_collection']);
    }

    public function test_timestamp_repair_restores_each_transaction_from_its_authoritative_source(): void
    {
        $this->insertDeposit(1, '2026-10-01 08:00:00', '2026-10-01 08:00:00');

        foreach ([
            'ticket_refunds' => ['id' => 2, 'created_at' => '2026-10-02 09:00:00'],
            'ticket_cancellations' => ['id' => 3, 'created_at' => '2026-10-03 10:00:00'],
            'ticket_voids' => ['id' => 4, 'created_at' => '2026-10-04 11:00:00'],
        ] as $table => $source) {
            DB::table($table)->insert($source + ['updated_at' => $source['created_at']]);
        }

        DB::table('online_ticket_validations')->insert([
            [
                'id' => 5,
                'validated_at' => '2026-10-05 12:00:00',
                'discount_authorized_at' => null,
            ],
            [
                'id' => 6,
                'validated_at' => null,
                'discount_authorized_at' => '2026-10-06 13:00:00',
            ],
        ]);

        foreach ([
            ['sold:1:101', 'Sold', '2026-10-01 08:00:00', 1],
            ['refunded:2', 'Refunded', '2026-10-02 09:00:00', null],
            ['cancelled:3', 'Cancelled', '2026-10-03 10:00:00', null],
            ['voided:4', 'Voided', '2026-10-04 11:00:00', null],
            ['online-validation:5', 'Validated', '2026-10-05 12:00:00', null],
            ['discount-override:6', 'Discount Override', '2026-10-06 13:00:00', null],
            ['rebooked:test:101', 'Rebooked', '2026-10-07 14:00:00', null],
        ] as $index => [$eventKey, $status, $sourceTime, $depositId]) {
            DB::table('cashier_transaction_events')->insert([
                'id' => 20 + $index,
                'deposit_id' => $depositId,
                'event_key' => $eventKey,
                'status' => $status,
                'processed_at' => '2026-10-20 06:05:00',
                'source' => 'Online',
                'amount' => 0,
                'created_at' => $sourceTime,
                'updated_at' => '2026-10-20 06:05:00',
            ]);
        }

        $migration = require database_path(
            'migrations/2026_10_20_000001_preserve_cashier_transaction_event_times.php'
        );
        $migration->up();

        $timestamps = DB::table('cashier_transaction_events')
            ->orderBy('id')
            ->pluck('processed_at', 'event_key')
            ->all();

        $this->assertSame('2026-10-01 08:00:00', $timestamps['sold:1:101']);
        $this->assertSame('2026-10-02 09:00:00', $timestamps['refunded:2']);
        $this->assertSame('2026-10-03 10:00:00', $timestamps['cancelled:3']);
        $this->assertSame('2026-10-04 11:00:00', $timestamps['voided:4']);
        $this->assertSame('2026-10-05 12:00:00', $timestamps['online-validation:5']);
        $this->assertSame('2026-10-06 13:00:00', $timestamps['discount-override:6']);
        $this->assertSame('2026-10-07 14:00:00', $timestamps['rebooked:test:101']);
        $this->assertSame(
            0,
            DB::table('cashier_transaction_events')
                ->whereBetween('processed_at', ['2026-10-20 00:00:00', '2026-10-20 23:59:59'])
                ->count()
        );
        $this->assertSame(7, DB::table('cashier_transaction_events')->count());
    }

    public function test_daily_and_shift_reports_exclude_departures_before_the_report_date(): void
    {
        DB::table('admins')->insert([
            'id' => 1,
            'name' => 'Alice Cashier',
            'username' => 'alice',
        ]);

        $this->insertEvent(1, 1, 'Sold', 'Kiosk', 900, '2026-10-20 01:00:00', journeyDate: '2026-10-19');
        $this->insertEvent(2, 1, 'Validated', 'Online', 0, '2026-10-20 02:00:00', journeyDate: '2026-10-19');
        $this->insertEvent(3, 1, 'Sold', 'Counter', 100, '2026-10-20 08:00:00', journeyDate: '2026-10-20');
        $this->insertEvent(4, 1, 'Sold', 'Online', 200, '2026-10-20 09:00:00', journeyDate: '2026-10-21');

        $recorder = new CapturingCashierTransactionRecorder();
        $dashboard = new CashierDashboardService($recorder);
        $date = Carbon::parse('2026-10-20');
        $daily = (new DailyReportService($recorder, $dashboard))->forDate($date);
        $shift = $dashboard->forDate(Admin::findOrFail(1), $date);

        $this->assertSame([3, 4], $daily['transactions']->pluck('id')->all());
        $this->assertSame(2, $daily['summary']['tickets']);
        $this->assertSame(300.0, $daily['summary']['net_collection']);
        $this->assertSame([3, 4], $shift['transactions']->pluck('id')->all());
        $this->assertSame(2, $shift['summary']['tickets']);
        $this->assertSame(300.0, $shift['summary']['net_collection']);
    }

    public function test_daily_reports_keep_transactions_on_their_actual_calendar_date(): void
    {
        $this->insertEvent(
            1,
            null,
            'Sold',
            'Online',
            100,
            '2026-10-06 23:34:00',
            journeyDate: '2026-10-07'
        );
        $this->insertEvent(
            2,
            null,
            'Sold',
            'Online',
            200,
            '2026-10-07 00:19:00',
            journeyDate: '2026-10-07'
        );

        $recorder = new CapturingCashierTransactionRecorder();
        $dashboard = new CashierDashboardService($recorder);
        $service = new DailyReportService($recorder, $dashboard);

        $octoberSix = $service->forDate(Carbon::parse('2026-10-06'));
        $octoberSeven = $service->forDate(Carbon::parse('2026-10-07'));

        $this->assertSame([1], $octoberSix['transactions']->pluck('id')->all());
        $this->assertSame([2], $octoberSeven['transactions']->pluck('id')->all());
    }

    public function test_follow_up_repair_restores_reconciled_event_time(): void
    {
        DB::table('online_ticket_validations')->insert([
            'id' => 10,
            'validated_at' => '2026-10-06 23:34:00',
            'discount_authorized_at' => null,
        ]);
        $this->insertEvent(
            10,
            null,
            'Validated',
            'Online',
            0,
            '2026-10-20 06:05:00',
            '2026-10-06 23:34:00',
            '2026-10-07'
        );
        DB::table('cashier_transaction_events')
            ->where('id', 10)
            ->update(['event_key' => 'online-validation:10']);

        $migration = require database_path(
            'migrations/2026_10_20_000002_repair_cashier_event_times_after_timezone_fix.php'
        );
        $migration->up();
        $migration->up();

        $this->assertSame(
            '2026-10-06 23:34:00',
            DB::table('cashier_transaction_events')->where('id', 10)->value('processed_at')
        );
    }

    private function insertDeposit(int $id, string $completedAt, string $updatedAt): void
    {
        DB::table('deposits')->insert([
            'id' => $id,
            'processed_by_admin_id' => null,
            'booked_ticket_id' => null,
            'status' => Status::PAYMENT_SUCCESS,
            'completed_at' => $completedAt,
            'created_at' => $completedAt,
            'updated_at' => $updatedAt,
        ]);
    }

    private function insertEvent(
        int $id,
        ?int $adminId,
        string $status,
        string $source,
        float $amount,
        string $processedAt,
        ?string $createdAt = null,
        ?string $journeyDate = null
    ): void {
        DB::table('cashier_transaction_events')->insert([
            'id' => $id,
            'admin_id' => $adminId,
            'event_key' => "test:{$id}",
            'status' => $status,
            'processed_at' => $processedAt,
            'journey_date' => $journeyDate ?: Carbon::parse($processedAt)->toDateString(),
            'source' => $source,
            'payment_method' => $source === 'Online' ? 'GCash' : 'Cash',
            'amount' => $amount,
            'created_at' => $createdAt ?: $processedAt,
            'updated_at' => $createdAt ?: $processedAt,
        ]);
    }
}

class CapturingCashierTransactionRecorder extends CashierTransactionRecorder
{
    public array $recordedDepositIds = [];

    public function __construct()
    {
        parent::__construct(new TicketPassengerResolver());
    }

    public function recordSold(Deposit $deposit): void
    {
        $this->recordedDepositIds[] = (int) $deposit->id;
    }
}
