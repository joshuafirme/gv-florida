<?php

namespace App\Services;

use App\Models\FleetType;
use Illuminate\Support\Collection;

class SeatLayoutService
{
    /**
     * Build the canonical seat geometry consumed by every seat-map surface.
     */
    public function layout(FleetType|array $fleetType, array $states = []): array
    {
        $config = $this->configuration($fleetType);
        $decks = [];

        foreach ($config['deck_seats'] as $deckIndex => $seatCount) {
            $decks[] = $this->buildDeck($config, $deckIndex, $seatCount);
        }

        $seatIndex = collect($decks)
            ->flatMap(fn (array $deck) => collect($deck['rows'])->flatMap(
                fn (array $row) => collect($row['groups'])->flatMap(fn (array $group) => $group['cells'])
            ))
            ->where('type', 'seat')
            ->keyBy('seat_id');
        $stateIds = [
            'disabled' => $this->canonicalizeFromIndex($seatIndex, $config['disabled_seats']),
            'booked' => $this->canonicalizeFromIndex($seatIndex, $states['booked'] ?? []),
            'pending' => $this->canonicalizeFromIndex($seatIndex, $states['pending'] ?? []),
            'locked' => $this->canonicalizeFromIndex($seatIndex, $states['locked'] ?? []),
        ];

        foreach ($decks as &$deck) {
            foreach ($deck['rows'] as &$row) {
                foreach ($row['groups'] as &$group) {
                    foreach ($group['cells'] as &$cell) {
                        if ($cell['type'] !== 'seat') {
                            continue;
                        }

                        $cell['state'] = match (true) {
                            in_array($cell['seat_id'], $stateIds['disabled'], true) => 'disabled',
                            in_array($cell['seat_id'], $stateIds['locked'], true) => 'locked',
                            in_array($cell['seat_id'], $stateIds['booked'], true) => 'booked',
                            in_array($cell['seat_id'], $stateIds['pending'], true) => 'pending',
                            default => 'available',
                        };
                    }
                    unset($cell);
                }
                unset($group);
            }
            unset($row);
        }
        unset($deck);

        return [
            'name' => $config['name'],
            'seat_layout' => $config['seat_layout'],
            'groups' => $config['groups'],
            'seats_per_row' => $config['seats_per_row'],
            'decks' => $decks,
            'seat_ids' => $seatIndex->keys()->values()->all(),
            'disabled_seat_ids' => $stateIds['disabled'],
        ];
    }

    public function decks(FleetType $fleetType): array
    {
        return collect($this->layout($fleetType)['decks'])
            ->map(fn (array $deck) => collect($deck['rows'])
                ->flatMap(fn (array $row) => collect($row['groups'])
                    ->flatMap(fn (array $group) => $group['cells'])
                )
                ->reject(fn (array $cell) => in_array($cell['type'], ['empty', 'covered'], true))
                ->values()
                ->all())
            ->all();
    }

    public function seatIds(FleetType $fleetType): Collection
    {
        return collect($this->layout($fleetType)['seat_ids']);
    }

    public function canonicalSeatId(FleetType $fleetType, string $seat): ?string
    {
        $layout = $this->layout($fleetType);
        $seatIndex = $this->seatIndex($layout);

        return $this->canonicalizeFromIndex($seatIndex, [$seat])[0] ?? null;
    }

    public function canonicalizeSeats(FleetType $fleetType, array $seats): array
    {
        return $this->canonicalizeFromIndex($this->seatIndex($this->layout($fleetType)), $seats);
    }

    public function disabledSeatIds(FleetType $fleetType): array
    {
        return $this->layout($fleetType)['disabled_seat_ids'];
    }

    /**
     * Size every manifest row against one Legal portrait page. Both decks
     * share the same row height so their geometry stays visually consistent.
     */
    public function manifestPrintSizing(array $layout): array
    {
        $decks = collect($layout['decks'] ?? []);
        $deckCount = max($decks->count(), 1);
        $rowCount = max($decks->sum(fn (array $deck) => count($deck['rows'] ?? [])), 1);
        $rowBudgetMm = 300.0;
        $maxRowHeightMm = 30.0;
        $rowHeightMm = round(min($maxRowHeightMm, $rowBudgetMm / $rowCount), 2);

        return [
            'deck_count' => $deckCount,
            'row_count' => $rowCount,
            'row_height_mm' => $rowHeightMm,
            'density' => match (true) {
                $rowHeightMm < 14 => 'compact',
                $rowHeightMm < 17 => 'dense',
                default => 'standard',
            },
        ];
    }

    private function configuration(FleetType|array $fleetType): array
    {
        $seatLayout = (string) $this->value($fleetType, 'seat_layout', '');
        $segments = array_map('intval', explode('x', str_replace(' ', '', strtolower($seatLayout))));
        $groups = count($segments) === 3
            ? ['left' => $segments[0] ?? 0, 'center' => $segments[1] ?? 0, 'right' => $segments[2] ?? 0]
            : ['left' => $segments[0] ?? 0, 'right' => $segments[1] ?? 0];
        $groups = array_filter($groups, fn (int $size) => $size > 0);

        if (!$groups) {
            $groups = ['left' => 1, 'right' => 1];
        }

        return [
            'name' => (string) $this->value($fleetType, 'name', 'Fleet Type'),
            'seat_layout' => $seatLayout,
            'groups' => $groups,
            'seats_per_row' => array_sum($groups),
            'deck_seats' => array_map('intval', array_values((array) $this->value($fleetType, 'deck_seats', []))),
            'last_row' => array_map('intval', array_values((array) $this->value($fleetType, 'last_row', []))),
            'prefixes' => array_map(fn ($prefix) => strtoupper(trim((string) $prefix)), array_values((array) $this->value($fleetType, 'prefixes', []))),
            'disabled_seats' => array_values((array) $this->value($fleetType, 'disabled_seats', [])),
            'cr_position' => strtolower((string) $this->value($fleetType, 'cr_position', '')),
            'cr_row' => max((int) $this->value($fleetType, 'cr_row', 0), 0),
            'cr_row_covered' => max((int) $this->value($fleetType, 'cr_row_covered', 1), 1),
            'cr_column_covered' => max((int) $this->value($fleetType, 'cr_column_covered', 1), 1),
            'cr_override_seat' => filter_var($this->value($fleetType, 'cr_override_seat', false), FILTER_VALIDATE_BOOL),
        ];
    }

    private function buildDeck(array $config, int $deckIndex, int $seatCount): array
    {
        $prefix = $config['prefixes'][$deckIndex] ?? '';
        $cells = $this->buildDeckCells($config, $deckIndex, $seatCount, $prefix);

        $lastRowCount = max($config['last_row'][$deckIndex] ?? 0, 0);
        $lastRowCells = $this->extractLastRowSeats($cells, $lastRowCount);
        $rows = [];

        foreach (array_chunk($cells, $config['seats_per_row']) as $rowIndex => $rowCells) {
            $isPartial = count($rowCells) < $config['seats_per_row'];
            $rows[] = $this->layoutRow(
                $rowCells,
                $config['groups'],
                $deckIndex,
                $rowIndex,
                $isPartial
            );
        }

        if ($lastRowCells) {
            $rows[] = $this->layoutRow(
                $lastRowCells,
                $config['groups'],
                $deckIndex,
                count($rows),
                true
            );
        }

        return [
            'number' => $deckIndex + 1,
            'name' => match ($deckIndex) {
                0 => 'Lower Deck',
                1 => 'Upper Deck',
                default => 'Deck ' . ($deckIndex + 1),
            },
            'prefix' => $prefix,
            'rows' => $rows,
        ];
    }

    private function layoutRow(
        array $cells,
        array $groups,
        int $deckIndex,
        int $rowIndex,
        bool $centered
    ): array {
        if ($centered) {
            foreach ($cells as &$cell) {
                $cell['row'] = $rowIndex + 1;
                $cell['group'] = 'centered';
                $cell['is_sc_pwd'] = $deckIndex === 0 && $rowIndex === 0 && $cell['type'] === 'seat';
            }
            unset($cell);

            return [
                'number' => $rowIndex + 1,
                'centered' => true,
                'is_front_row' => $deckIndex === 0 && $rowIndex === 0,
                'groups' => [[
                    'name' => 'centered',
                    'cells' => $cells,
                ]],
            ];
        }

        $rowGroups = [];
        $offset = 0;

        foreach ($groups as $groupName => $groupSize) {
            $groupCells = [];
            for ($position = 0; $position < $groupSize; $position++) {
                $cell = $cells[$offset] ?? [
                    'type' => 'empty',
                    'label' => '',
                    'seat_id' => null,
                    'deck' => $deckIndex + 1,
                    'state' => 'static',
                ];
                $cell['row'] = $rowIndex + 1;
                $cell['group'] = $groupName;
                $cell['is_sc_pwd'] = $deckIndex === 0 && $rowIndex === 0 && $cell['type'] === 'seat';
                $groupCells[] = $cell;
                $offset++;
            }

            $rowGroups[] = [
                'name' => $groupName,
                'cells' => $groupCells,
            ];
        }

        return [
            'number' => $rowIndex + 1,
            'centered' => false,
            'is_front_row' => $deckIndex === 0 && $rowIndex === 0,
            'groups' => $rowGroups,
        ];
    }

    private function buildDeckCells(array $config, int $deckIndex, int $seatCount, string $prefix): array
    {
        $crSlots = $deckIndex === 0 ? $this->comfortRoomSlots($config, $seatCount) : [];
        $firstCrSlot = $crSlots ? min(array_keys($crSlots)) : null;
        $lastPhysicalSlot = $config['cr_override_seat'] || !$crSlots
            ? $seatCount - 1
            : max(($seatCount + count($crSlots)) - 1, max(array_keys($crSlots)));
        $cells = [];
        $seatNumber = 1;

        for ($slot = 0; $slot <= $lastPhysicalSlot; $slot++) {
            if (isset($crSlots[$slot])) {
                $cells[] = $slot === $firstCrSlot
                    ? $this->comfortRoomCell($config, $deckIndex, $crSlots)
                    : $this->coveredCell($deckIndex);
                continue;
            }

            if ($seatNumber > $seatCount) {
                $cells[] = $this->emptyCell($deckIndex);
                continue;
            }

            $number = $config['cr_override_seat'] ? $slot + 1 : $seatNumber;
            $label = $prefix . $number;
            $cells[] = [
                'type' => 'seat',
                'label' => $label,
                'seat_id' => ($deckIndex + 1) . '-' . $label,
                'deck' => $deckIndex + 1,
                'state' => 'available',
            ];
            $seatNumber++;
        }

        return $cells;
    }

    private function comfortRoomSlots(array $config, int $seatCount): array
    {
        if (!$config['cr_row'] || !$config['cr_position']) {
            return [];
        }

        $offset = 0;
        foreach ($config['groups'] as $groupName => $groupSize) {
            if ($groupName === $config['cr_position']) {
                break;
            }
            $offset += $groupSize;
        }

        if (!isset($groupSize) || $groupName !== $config['cr_position']) {
            return [];
        }

        $start = (($config['cr_row'] - 1) * $config['seats_per_row']) + $offset;
        if ($start >= $seatCount) {
            return [];
        }

        $columnSpan = $this->comfortRoomColumnSpan($config);
        $availableRows = (int) ceil($seatCount / $config['seats_per_row']) - $config['cr_row'] + 1;
        $rowSpan = min($config['cr_row_covered'], max($availableRows, 1));
        $slots = [];

        for ($row = 0; $row < $rowSpan; $row++) {
            for ($column = 0; $column < $columnSpan; $column++) {
                $slots[$start + ($row * $config['seats_per_row']) + $column] = true;
            }
        }

        return $slots;
    }

    private function comfortRoomColumnSpan(array $config): int
    {
        $groupSize = $config['groups'][$config['cr_position']] ?? 1;

        return min(max($config['cr_column_covered'], 1), $groupSize);
    }

    private function comfortRoomCell(array $config, int $deckIndex, array $slots): array
    {
        $coveredRows = collect(array_keys($slots))
            ->map(fn (int $slot) => intdiv($slot, $config['seats_per_row']))
            ->unique()
            ->count();

        return [
            'type' => 'cr',
            'label' => 'CR',
            'seat_id' => null,
            'deck' => $deckIndex + 1,
            'state' => 'static',
            'span' => $this->comfortRoomColumnSpan($config),
            'row_span' => max($coveredRows, 1),
        ];
    }

    private function coveredCell(int $deckIndex): array
    {
        return [
            'type' => 'covered',
            'label' => '',
            'seat_id' => null,
            'deck' => $deckIndex + 1,
            'state' => 'static',
        ];
    }

    private function emptyCell(int $deckIndex): array
    {
        return [
            'type' => 'empty',
            'label' => '',
            'seat_id' => null,
            'deck' => $deckIndex + 1,
            'state' => 'static',
        ];
    }

    private function extractLastRowSeats(array &$cells, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $lastRow = [];
        for ($index = count($cells) - 1; $index >= 0 && count($lastRow) < $count; $index--) {
            if (($cells[$index]['type'] ?? null) !== 'seat') {
                continue;
            }

            array_unshift($lastRow, $cells[$index]);
            unset($cells[$index]);
        }
        $cells = array_values($cells);

        return $lastRow;
    }

    private function seatIndex(array $layout): Collection
    {
        return collect($layout['decks'])
            ->flatMap(fn (array $deck) => collect($deck['rows'])->flatMap(
                fn (array $row) => collect($row['groups'])->flatMap(fn (array $group) => $group['cells'])
            ))
            ->where('type', 'seat')
            ->keyBy('seat_id');
    }

    private function canonicalizeFromIndex(Collection $seatIndex, array $seats): array
    {
        return collect($seats)
            ->map(function ($seat) use ($seatIndex) {
                $seat = strtoupper(trim(strip_tags((string) $seat)));
                if ($seatIndex->has($seat)) {
                    return $seat;
                }

                return $seatIndex->first(fn (array $cell) => $cell['label'] === $seat)['seat_id'] ?? null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function value(FleetType|array $fleetType, string $key, mixed $default = null): mixed
    {
        return is_array($fleetType)
            ? ($fleetType[$key] ?? $default)
            : ($fleetType->{$key} ?? $default);
    }
}
