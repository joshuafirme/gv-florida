<?php

namespace Tests\Unit;

use App\Models\Admin;
use App\Models\CashierTransactionEvent;
use App\Services\CashierTransactionRecorder;
use App\Services\TicketPassengerResolver;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CashierTransactionRecorderTest extends TestCase
{
    private Capsule $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = new Capsule();
        $this->database->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $this->database->setAsGlobal();
        $this->database->bootEloquent();

        $this->database->schema()->create('cashier_transaction_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('booked_ticket_id')->nullable();
            $table->unsignedBigInteger('slip_series_number_id')->nullable();
            $table->unsignedBigInteger('deposit_id')->nullable();
            $table->string('event_key')->unique();
            $table->string('status');
            $table->timestamp('processed_at');
            $table->string('source')->nullable();
            $table->string('pnr')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('passenger_name')->nullable();
            $table->string('passenger_type')->nullable();
            $table->string('passenger_id')->nullable();
            $table->date('journey_date')->nullable();
            $table->time('departure_time')->nullable();
            $table->string('trip_class')->nullable();
            $table->string('trip_route')->nullable();
            $table->string('seat_no')->nullable();
            $table->string('drop_off')->nullable();
            $table->string('km_post')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('base_fare', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('surcharge_amount', 14, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->text('reason')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Model::unsetConnectionResolver();
        parent::tearDown();
    }

    public function test_sold_snapshot_is_not_overwritten_after_rebooking(): void
    {
        $recorder = new CashierTransactionRecorder(new TicketPassengerResolver());
        $store = new ReflectionMethod($recorder, 'store');
        $original = $this->snapshot('2026-07-31', '1-D1', 'Laoag');
        $rebooked = $this->snapshot('2026-08-02', '1-D1', 'Laoag');

        $store->invoke(
            $recorder,
            'sold:10:100677',
            1,
            'Sold',
            $original,
            2100,
            null,
            Carbon::parse('2026-07-27 16:52:00')
        );
        $store->invoke(
            $recorder,
            'sold:10:100677',
            1,
            'Sold',
            $rebooked,
            2100,
            null,
            Carbon::parse('2026-07-27 16:52:00')
        );
        $store->invoke(
            $recorder,
            'rebooked:batch:100677',
            1,
            'Rebooked',
            $rebooked,
            0,
            'Travel date changed from 2026-07-31 to 2026-08-02',
            Carbon::parse('2026-07-27 16:53:00')
        );

        $sold = CashierTransactionEvent::where('status', 'Sold')->firstOrFail();
        $rebooking = CashierTransactionEvent::where('status', 'Rebooked')->firstOrFail();

        $this->assertSame('2026-07-31', $sold->journey_date->format('Y-m-d'));
        $this->assertSame('1-D1', $sold->seat_no);
        $this->assertSame(2100.0, (float) $sold->amount);
        $this->assertSame('2026-08-02', $rebooking->journey_date->format('Y-m-d'));
        $this->assertSame(0.0, (float) $rebooking->amount);
        $this->assertSame(2, CashierTransactionEvent::count());
    }

    public function test_legacy_overwritten_sold_date_is_restored_from_first_rebooking(): void
    {
        $recorder = new CashierTransactionRecorder(new TicketPassengerResolver());
        $store = new ReflectionMethod($recorder, 'store');
        $rebooked = $this->snapshot('2026-08-02', '1-D1', 'Laoag');

        $sold = $store->invoke(
            $recorder,
            'sold:10:100677',
            1,
            'Sold',
            $rebooked,
            2100,
            null,
            Carbon::parse('2026-07-27 16:52:00')
        );
        $store->invoke(
            $recorder,
            'rebooked:batch:100677',
            1,
            'Rebooked',
            $rebooked,
            0,
            'Travel date changed from 2026-07-31 to 2026-08-02',
            Carbon::parse('2026-07-27 16:53:00')
        );

        $restore = new ReflectionMethod($recorder, 'restoreOriginalSoldTravelDetails');
        $restore->invoke($recorder, $sold);
        $sold->refresh();

        $this->assertSame('2026-07-31', $sold->journey_date->format('Y-m-d'));
        $this->assertSame('1-D1', $sold->seat_no);
        $this->assertSame(2100.0, (float) $sold->amount);
    }

    public function test_authorization_snapshot_records_authorizer_time_and_optional_remarks(): void
    {
        $recorder = new CashierTransactionRecorder(new TicketPassengerResolver());
        $method = new ReflectionMethod($recorder, 'withAuthorizationAudit');
        $authorizer = (new Admin())->forceFill(['id' => 17, 'name' => 'Authorized Supervisor']);
        $cashier = (new Admin())->forceFill(['id' => 9, 'name' => 'Counter Cashier']);

        $snapshot = $method->invoke(
            $recorder,
            [],
            $authorizer,
            $cashier,
            'Ticket Rebooking',
            Carbon::parse('2026-08-14 14:35:10', 'Asia/Manila'),
            'Approved as an operational correction.'
        );

        $authorization = $snapshot['authorization'];

        $this->assertSame('Authorized Supervisor', $authorization['authorized_by_name']);
        $this->assertSame(17, $authorization['authorization_code_owner_id']);
        $this->assertSame('Counter Cashier', $authorization['performed_by_name']);
        $this->assertSame('2026-08-14T14:35:10+08:00', $authorization['authorized_at']);
        $this->assertSame('Approved as an operational correction.', $authorization['approval_remarks']);
    }

    private function snapshot(string $date, string $seat, string $route): array
    {
        return [
            'booked_ticket_id' => 20,
            'slip_series_number_id' => 100677,
            'deposit_id' => 10,
            'source' => 'Kiosk',
            'pnr' => 'TESTPNR',
            'reference_no' => '100677',
            'passenger_name' => 'Guest',
            'passenger_type' => 'Regular',
            'journey_date' => $date,
            'departure_time' => '21:05:00',
            'trip_class' => 'Executive Sleeper',
            'trip_route' => $route,
            'seat_no' => $seat,
            'drop_off' => 'Laoag',
            'km_post' => 'KM 488',
            'payment_method' => 'Cash',
            'base_fare' => 2100,
            'discount_amount' => 0,
            'surcharge_amount' => 0,
            'fare' => 2100,
        ];
    }
}
