<?php

namespace Tests\Unit;

use App\Models\BookedTicket;
use App\Models\Counter;
use App\Models\PassengerNotification;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\User;
use App\Services\BookingNotificationService;
use App\Services\PassengerNotificationBroadcaster;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingNotificationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.pusher.app_id' => null,
            'services.pusher.key' => null,
            'services.pusher.secret' => null,
        ]);

        Schema::create('passenger_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('booked_ticket_id')->nullable();
            $table->unsignedBigInteger('sent_by_admin_id')->nullable();
            $table->string('event_type');
            $table->string('dedupe_key')->unique();
            $table->string('title');
            $table->text('message');
            $table->string('channel');
            $table->string('sent_by');
            $table->string('status');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_uses_latest_booking_details_and_prevents_duplicate_events(): void
    {
        $this->mock(PassengerNotificationBroadcaster::class, function ($mock) {
            $mock->shouldReceive('broadcast')->once()->andReturnTrue();
        });

        $ticket = $this->ticket();
        $service = app(BookingNotificationService::class);

        $first = $service->send($ticket, 'ticket_rebooked', 'rebooked:501:version-1');
        $duplicate = $service->send($ticket, 'ticket_rebooked', 'rebooked:501:version-1');

        $this->assertInstanceOf(PassengerNotification::class, $first);
        $this->assertNull($duplicate);
        $this->assertSame(1, PassengerNotification::count());
        $this->assertStringContainsString('Passenger: Juan Dela Cruz', $first->message);
        $this->assertStringContainsString('Oct 06, 2026 at 8:30 AM', $first->message);
        $this->assertStringContainsString('Tuguegarao to Manila', $first->message);
        $this->assertStringContainsString('Seat: D3', $first->message);
        $this->assertStringContainsString('PNR: GV-57D892', $first->message);
        $this->assertSame('Pusher / In-app', $first->channel);
        $this->assertSame('System', $first->sent_by);
    }

    public function test_passenger_channels_are_stable_and_do_not_expose_user_ids(): void
    {
        config(['app.key' => 'base64:test-notification-secret']);

        $first = PassengerNotificationBroadcaster::channelFor(42);

        $this->assertSame($first, PassengerNotificationBroadcaster::channelFor(42));
        $this->assertNotSame($first, PassengerNotificationBroadcaster::channelFor(43));
        $this->assertStringStartsWith('passenger-notifications-', $first);
        $this->assertStringNotContainsString('42', substr($first, strlen('passenger-notifications-')));
    }

    private function ticket(): BookedTicket
    {
        $user = new User();
        $user->forceFill(['id' => 77, 'firstname' => 'Juan', 'lastname' => 'Dela Cruz']);

        $schedule = new Schedule();
        $schedule->forceFill(['id' => 10, 'start_from' => '08:30:00']);

        $trip = new Trip();
        $trip->forceFill(['id' => 59, 'schedule_id' => 10]);
        $trip->setRelation('schedule', $schedule);

        $pickup = new Counter();
        $pickup->forceFill(['id' => 1, 'name' => 'Tuguegarao']);
        $drop = new Counter();
        $drop->forceFill(['id' => 2, 'name' => 'Manila']);

        $ticket = new BookedTicket();
        $ticket->forceFill([
            'id' => 501,
            'user_id' => 77,
            'trip_id' => 59,
            'date_of_journey' => '2026-10-06',
            'seats' => ['1-D3'],
            'pnr_number' => 'GV-57D892',
        ]);
        $ticket->setRelation('user', $user);
        $ticket->setRelation('trip', $trip);
        $ticket->setRelation('pickup', $pickup);
        $ticket->setRelation('drop', $drop);
        $ticket->setRelation('deposit', null);
        $ticket->setRelation('paymentSourceDeposit', null);
        $ticket->setRelation('slipSeriesNumbers', collect());

        return $ticket;
    }
}
