<?php

namespace App\Services;

use App\Models\CashierTransactionEvent;
use Carbon\Carbon;

class TransactionReasonFormatter
{
    public function format(CashierTransactionEvent $transaction): string
    {
        $reason = trim((string) $transaction->reason);

        return match ($transaction->status) {
            'Sold' => $reason !== '' ? "Sale: {$reason}" : 'Ticket sold.',
            'Rebooked' => $this->formatRebooking($transaction, $reason),
            'Cancelled' => $reason !== '' ? "Cancellation: {$reason}" : 'Ticket cancelled.',
            'Voided' => $reason !== '' ? "Void: {$reason}" : 'Ticket voided.',
            'Refunded' => $reason !== '' ? "Refund: {$reason}" : 'Ticket refunded.',
            default => $reason !== '' ? $reason : ($transaction->status ?: '-'),
        };
    }

    private function formatRebooking(CashierTransactionEvent $transaction, string $reason): string
    {
        $history = data_get($transaction->snapshot, 'rebooking', []);
        $previous = $history['previous'] ?? [];
        $new = $history['new'] ?? [];
        $changes = [];

        $this->addChange(
            $changes,
            'Travel Date',
            $this->formatDate($previous['journey_date'] ?? null),
            $this->formatDate($new['journey_date'] ?? null)
        );
        $this->addChange(
            $changes,
            'Departure Time',
            $this->formatTime($previous['departure_at'] ?? null),
            $this->formatTime($new['departure_at'] ?? null)
        );
        [$previousTrip, $newTrip] = $this->tripLabels($previous, $new);
        $this->addChange($changes, 'Trip', $previousTrip, $newTrip);
        $this->addChange(
            $changes,
            'Bus Class',
            $this->text($previous['trip_class'] ?? null),
            $this->text($new['trip_class'] ?? null)
        );
        $this->addChange(
            $changes,
            'Seat No.',
            $this->seat($history['previous_seat'] ?? null),
            $this->seat($history['new_seat'] ?? null)
        );
        $this->addChange(
            $changes,
            'Drop-Off',
            $this->dropOff($previous),
            $this->dropOff($new)
        );

        $sequence = (int) ($history['sequence'] ?? data_get($transaction->snapshot, 'rebooking_sequence', 0));
        $label = $sequence > 0 ? "Rebooking #{$sequence}" : 'Rebooking';

        if (!$changes) {
            return $reason !== '' ? "{$label}: {$reason}" : "{$label} completed.";
        }

        if ($reason !== '' && !$this->isGeneratedRebookingReason($reason)) {
            $changes[] = "Reason: {$reason}";
        }

        return $label . ': ' . implode('; ', $changes);
    }

    private function addChange(array &$changes, string $label, string $previous, string $new): void
    {
        if ($previous === '' || $new === '' || strcasecmp($previous, $new) === 0) {
            return;
        }

        $changes[] = "{$label}: {$previous} to {$new}";
    }

    private function formatDate($value): string
    {
        if (!$value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('M j, Y');
        } catch (\Throwable $exception) {
            return $this->text($value);
        }
    }

    private function formatTime($value): string
    {
        if (!$value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('h:i A');
        } catch (\Throwable $exception) {
            return $this->text($value);
        }
    }

    private function seat($value): string
    {
        if (is_array($value)) {
            return collect($value)->map(fn ($seat) => formatSeatLabel($seat))->filter()->implode(', ');
        }

        return $value ? (string) formatSeatLabel($value) : '';
    }

    private function dropOff(array $details): string
    {
        $name = $this->text($details['drop_off'] ?? null);
        $kmPost = $this->text($details['km_post'] ?? null);

        if ($kmPost !== '' && !str_starts_with(strtoupper($kmPost), 'KM ')) {
            $kmPost = 'KM ' . $kmPost;
        }

        if ($name !== '' && $kmPost !== '') {
            return "{$name} ({$kmPost})";
        }

        return $name ?: $kmPost;
    }

    private function tripLabels(array $previous, array $new): array
    {
        $previousTrip = $this->text($previous['trip'] ?? null);
        $newTrip = $this->text($new['trip'] ?? null);
        $previousId = $this->text($previous['trip_id'] ?? null);
        $newId = $this->text($new['trip_id'] ?? null);

        if ($previousTrip !== ''
            && $newTrip !== ''
            && strcasecmp($previousTrip, $newTrip) === 0
            && $previousId !== ''
            && $newId !== ''
            && $previousId !== $newId) {
            return [
                "{$previousTrip} (Trip #{$previousId})",
                "{$newTrip} (Trip #{$newId})",
            ];
        }

        return [$previousTrip, $newTrip];
    }

    private function text($value): string
    {
        return trim((string) $value);
    }

    private function isGeneratedRebookingReason(string $reason): bool
    {
        foreach (preg_split('/;\s*/', $reason) ?: [] as $part) {
            if (!preg_match('/^(Travel date|Departure time|Trip|Bus class|Seat(?: No\.)?|Drop-?off) changed from .+ to .+$/i', trim($part))) {
                return false;
            }
        }

        return true;
    }
}
