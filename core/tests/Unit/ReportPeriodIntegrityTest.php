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
        ?string $createdAt = null
    ): void {
        DB::table('cashier_transaction_events')->insert([
            'id' => $id,
            'admin_id' => $adminId,
            'event_key' => "test:{$id}",
            'status' => $status,
            'processed_at' => $processedAt,
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
