<?php

namespace App\Services;

use App\Models\FleetType;
use Illuminate\Support\Collection;

class SeatLayoutService
{
    public function decks(FleetType $fleetType): array
    {
        $layout = array_map('intval', explode('x', str_replace(' ', '', (string) $fleetType->seat_layout)));
        $left = $layout[0] ?? 0;
        $center = count($layout) === 3 ? ($layout[1] ?? 0) : 0;
        $right = count($layout) === 2 ? ($layout[1] ?? 0) : ($layout[2] ?? 0);
        $seatsPerRow = $left + $center + $right;
        $crOffset = match (strtolower((string) $fleetType->cr_position)) {
            'left' => $left > 0 ? 1 : null,
            'center' => $center > 0 ? $left + 1 : null,
            'right' => $right > 0 ? $left + $center + 1 : null,
            default => null,
        };
        $crSlot = $seatsPerRow > 0 && $crOffset && (int) $fleetType->cr_row > 0
            ? (((int) $fleetType->cr_row - 1) * $seatsPerRow) + $crOffset
            : null;
        $prefixes = array_values((array) ($fleetType->prefixes ?? []));
        $decks = [];

        foreach (array_values((array) $fleetType->deck_seats) as $deckIndex => $seatCount) {
            $prefix = (string) ($prefixes[$deckIndex] ?? '');
            $cells = [];

            for ($number = 1; $number <= (int) $seatCount; $number++) {
                $label = strtoupper($prefix . $number);
                $cells[] = [
                    'type' => 'seat',
                    'label' => $label,
                    'seat_id' => ($deckIndex + 1) . '-' . $label,
                ];
            }

            if ($deckIndex === 0 && $crSlot && $crSlot <= (int) $seatCount) {
                $crCell = ['type' => 'cr', 'label' => 'CR', 'seat_id' => null];

                if ($fleetType->cr_override_seat) {
                    $coveredRows = max((int) $fleetType->cr_row_covered, 1);
                    $coveredSlots = [];

                    for ($row = 0; $row < $coveredRows; $row++) {
                        $coveredSlot = $crSlot + ($row * $seatsPerRow);
                        if ($coveredSlot <= (int) $seatCount) {
                            $coveredSlots[] = $coveredSlot;
                        }
                    }

                    rsort($coveredSlots);
                    foreach ($coveredSlots as $coveredSlot) {
                        array_splice($cells, $coveredSlot - 1, 1);
                    }
                    array_splice($cells, $crSlot - 1, 0, [$crCell]);
                } else {
                    array_splice($cells, $crSlot - 1, 0, [$crCell]);
                }
            }

            $decks[] = $cells;
        }

        return $decks;
    }

    public function seatIds(FleetType $fleetType): Collection
    {
        return collect($this->decks($fleetType))
            ->flatten(1)
            ->where('type', 'seat')
            ->pluck('seat_id')
            ->values();
    }

    public function canonicalSeatId(FleetType $fleetType, string $seat): ?string
    {
        $seat = strtoupper(trim($seat));
        $seats = collect($this->decks($fleetType))->flatten(1)->where('type', 'seat');
        $exact = $seats->first(fn (array $cell) => $cell['seat_id'] === $seat);

        if ($exact) {
            return $exact['seat_id'];
        }

        $byLabel = $seats->first(fn (array $cell) => $cell['label'] === $seat);

        return $byLabel['seat_id'] ?? null;
    }

    public function canonicalizeSeats(FleetType $fleetType, array $seats): array
    {
        return collect($seats)
            ->map(fn ($seat) => $this->canonicalSeatId($fleetType, (string) $seat))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function disabledSeatIds(FleetType $fleetType): array
    {
        return $this->canonicalizeSeats(
            $fleetType,
            array_values((array) ($fleetType->disabled_seats ?? []))
        );
    }
}
