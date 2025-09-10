@php
    $disabled_seats = $fleetType->disabled_seats ? $fleetType->disabled_seats : [];
@endphp

<div class="container p-4">
        <h4>{{ $fleetType->name }}</h4>
    @foreach ($fleetType->deck_seats as $key => $seat)
        <div class="seat-plan-inner" style="max-width: 400px">
            <div class="single">

                @php
                    echo $busLayout->getDeckHeader($loop->index);
                @endphp

                @php
                    // REFACTORED: More direct calculation of full rows and the remaining seats.
                    $fullRows = $busLayout->getTotalRow($seat);
                    $lastRowSeat = $busLayout->getLastRowSit($seat);
                    $deckIndex = $loop->index + 1;
                    $seatlayout = $busLayout->sitLayouts();
                    $colItem = $seatlayout->left + $seatlayout->center + $seatlayout->right;
                    $seatCounter = 1;
                    $total_seats = $fullRows * $colItem + $lastRowSeat;
                    $prefix = $fleetType->prefixes ? $fleetType->prefixes[$key] : '';
                    $has_cr = false;
                @endphp

                {{-- Main Rows --}}
                @for ($row = 1; $row <= $fullRows; $row++)
                    @php
                        // The confusing break condition has been removed.
                        $seatNumber = '';
                    @endphp
                    <div class="seat-wrapper">
                        {{-- Left Side --}}
                        <div class="left-side">
                            @for ($ls = 1; $ls <= $seatlayout->left; $ls++)
                                @php
                                    if ($fleetType->last_row && $seatCounter > $seat - $fleetType->last_row[$key]) {
                                        continue;
                                    }
                                @endphp
                                @if (($row == $fleetType->cr_row || $row == $fleetType->cr_row + 1) && $fleetType->cr_position == 'Left')
                                    @if (!$has_cr)
                                        <div>
                                            <span class='seat comfort-room cr-left'>
                                                CR
                                                <span></span>
                                            </span>
                                        </div>
                                    @endif
                                    @php
                                        $seatCounter--;
                                        $has_cr = true;
                                    @endphp
                                @else
                                    @php
                                        $label = $prefix . $seatCounter;
                                        if (in_array($label, $disabled_seats)) {
                                            $label = "<del>$label</del>";
                                        }
                                        echo $busLayout->generateSeats($ls, $deckIndex, $seatNumber, $label);
                                    @endphp
                                @endif
                                @php
                                    $seatCounter++;
                                @endphp
                            @endfor
                        </div>

                        {{-- NEW: Center Side --}}
                        <div class="center-side">
                            @for ($cs = 1; $cs <= $seatlayout->center; $cs++)
                                @php
                                    if ($fleetType->last_row && $seatCounter > $seat - $fleetType->last_row[$key]) {
                                        continue;
                                    }
                                @endphp
                                {{-- This logic assumes a CR could be positioned in the center --}}
                                @if (($row == $fleetType->cr_row || $row == $fleetType->cr_row + 1) && $fleetType->cr_position == 'Center')
                                    @if (!$has_cr)
                                        <div>
                                            <span class='seat comfort-room cr-center'>
                                                CR
                                                <span></span>
                                            </span>
                                        </div>
                                    @endif
                                    @php
                                        $seatCounter--;
                                        $has_cr = true;
                                    @endphp
                                @else
                                    @php
                                        $label = $prefix . $seatCounter;
                                        if (in_array($label, $disabled_seats)) {
                                            $label = "<del>$label</del>";
                                        }
                                        echo $busLayout->generateSeats($cs, $deckIndex, $seatNumber, $label);
                                    @endphp
                                @endif
                                @php
                                    $seatCounter++;
                                @endphp
                            @endfor
                        </div>

                        {{-- Right Side --}}
                        <div class="right-side">
                            @for ($rs = 1; $rs <= $seatlayout->right; $rs++)
                                @php
                                    if ($fleetType->last_row && $seatCounter > $seat - $fleetType->last_row[$key]) {
                                        continue;
                                    }
                                @endphp

                                @if (($row == $fleetType->cr_row || $row == $fleetType->cr_row + 1) && $fleetType->cr_position == 'Right')
                                    @if (!$has_cr)
                                        <div>
                                            <span class='seat comfort-room cr-right'>
                                                CR
                                                <span></span>
                                            </span>
                                        </div>
                                    @endif
                                    @php
                                        $seatCounter--;
                                        $has_cr = true;
                                    @endphp
                                @else
                                    @php
                                        $label = $prefix . $seatCounter;
                                        if (in_array($label, $disabled_seats)) {
                                            $label = "<del>$label</del>";
                                        }
                                        echo $busLayout->generateSeats($rs, $deckIndex, $seatNumber, $label);
                                    @endphp
                                @endif

                                @php
                                    $seatCounter++;
                                @endphp
                            @endfor
                        </div>
                    </div>
                @endfor

                {{-- This section handles the last row which may have a different number of seats --}}
                @if ($fleetType->last_row)
                    @php $seatNumber++ @endphp
                    <div class="seat-wrapper justify-content-center">
                        @for ($lsr = 1; $lsr <= $fleetType->last_row[$key]; $lsr++)
                            @php echo $busLayout->generateSeats($lsr, $deckIndex, $seatNumber, $prefix.$seatCounter); @endphp
                            @php
                                $seatCounter++;
                            @endphp
                        @endfor
                    </div>
                @else
                    {{-- REFACTORED AND FIXED: This logic now correctly renders the remaining seats. --}}
                    @if ($lastRowSeat > 0)
                        @php $seatNumber++ @endphp
                        <div class="seat-wrapper justify-content-center">
                            @for ($l = 1; $l <= $lastRowSeat; $l++)
                                @php echo $busLayout->generateSeats($l, $deckIndex, $seatNumber, $prefix.$seatCounter); @endphp
                                @php $seatCounter++; @endphp
                            @endfor
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endforeach
</div>
