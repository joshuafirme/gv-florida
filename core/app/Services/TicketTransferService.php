<?php

namespace App\Services;

use InvalidArgumentException;

class TicketTransferService
{
    public const PASSENGER_REQUESTED = 'passenger';
    public const MANAGEMENT_INITIATED = 'management';

    public const STATUS_UPGRADED = 'Upgraded';
    public const STATUS_DOWNGRADED = 'Downgraded';
    public const STATUS_TRANSFERRED = 'Transferred';
    public const STATUS_MANAGEMENT_TRANSFERRED = 'Management Transferred';

    public const TRANSACTION_STATUSES = [
        self::STATUS_UPGRADED,
        self::STATUS_DOWNGRADED,
        self::STATUS_TRANSFERRED,
        self::STATUS_MANAGEMENT_TRANSFERRED,
    ];

    public function quote(string $type, float $originalFare, float $selectedFare): array
    {
        if (!in_array($type, [self::PASSENGER_REQUESTED, self::MANAGEMENT_INITIATED], true)) {
            throw new InvalidArgumentException('Unsupported ticket transfer type.');
        }

        $originalFare = round(max($originalFare, 0), 2);
        $selectedFare = round(max($selectedFare, 0), 2);
        $difference = round($selectedFare - $originalFare, 2);

        if ($type === self::MANAGEMENT_INITIATED) {
            $refund = $difference < 0 ? abs($difference) : 0.0;

            return [
                'status' => self::STATUS_MANAGEMENT_TRANSFERRED,
                'original_fare' => $originalFare,
                'selected_fare' => $selectedFare,
                'final_fare' => $difference < 0 ? $selectedFare : $originalFare,
                'amount_due' => 0.0,
                'refund_amount' => $refund,
                'cash_adjustment' => -$refund,
            ];
        }

        if ($difference > 0) {
            return [
                'status' => self::STATUS_UPGRADED,
                'original_fare' => $originalFare,
                'selected_fare' => $selectedFare,
                'final_fare' => $selectedFare,
                'amount_due' => $difference,
                'refund_amount' => 0.0,
                'cash_adjustment' => $difference,
            ];
        }

        if ($difference < 0) {
            return [
                'status' => self::STATUS_DOWNGRADED,
                'original_fare' => $originalFare,
                'selected_fare' => $selectedFare,
                'final_fare' => $originalFare,
                'amount_due' => 0.0,
                'refund_amount' => 0.0,
                'cash_adjustment' => 0.0,
            ];
        }

        return [
            'status' => self::STATUS_TRANSFERRED,
            'original_fare' => $originalFare,
            'selected_fare' => $selectedFare,
            'final_fare' => $originalFare,
            'amount_due' => 0.0,
            'refund_amount' => 0.0,
            'cash_adjustment' => 0.0,
        ];
    }
}
