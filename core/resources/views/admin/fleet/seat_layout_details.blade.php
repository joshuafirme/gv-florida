@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-md-4">
                            @php
                                $disabled_seats = $fleetType->disabled_seats ? $fleetType->disabled_seats : [];
                            @endphp
                            @foreach ($fleetType->deck_seats as $key => $seat)
                                <div class="seat-plan-inner m-4 p-3">
                                    <div class="single">

                                        @php
                                            echo $busLayout->getDeckHeader($loop->index);
                                        @endphp

                                        @php
                                            $totalRow = $busLayout->getTotalRow($seat) + 1;
                                            $lastRowSeat = $busLayout->getLastRowSit($seat);
                                            $deckIndex = $loop->index + 1;
                                            $seatlayout = $busLayout->sitLayouts();
                                            $colItem = $seatlayout->left + $seatlayout->right;
                                            $seatCounter = 1;
                                            $total_seats = $totalRow * $colItem;
                                            $prefix = $fleetType->prefixes ? $fleetType->prefixes[$key] : '';
                                            $has_cr = false;
                                        @endphp

                                        {{-- Main Rows --}}
                                        @for ($row = 1; $row <= $totalRow; $row++)
                                            @php
                                                if ($lastRowSeat == 1 && $row == $totalRow) {
                                                    break;
                                                }

                                                $seatNumber = '';
                                            @endphp
                                            <div class="seat-wrapper">
                                                <div class="left-side">
                                                    @for ($ls = 1; $ls <= $seatlayout->left; $ls++)
                                                        @php
                                                            if ($fleetType->last_row) {
                                                                $offset = $seat - $fleetType->last_row[$key];
                                                                if ($seatCounter > $offset) {
                                                                    continue;
                                                                }
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
                                                                echo $busLayout->generateSeats(
                                                                    $ls,
                                                                    $deckIndex,
                                                                    $seatNumber,
                                                                    $label,
                                                                );
                                                            @endphp
                                                        @endif
                                                        @php
                                                            $seatCounter++;
                                                        @endphp
                                                    @endfor
                                                </div>
                                                <div class="right-side">
                                                    @for ($rs = 1; $rs <= $seatlayout->right; $rs++)
                                                        @php
                                                            if ($fleetType->last_row) {
                                                                $offset = $seat - $fleetType->last_row[$key];
                                                                if ($seatCounter > $offset) {
                                                                    continue;
                                                                }
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
                                                                echo $busLayout->generateSeats(
                                                                    $ls,
                                                                    $deckIndex,
                                                                    $seatNumber,
                                                                    $label,
                                                                );
                                                            @endphp
                                                        @endif

                                                        @php
                                                            $seatCounter++;
                                                        @endphp
                                                    @endfor
                                                </div>
                                            </div>
                                        @endfor
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
                                            @if ($lastRowSeat == 1)
                                                @php $seatNumber++ @endphp
                                                <div class="seat-wrapper justify-content-center">
                                                    @for ($lsr = 1; $lsr <= $colItem + 1; $lsr++)
                                                        @php echo $busLayout->generateSeats($lsr, $deckIndex, $seatNumber, $prefix.$seatCounter); @endphp
                                                        @php $seatCounter++; @endphp
                                                    @endfor
                                                </div>
                                            @endif
                                            @if ($lastRowSeat > 1)
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
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
