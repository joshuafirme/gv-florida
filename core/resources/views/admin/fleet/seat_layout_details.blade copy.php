@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="row">
                    <div class="col-md-4">
                        @foreach ($fleetType->deck_seats as $seat)
                        <div class="seat-plan-inner m-4 p-3">
                            <div class="single">

                                @php
                                echo $busLayout->getDeckHeader($loop->index);
                                @endphp

                                @php
                                $totalRow = $busLayout->getTotalRow($seat);
                                $lastRowSeat = $busLayout->getLastRowSit($seat);
                                $chr = 'A';
                                $deckIndex = $loop->index + 1;
                                $seatlayout = $busLayout->sitLayouts();
                                $rowItem = $seatlayout->left + $seatlayout->right;
                                @endphp
                                @for ($i = 1; $i <= $totalRow; $i++) @php if ($lastRowSeat==1 && $i==$totalRow) { break;
                                    } $seatNumber=$chr; $chr++; $seats=$busLayout->getSeats($deckIndex, $seatNumber);
                                    @endphp
                                    <div class="seat-wrapper">
                                        @php echo $seats->left; @endphp
                                        @php echo $seats->right; @endphp
                                    </div>
                                    @endfor
                                    @if ($lastRowSeat == 1)
                                    @php $seatNumber++ @endphp
                                    <div class="seat-wrapper justify-content-center">
                                        @for ($lsr = 1; $lsr <= $rowItem + 1; $lsr++) @php echo $busLayout->
                                            generateSeats($lsr,$deckIndex,$seatNumber); @endphp
                                            @endfor
                                    </div><!-- single-row end -->
                                    @endif

                                    @if ($lastRowSeat > 1)
                                    @php $seatNumber++ @endphp
                                    <div class="seat-wrapper justify-content-center">
                                        @for ($l = 1; $l <= $lastRowSeat; $l++) @php echo $busLayout->
                                            generateSeats($l,$deckIndex,$seatNumber); @endphp
                                            @endfor
                                    </div><!-- single-row end -->
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