<?php

namespace App\Services;

use App\Models\BookedTicket;

class TicketPassengerResolver
{
    public function forSeat(BookedTicket $ticket, string $seat): array
    {
        $entry = $this->manifestEntry($ticket, $seat);

        if ($entry !== null) {
            $discounted = strtolower((string) ($entry['passenger_type'] ?? 'regular')) === 'discounted'
                || !empty($entry['discount_id'])
                || !empty($entry['discount_name']);

            return [
                'manifest_found' => true,
                'name' => trim((string) ($entry['name'] ?? '')) ?: 'Guest',
                'type' => $discounted
                    ? ($entry['discount_name'] ?? 'Discounted')
                    : 'Regular',
                'id_number' => $entry['id_number'] ?? null,
                'entry' => $entry,
            ];
        }

        return [
            'manifest_found' => false,
            'name' => $ticket->deposit?->userDiscount?->passenger_name
                ?: $ticket->user?->fullname
                ?: 'Guest',
            'type' => getPassengerType($ticket->deposit),
            'id_number' => $ticket->deposit?->userDiscount?->id_number,
            'entry' => [],
        ];
    }

    private function manifestEntry(BookedTicket $ticket, string $seat): ?array
    {
        $manifest = collect(
            $ticket->passenger_manifest
                ?: ($ticket->deposit?->userDiscount?->passenger_manifest ?: [])
        )
            ->map(fn ($entry) => is_array($entry) ? $entry : (array) $entry)
            ->filter(fn (array $entry) => trim((string) ($entry['seat'] ?? '')) !== '')
            ->values();

        $exact = $manifest->first(
            fn (array $entry) => strcasecmp(
                trim((string) $entry['seat']),
                trim($seat)
            ) === 0
        );

        if ($exact !== null) {
            return $exact;
        }

        $normalizedSeat = $this->normalizeSeat($seat);
        $matches = $manifest
            ->filter(fn (array $entry) => $this->normalizeSeat((string) $entry['seat']) === $normalizedSeat)
            ->values();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function normalizeSeat(string $seat): string
    {
        return strtoupper((string) preg_replace('/^\d+-/', '', trim($seat)));
    }
}
