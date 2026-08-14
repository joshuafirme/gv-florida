<?php

namespace Tests\Unit;

use App\Models\CashierTransactionEvent;
use App\Services\TransactionReasonFormatter;
use Tests\TestCase;

class TransactionReasonFormatterTest extends TestCase
{
    public function test_rebooking_reason_lists_every_changed_detail_and_staff_reason(): void
    {
        $transaction = new CashierTransactionEvent([
            'status' => 'Rebooked',
            'reason' => 'Management-approved correction',
            'snapshot' => [
                'rebooking_sequence' => 2,
                'rebooking' => [
                    'sequence' => 2,
                    'previous_seat' => '1-D1',
                    'new_seat' => '2-U2',
                    'previous' => [
                        'journey_date' => '2026-08-10',
                        'departure_at' => '2026-08-10T08:00:00+08:00',
                        'trip' => 'Laoag',
                        'trip_class' => 'Executive Sleeper',
                        'drop_off' => 'Laoag',
                        'km_post' => '488',
                    ],
                    'new' => [
                        'journey_date' => '2026-08-12',
                        'departure_at' => '2026-08-12T09:30:00+08:00',
                        'trip' => 'Tuguegarao',
                        'trip_class' => 'Cyan Executive Sleeper',
                        'drop_off' => 'Tuguegarao',
                        'km_post' => '485',
                    ],
                ],
            ],
        ]);

        $reason = (new TransactionReasonFormatter())->format($transaction);

        $this->assertStringContainsString('Rebooking #2:', $reason);
        $this->assertStringContainsString('Travel Date: Aug 10, 2026 to Aug 12, 2026', $reason);
        $this->assertStringContainsString('Departure Time: 08:00 AM to 09:30 AM', $reason);
        $this->assertStringContainsString('Trip: Laoag to Tuguegarao', $reason);
        $this->assertStringContainsString('Bus Class: Executive Sleeper to Cyan Executive Sleeper', $reason);
        $this->assertStringContainsString('Seat No.: D1 to U2', $reason);
        $this->assertStringContainsString('Drop-Off: Laoag (KM 488) to Tuguegarao (KM 485)', $reason);
        $this->assertStringContainsString('Reason: Management-approved correction', $reason);
    }

    public function test_generated_rebooking_reason_is_not_duplicated(): void
    {
        $transaction = new CashierTransactionEvent([
            'status' => 'Rebooked',
            'reason' => 'Seat changed from D1 to D2',
            'snapshot' => [
                'rebooking' => [
                    'sequence' => 1,
                    'previous_seat' => '1-D1',
                    'new_seat' => '1-D2',
                ],
            ],
        ]);

        $this->assertSame(
            'Rebooking #1: Seat No.: D1 to D2',
            (new TransactionReasonFormatter())->format($transaction)
        );
    }

    public function test_same_route_trip_change_is_identified_by_trip_number(): void
    {
        $transaction = new CashierTransactionEvent([
            'status' => 'Rebooked',
            'snapshot' => [
                'rebooking' => [
                    'previous' => ['trip_id' => 10, 'trip' => 'Laoag'],
                    'new' => ['trip_id' => 12, 'trip' => 'Laoag'],
                ],
            ],
        ]);

        $this->assertSame(
            'Rebooking: Trip: Laoag (Trip #10) to Laoag (Trip #12)',
            (new TransactionReasonFormatter())->format($transaction)
        );
    }

    public function test_each_transaction_type_has_a_standard_reason_label(): void
    {
        $formatter = new TransactionReasonFormatter();

        $this->assertSame('Ticket sold.', $formatter->format($this->transaction('Sold')));
        $this->assertSame('Cancellation: Passenger no-show', $formatter->format($this->transaction('Cancelled', 'Passenger no-show')));
        $this->assertSame('Void: Duplicate booking', $formatter->format($this->transaction('Voided', 'Duplicate booking')));
        $this->assertSame('Refund: Change of plans', $formatter->format($this->transaction('Refunded', 'Change of plans')));
    }

    private function transaction(string $status, ?string $reason = null): CashierTransactionEvent
    {
        return new CashierTransactionEvent([
            'status' => $status,
            'reason' => $reason,
        ]);
    }
}
