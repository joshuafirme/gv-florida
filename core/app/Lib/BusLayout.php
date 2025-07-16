<?php
namespace App\Lib;

class BusLayout
{
    protected $trip;
    protected $fleet;
    public $sitLayouts;
    protected $totalRow;
    protected $deckNumber;
    protected $seatNumber;

    public function __construct($trip)
    {
        $this->trip = $trip;
        $this->fleet = $trip->fleetType;
        $this->sitLayouts = $this->sitLayouts();
    }

    public function sitLayouts(){
        $seatLayout = explode('x', str_replace(' ','', $this->fleet->seat_layout));
        $layout['left'] = $seatLayout[0];
        $layout['right'] = $seatLayout[1];
        return (object)$layout;
    }


    public function getDeckHeader($deckNumber){
        $html = '
            <span class="front">Front</span>
            <span class="rear">Rear</span>
        ';
        if ($deckNumber == 0){
            $html .= '
                <span class="lower">Door</span>
                <span class="driver"><img src="'.getImage('assets/templates/basic/images/icon/wheel.svg').'" alt="icon"></span>
            ';
        }else{
            $html .= '<span class="driver">Deck :  '.($deckNumber+1) .'</span>';
        }
        return $html;
    }

    public function getSeats($deckNumber,$seatNumber){
        $this->deckNumber = $deckNumber;
        $this->seatNumber = $seatNumber;
        $seats = [
            'left'=>$this->leftSeats(),
            'right'=>$this->rightSeats(),
        ];
        return (object)$seats;
    }

    protected function leftSeats(){
        $html = '<div class="left-side">';
        $seatData = '';
        for ($i = 1; $i <= $this->sitLayouts->left; $i++){
            $seatData .= $this->generateSeats($i);
        }

        $html .= $seatData;
        $html .=  '</div>';
        return $html;
    }

    protected function rightSeats(){
        $html = '<div class="right-side">';

        $seatData = '';
        for ($i = 1; $i <= $this->sitLayouts->right; $i++){
            $seatData .= $this->generateSeats($i + $this->sitLayouts->left);
        }

        $html .= $seatData;
        $html .=  '</div>';
        return $html;
    }

    public function generateSeats($loopIndex, $deckNumber = null,$seatNumber = null){
        $deckNumber = $deckNumber ?? $this->deckNumber;
        $seatNumber = $seatNumber ?? $this->seatNumber;
        return "<div>
                    <span class='seat' data-seat='".($deckNumber .'-'. $seatNumber.''.$loopIndex) ."'>
                        $this->seatNumber$loopIndex
                        <span></span>
                    </span>
                </div>";
    }

    public function getTotalRow($seat){
        $rowItem    = $this->sitLayouts->left + $this->sitLayouts->right;
        $totalRow   = floor ($seat / $rowItem);
        $this->totalRow = $totalRow;
        return $this->totalRow;
    }

    public function getLastRowSit($seat){
        $rowItem = $this->sitLayouts->left + $this->sitLayouts->right;
        $lastRowSeat = $seat - $this->getTotalRow($seat) * $rowItem;
        return $lastRowSeat;
    }

}
