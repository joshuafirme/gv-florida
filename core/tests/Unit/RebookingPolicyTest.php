<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Models\BookedTicket;
use App\Models\Schedule;
use App\Models\Trip;
use App\Services\RebookingPolicy;
use Carbon\Carbon;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class RebookingPolicyTest extends TestCase
{
    private RebookingPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $container = new Container();
        $container->instance(
            'validator',
            new ValidationFactory(new Translator(new ArrayLoader(), 'en'), $container)
        );
        Container::setInstance($container);
        Facade::setFacadeApplication($container);
        $this->policy = new RebookingPolicy();
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_it_allows_rebooking_to_an_earlier_active_trip_that_has_not_departed(): void
    {
        $ticket = $this->ticket('2026-08-03', '5:00 PM');
        $replacement = $this->trip(2, '2:00 PM');

        $this->policy->assertReplacementTrip(
            $ticket,
            $replacement,
            '2026-08-03',
            Carbon::parse('2026-08-03 10:00 AM')
        );

        $this->addToAssertionCount(1);
    }

    public function test_it_allows_rebooking_to_a_later_active_trip(): void
    {
        $ticket = $this->ticket('2026-08-03', '5:00 PM');
        $replacement = $this->trip(2, '8:00 PM');

        $this->policy->assertReplacementTrip(
            $ticket,
            $replacement,
            '2026-08-03',
            Carbon::parse('2026-08-03 10:00 AM')
        );

        $this->addToAssertionCount(1);
    }

    public function test_pending_booking_is_rebookable_before_departure(): void
    {
        $ticket = $this->ticket('2026-08-03', '5:00 PM', Status::BOOKED_PENDING);

        $eligibility = $this->policy->assertEligible(
            $ticket,
            Carbon::parse('2026-08-03 4:59 PM')
        );

        $this->assertFalse($eligibility['after_departure']);
    }

    public function test_paid_booking_is_rebookable_within_the_post_departure_grace_period(): void
    {
        $ticket = $this->ticket('2026-08-03', '5:00 PM');

        $eligibility = $this->policy->assertEligible(
            $ticket,
            Carbon::parse('2026-08-04 4:59:59 PM')
        );

        $this->assertTrue($eligibility['after_departure']);
    }

    public function test_booking_is_not_normally_rebookable_when_the_grace_period_expires(): void
    {
        $ticket = $this->ticket('2026-08-03', '5:00 PM');

        $this->expectException(ValidationException::class);

        $this->policy->assertEligible(
            $ticket,
            Carbon::parse('2026-08-04 5:00 PM')
        );
    }

    public function test_it_rejects_a_replacement_without_enough_available_seats(): void
    {
        $this->expectException(ValidationException::class);

        $this->policy->assertEnoughSeats(3, 2);
    }

    public function test_an_already_rebooked_ticket_can_be_rebooked_again_by_staff(): void
    {
        $ticket = $this->ticket('2026-08-03', '5:00 PM');
        $ticket->is_rebooked = 1;

        $firstCheck = $this->policy->assertEligible(
            $ticket,
            Carbon::parse('2026-08-03 10:00 AM')
        );
        $secondCheck = $this->policy->assertEligible(
            $ticket,
            Carbon::parse('2026-08-03 11:00 AM')
        );

        $this->assertFalse($firstCheck['after_departure']);
        $this->assertFalse($secondCheck['after_departure']);
    }

    public function test_forfeiture_becomes_due_only_when_the_full_grace_period_has_elapsed(): void
    {
        $ticket = $this->ticket('2026-08-03', '5:00 PM');

        $this->assertFalse($this->policy->forfeitureIsDue(
            $ticket,
            Carbon::parse('2026-08-04 4:59:59 PM')
        ));
        $this->assertTrue($this->policy->forfeitureIsDue(
            $ticket,
            Carbon::parse('2026-08-04 5:00 PM')
        ));
    }

    public function test_pending_booking_cannot_be_rebooked_after_departure(): void
    {
        $ticket = $this->ticket('2026-08-03', '5:00 PM', Status::BOOKED_PENDING);

        $this->expectException(ValidationException::class);

        $this->policy->assertEligible(
            $ticket,
            Carbon::parse('2026-08-03 5:00 PM')
        );
    }

    private function ticket(
        string $date,
        string $departure,
        int $status = Status::BOOKED_APPROVED,
        int $tripId = 1
    ): BookedTicket {
        $ticket = new BookedTicket();
        $ticket->forceFill([
            'trip_id' => $tripId,
            'date_of_journey' => $date,
            'status' => $status,
            'is_rebooked' => 0,
        ]);
        $ticket->setRelation('trip', $this->trip($tripId, $departure));

        return $ticket;
    }

    private function trip(int $id, string $departure): Trip
    {
        $schedule = new Schedule();
        $schedule->forceFill([
            'start_from' => $departure,
            'status' => Status::ENABLE,
        ]);

        $trip = new Trip();
        $trip->forceFill([
            'id' => $id,
            'status' => Status::ENABLE,
            'trip_status' => Status::TRIP_ON_TIME,
        ]);
        $trip->setRelation('schedule', $schedule);

        return $trip;
    }
}
