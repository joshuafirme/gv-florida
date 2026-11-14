<?php

namespace App\Services;

use App\Models\FleetType;
use InvalidArgumentException;
use Illuminate\Support\Collection;

class SeatLayoutService
{
    /**
     * Build the canonical seat geometry consumed by every seat-map surface.
     */
    public function layout(FleetType|array $fleetType, array $states = []): array
    {
        $config = $this->configuration($fleetType);
        if ($config['custom_layout']) {
            $decks = $this->buildCustomDecks($config);
        } else {
            $decks = [];
            foreach ($config['deck_seats'] as $deckIndex => $seatCount) {
                $decks[] = $this->buildDeck($config, $deckIndex, $seatCount);
            }
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
            'is_custom' => (bool) $config['custom_layout'],
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

    /**
     * Build manifest rows from the fleet's configured seat groups.
     *
     * Aisles and empty cells are explicit so the view can preserve the bus
     * arrangement instead of flattening every fleet into the same grid.
     */
    public function manifestDecks(FleetType $fleetType): array
    {
        $layout = array_map('intval', explode('x', str_replace(' ', '', (string) $fleetType->seat_layout)));
        $groups = count($layout) === 3
            ? [$layout[0] ?? 0, $layout[1] ?? 0, $layout[2] ?? 0]
            : [$layout[0] ?? 0, $layout[1] ?? 0];
        $groups = array_values(array_filter($groups, fn (int $size) => $size > 0));

        // Retain the former two-column fallback for incomplete legacy records.
        if (!$groups) {
            $groups = [1, 1];
        }

        $seatsPerRow = array_sum($groups);
        $lastRows = array_values((array) ($fleetType->last_row ?? []));

        return collect($this->decks($fleetType))
            ->map(function (array $cells, int $deckIndex) use ($groups, $seatsPerRow, $lastRows) {
                $lastRowCells = [];
                $lastRowSeatCount = max((int) ($lastRows[$deckIndex] ?? 0), 0);

                if ($lastRowSeatCount > 0) {
                    for ($index = count($cells) - 1; $index >= 0 && count($lastRowCells) < $lastRowSeatCount; $index--) {
                        if (($cells[$index]['type'] ?? null) !== 'seat') {
                            continue;
                        }

                        array_unshift($lastRowCells, $cells[$index]);
                        unset($cells[$index]);
                    }

                    $cells = array_values($cells);
                }

                $rows = collect(array_chunk($cells, $seatsPerRow))
                    ->map(fn (array $row) => [
                        'slots' => $this->manifestRowSlots($row, $groups),
                        'centered' => false,
                    ])
                    ->values()
                    ->all();

                if ($lastRowCells) {
                    $rows[] = [
                        'slots' => $lastRowCells,
                        'centered' => true,
                    ];
                }

                return [
                    'name' => match ($deckIndex) {
                        0 => 'Lower Deck',
                        1 => 'Upper Deck',
                        default => 'Deck ' . ($deckIndex + 1),
                    },
                    'rows' => $rows,
                    'seat_columns' => $seatsPerRow,
                    'grid_template' => $this->manifestGridTemplate($groups),
                ];
            })
            ->values()
            ->all();
    }

    public function seatIds(FleetType $fleetType): Collection
    {
        return collect($this->layout($fleetType)['seat_ids']);
    }

    public function availableSeatIds(FleetType|array $fleetType, array $states = []): Collection
    {
        return collect($this->layout($fleetType, $states)['decks'])
            ->flatMap(fn (array $deck) => collect($deck['rows'])->flatMap(
                fn (array $row) => collect($row['groups'])->flatMap(fn (array $group) => $group['cells'])
            ))
            ->where('type', 'seat')
            ->where('state', 'available')
            ->pluck('seat_id')
            ->values();
    }

    public function availableSeatCount(FleetType|array $fleetType, array $states = []): int
    {
        return $this->availableSeatIds($fleetType, $states)->count();
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
     * Return the persisted custom geometry, or convert a legacy formula into
     * the same editable row/cell format used by the admin layout editor.
     */
    public function editableLayout(FleetType|array $fleetType): array
    {
        $config = $this->configuration($fleetType);
        if ($config['custom_layout']) {
            return $config['custom_layout'];
        }

        $layout = $this->layout($fleetType);
        $decks = collect($layout['decks'])->map(function (array $deck) {
            $rows = collect($deck['rows'])->map(function (array $row) {
                $cells = [];

                foreach ($row['groups'] as $groupIndex => $group) {
                    if ($groupIndex > 0) {
                        $cells[] = ['type' => 'aisle'];
                    }

                    foreach ($group['cells'] as $cell) {
                        $cells[] = $this->editableCell($cell);
                    }
                }

                return $cells;
            })->values()->all();

            $columnCount = max(collect($rows)->map(fn (array $row) => count($row))->max() ?? 1, 1);
            foreach ($rows as &$row) {
                $missing = $columnCount - count($row);
                if ($missing <= 0) {
                    continue;
                }

                $leftPadding = intdiv($missing, 2);
                $row = array_merge(
                    array_fill(0, $leftPadding, ['type' => 'empty']),
                    $row,
                    array_fill(0, $missing - $leftPadding, ['type' => 'empty'])
                );
            }
            unset($row);

            return [
                'name' => $deck['name'],
                'rows' => $rows,
            ];
        })->values()->all();

        return ['version' => 1, 'decks' => $decks];
    }

    /**
     * Validate and normalize admin-defined geometry before it is persisted.
     */
    public function validateCustomLayout(mixed $layout, array $expectedSeatCounts = []): array
    {
        if (is_string($layout)) {
            $layout = json_decode($layout, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('The custom seat layout is not valid JSON.');
            }
        }

        if (!is_array($layout) || !is_array($layout['decks'] ?? null) || !$layout['decks']) {
            throw new InvalidArgumentException('The custom seat layout must contain at least one deck.');
        }

        if (count($layout['decks']) > 5) {
            throw new InvalidArgumentException('A seat layout can contain up to five decks.');
        }

        $seatIds = [];
        $seatLabels = [];
        $decks = [];

        foreach (array_values($layout['decks']) as $deckIndex => $deck) {
            if (!is_array($deck) || !is_array($deck['rows'] ?? null) || !$deck['rows']) {
                throw new InvalidArgumentException('Each deck must contain at least one row.');
            }

            if (count($deck['rows']) > 40) {
                throw new InvalidArgumentException('A deck can contain up to 40 rows.');
            }

            $rows = [];
            $deckSeatCount = 0;
            $columnCount = null;

            foreach (array_values($deck['rows']) as $row) {
                if (!is_array($row) || !$row || count($row) > 12) {
                    throw new InvalidArgumentException('Every row must contain between 1 and 12 positions.');
                }

                $columnCount ??= count($row);
                if (count($row) !== $columnCount) {
                    throw new InvalidArgumentException('All rows on a deck must use the same number of positions.');
                }

                $cells = [];
                foreach (array_values($row) as $cell) {
                    $cell = is_array($cell) ? $cell : [];
                    $type = strtolower(trim((string) ($cell['type'] ?? 'empty')));
                    if (!in_array($type, ['seat', 'empty', 'aisle', 'cr', 'covered'], true)) {
                        throw new InvalidArgumentException('A layout position has an unsupported type.');
                    }

                    if ($type !== 'seat') {
                        $cells[] = $type === 'cr'
                            ? [
                                'type' => 'cr',
                                'label' => 'CR',
                                'row_span' => min(max((int) ($cell['row_span'] ?? 1), 1), 10),
                                'span' => min(max((int) ($cell['span'] ?? 1), 1), 6),
                            ]
                            : ['type' => $type];
                        continue;
                    }

                    $label = strtoupper(trim(strip_tags((string) ($cell['label'] ?? ''))));
                    if ($label === '' || mb_strlen($label) > 20) {
                        throw new InvalidArgumentException('Every seat needs a label of 20 characters or fewer.');
                    }

                    $seatId = strtoupper(trim(strip_tags((string) ($cell['id'] ?? ''))));
                    $seatId = $seatId ?: ($deckIndex + 1) . '-' . $label;
                    if (mb_strlen($seatId) > 40) {
                        throw new InvalidArgumentException('A seat identifier cannot exceed 40 characters.');
                    }

                    if (isset($seatIds[$seatId])) {
                        throw new InvalidArgumentException("Seat identifier {$seatId} is used more than once.");
                    }
                    if (isset($seatLabels[$label])) {
                        throw new InvalidArgumentException("Seat label {$label} is used more than once.");
                    }

                    $aliases = collect((array) ($cell['aliases'] ?? []))
                        ->map(fn ($alias) => strtoupper(trim(strip_tags((string) $alias))))
                        ->filter(fn (string $alias) => $alias !== '' && mb_strlen($alias) <= 40)
                        ->reject(fn (string $alias) => $alias === $seatId || $alias === $label)
                        ->unique()
                        ->values()
                        ->all();

                    $cells[] = [
                        'type' => 'seat',
                        'id' => $seatId,
                        'label' => $label,
                        'aliases' => $aliases,
                        'priority' => filter_var($cell['priority'] ?? false, FILTER_VALIDATE_BOOL),
                    ];
                    $seatIds[$seatId] = true;
                    $seatLabels[$label] = true;
                    $deckSeatCount++;
                }

                $rows[] = $cells;
            }

            if (isset($expectedSeatCounts[$deckIndex]) && $deckSeatCount !== (int) $expectedSeatCounts[$deckIndex]) {
                $deckNumber = $deckIndex + 1;
                throw new InvalidArgumentException(
                    "Deck {$deckNumber} must contain exactly {$expectedSeatCounts[$deckIndex]} seats; {$deckSeatCount} configured."
                );
            }

            $decks[] = [
                'name' => trim(strip_tags((string) ($deck['name'] ?? ''))) ?: $this->deckName($deckIndex),
                'rows' => $rows,
            ];
        }

        if ($expectedSeatCounts && count($decks) !== count($expectedSeatCounts)) {
            throw new InvalidArgumentException('The custom layout must contain the configured number of decks.');
        }

        return ['version' => 1, 'decks' => $decks];
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
        // Legal portrait with 10 mm printer-safe margins leaves 335.6 mm of
        // usable height. Reserve the remaining space for manifest metadata,
        // statistics, deck headings, and borders.
        $rowBudgetMm = 281.0;
        $maxRowHeightMm = 30.0;
        $rowHeightMm = floor(min($maxRowHeightMm, $rowBudgetMm / $rowCount) * 100) / 100;

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

        $customLayout = null;
        $customLayoutValue = $this->value($fleetType, 'custom_seat_layout');
        if ($customLayoutValue) {
            try {
                $customLayout = $this->validateCustomLayout($customLayoutValue);
            } catch (InvalidArgumentException) {
                $customLayout = null;
            }
        }

        if ($customLayout) {
            $customSeatsPerRow = collect($customLayout['decks'])
                ->flatMap(fn (array $deck) => $deck['rows'])
                ->map(fn (array $row) => collect($row)->where('type', '!=', 'aisle')->count())
                ->max();
            $customGroups = $this->customGroups($customLayout['decks'][0]['rows'][0] ?? []);
        }

        return [
            'name' => (string) $this->value($fleetType, 'name', 'Fleet Type'),
            'seat_layout' => $seatLayout,
            'groups' => $customGroups ?? $groups,
            'seats_per_row' => max((int) ($customSeatsPerRow ?? array_sum($groups)), 1),
            'deck_seats' => array_map('intval', array_values((array) $this->value($fleetType, 'deck_seats', []))),
            'last_row' => array_map('intval', array_values((array) $this->value($fleetType, 'last_row', []))),
            'prefixes' => array_map(fn ($prefix) => strtoupper(trim((string) $prefix)), array_values((array) $this->value($fleetType, 'prefixes', []))),
            'disabled_seats' => array_values((array) $this->value($fleetType, 'disabled_seats', [])),
            'cr_position' => strtolower((string) $this->value($fleetType, 'cr_position', '')),
            'cr_row' => max((int) $this->value($fleetType, 'cr_row', 0), 0),
            'cr_row_covered' => max((int) $this->value($fleetType, 'cr_row_covered', 1), 1),
            'cr_column_covered' => max((int) $this->value($fleetType, 'cr_column_covered', 1), 1),
            'cr_override_seat' => filter_var($this->value($fleetType, 'cr_override_seat', false), FILTER_VALIDATE_BOOL),
            'custom_layout' => $customLayout,
        ];
    }

    private function buildCustomDecks(array $config): array
    {
        return collect($config['custom_layout']['decks'])->map(function (array $deck, int $deckIndex) {
            $rows = collect($deck['rows'])->map(function (array $slots, int $rowIndex) use ($deckIndex) {
                $groups = [];
                $cells = [];
                $groupNumber = 0;

                $flush = function () use (&$groups, &$cells, &$groupNumber): void {
                    if (!$cells) {
                        return;
                    }

                    $groups[] = ['name' => 'custom-' . $groupNumber, 'cells' => $cells];
                    $cells = [];
                    $groupNumber++;
                };

                foreach ($slots as $slot) {
                    if ($slot['type'] === 'aisle') {
                        $flush();
                        continue;
                    }

                    $cells[] = $this->customCell($slot, $deckIndex, $rowIndex, $groupNumber);
                }
                $flush();

                return [
                    'number' => $rowIndex + 1,
                    'centered' => false,
                    'is_front_row' => $rowIndex === 0,
                    'groups' => $groups ?: [[
                        'name' => 'custom-0',
                        'cells' => [$this->emptyCell($deckIndex)],
                    ]],
                ];
            })->values()->all();

            return [
                'number' => $deckIndex + 1,
                'name' => $deck['name'] ?: $this->deckName($deckIndex),
                'prefix' => '',
                'rows' => $rows,
            ];
        })->values()->all();
    }

    private function customCell(array $slot, int $deckIndex, int $rowIndex, int $groupNumber): array
    {
        $base = [
            'type' => $slot['type'],
            'label' => $slot['type'] === 'cr' ? 'CR' : '',
            'seat_id' => null,
            'seat_aliases' => [],
            'deck' => $deckIndex + 1,
            'state' => 'static',
            'row' => $rowIndex + 1,
            'group' => 'custom-' . $groupNumber,
            'is_sc_pwd' => false,
        ];

        if ($slot['type'] === 'seat') {
            return array_merge($base, [
                'label' => $slot['label'],
                'seat_id' => $slot['id'],
                'seat_aliases' => $slot['aliases'] ?? [],
                'state' => 'available',
                'is_sc_pwd' => (bool) ($slot['priority'] ?? false),
            ]);
        }

        if ($slot['type'] === 'cr') {
            return array_merge($base, [
                'span' => $slot['span'] ?? 1,
                'row_span' => $slot['row_span'] ?? 1,
            ]);
        }

        return $base;
    }

    private function editableCell(array $cell): array
    {
        return match ($cell['type']) {
            'seat' => [
                'type' => 'seat',
                'id' => $cell['seat_id'],
                'label' => $cell['label'],
                'aliases' => $cell['seat_aliases'] ?? [],
                'priority' => (bool) ($cell['is_sc_pwd'] ?? false),
            ],
            'cr' => [
                'type' => 'cr',
                'label' => 'CR',
                'span' => $cell['span'] ?? 1,
                'row_span' => $cell['row_span'] ?? 1,
            ],
            'covered' => ['type' => 'covered'],
            default => ['type' => 'empty'],
        };
    }

    private function customGroups(array $row): array
    {
        $groups = [];
        $size = 0;

        foreach ($row as $cell) {
            if (($cell['type'] ?? null) === 'aisle') {
                if ($size > 0) {
                    $groups['custom-' . count($groups)] = $size;
                    $size = 0;
                }
                continue;
            }

            $size++;
        }

        if ($size > 0) {
            $groups['custom-' . count($groups)] = $size;
        }

        return $groups ?: ['custom-0' => 1];
    }

    private function deckName(int $deckIndex): string
    {
        return match ($deckIndex) {
            0 => 'Lower Deck',
            1 => 'Upper Deck',
            default => 'Deck ' . ($deckIndex + 1),
        };
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

                return $seatIndex->first(function (array $cell) use ($seat) {
                    return $cell['label'] === $seat
                        || in_array($seat, $cell['seat_aliases'] ?? [], true);
                })['seat_id'] ?? null;
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

    private function manifestRowSlots(array $row, array $groups): array
    {
        $slots = [];
        $offset = 0;
        $emptyCell = ['type' => 'empty', 'label' => '', 'seat_id' => null];

        foreach ($groups as $groupIndex => $groupSize) {
            for ($column = 0; $column < $groupSize; $column++) {
                $slots[] = $row[$offset] ?? $emptyCell;
                $offset++;
            }

            if ($groupIndex < count($groups) - 1) {
                $slots[] = ['type' => 'aisle', 'label' => '', 'seat_id' => null];
            }
        }

        return $slots;
    }

    private function manifestGridTemplate(array $groups): string
    {
        $tracks = [];

        foreach ($groups as $groupIndex => $groupSize) {
            $tracks[] = "repeat({$groupSize}, minmax(0, 1fr))";

            if ($groupIndex < count($groups) - 1) {
                $tracks[] = 'minmax(20px, .18fr)';
            }
        }

        return implode(' ', $tracks);
    }
}
