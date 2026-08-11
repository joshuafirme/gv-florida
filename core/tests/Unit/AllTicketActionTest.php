<?php

namespace Tests\Unit;

use App\Constants\Status;
use App\Http\Controllers\Admin\VehicleTicketController;
use App\Models\BookedTicket;
use App\Models\SlipSeriesNumber;
use App\Models\TicketCancellation;
use App\Models\TicketRefund;
use App\Models\TicketVoid;
use ReflectionMethod;
use Tests\TestCase;

class AllTicketActionTest extends TestCase
{
    public function test_each_ticket_status_only_receives_its_applicable_actions(): void
    {
        $booked = $this->slip(Status::BOOKED_APPROVED);
        $pending = $this->slip(Status::BOOKED_PENDING);
        $rebooked = $this->slip(Status::BOOKED_APPROVED);
        $refunded = $this->slip(Status::BOOKED_REFUNDED, new TicketRefund(['id' => 31]));
        $cancelled = $this->slip(Status::BOOKED_CANCELLED, null, new TicketCancellation(['id' => 32]));
        $voided = $this->slip(Status::BOOKED_VOIDED, null, null, new TicketVoid(['id' => 33]));

        $this->assertSame(
            ['Reservation slip', 'Rebook ticket', 'Refund ticket', 'Cancel ticket', 'Void ticket'],
            $this->labels($this->actions($booked, 'Booked'))
        );
        $this->assertSame(['Rebook pending ticket'], $this->labels($this->actions($pending, 'Pending')));
        $this->assertSame(
            ['View rebooking history', 'Print current ticket', 'Rebook this ticket again'],
            $this->labels($this->actions($rebooked, 'Rebooked'))
        );
        $this->assertSame(['View refund record'], $this->labels($this->actions($refunded, 'Refunded')));
        $this->assertSame(['View cancellation acknowledgment'], $this->labels($this->actions($cancelled, 'Cancelled')));
        $this->assertSame(['View void transaction'], $this->labels($this->actions($voided, 'Voided')));
        $this->assertSame([], $this->actions($this->slip(Status::BOOKED_EXPIRED), 'Expired'));
        $this->assertSame([], $this->actions($this->slip(Status::BOOKED_REJECTED), 'Rejected'));
    }

    public function test_terminal_records_never_receive_active_ticket_actions(): void
    {
        $slip = $this->slip(
            Status::BOOKED_APPROVED,
            new TicketRefund(['id' => 41]),
            new TicketCancellation(['id' => 42]),
            new TicketVoid(['id' => 43])
        );

        $this->assertSame(['View void transaction'], $this->labels($this->actions($slip, 'Voided')));
    }

    private function slip(
        int $status,
        ?TicketRefund $refund = null,
        ?TicketCancellation $cancellation = null,
        ?TicketVoid $void = null
    ): SlipSeriesNumber {
        $ticket = new BookedTicket();
        $ticket->forceFill(['id' => 11, 'status' => $status]);

        $slip = new SlipSeriesNumber();
        $slip->forceFill(['id' => 21, 'booked_ticket_id' => 11]);
        $slip->setRelation('bookedTicket', $ticket);
        $slip->setRelation('refund', $refund);
        $slip->setRelation('cancellation', $cancellation);
        $slip->setRelation('voidRecord', $void);

        $refund?->forceFill(['id' => 31]);
        $cancellation?->forceFill(['id' => 32]);
        $void?->forceFill(['id' => 33]);

        return $slip;
    }

    private function actions(SlipSeriesNumber $slip, string $status): array
    {
        $method = new ReflectionMethod(VehicleTicketController::class, 'allTicketActions');

        return $method->invoke(new VehicleTicketController(), $slip, $status);
    }

    private function labels(array $actions): array
    {
        return array_column($actions, 'label');
    }
}
