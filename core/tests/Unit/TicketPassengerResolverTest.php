<?php

namespace Tests\Unit;

use App\Models\BookedTicket;
use App\Services\TicketPassengerResolver;
use PHPUnit\Framework\TestCase;

class TicketPassengerResolverTest extends TestCase
{
    public function test_it_resolves_each_reference_to_only_its_assigned_passenger(): void
    {
        $ticket = new BookedTicket();
        $ticket->passenger_manifest = [
            [
                'seat' => '1-D1',
                'name' => '',
                'passenger_type' => 'regular',
                'fare' => 2100,
            ],
            [
                'seat' => '1-D2',
                'name' => 'Ms Karen',
                'passenger_type' => 'discounted',
                'discount_id' => 1,
                'discount_name' => 'Senior Citizen',
                'id_number' => '13221',
                'fare' => 1680,
            ],
        ];

        $resolver = new TicketPassengerResolver();

        $regular = $resolver->forSeat($ticket, '1-D1');
        $discounted = $resolver->forSeat($ticket, 'D2');

        $this->assertSame('Guest', $regular['name']);
        $this->assertSame('Regular', $regular['type']);
        $this->assertNull($regular['id_number']);

        $this->assertSame('Ms Karen', $discounted['name']);
        $this->assertSame('Senior Citizen', $discounted['type']);
        $this->assertSame('13221', $discounted['id_number']);
    }
}
