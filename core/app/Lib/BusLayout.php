<?php
namespace App\Lib;

class BusLayout
{
    protected $trip;
    public $fleet;
    public $sitLayouts;
    protected $totalRow;
    protected $deckNumber;
    protected $seatNumber;

    public function __construct($trip = null, $fleet = null)
    {
        $this->trip = $trip;
        $this->fleet = $trip ? $trip->fleetType : $fleet;
        $this->sitLayouts = $this->sitLayouts();
    }

    public function sitLayouts()
    {
        $seatLayout = explode('x', str_replace(' ', '', $this->fleet->seat_layout));
        $layout['left'] = $seatLayout[0];
        $layout['right'] = $seatLayout[1];
        return (object) $layout;
    }

    public function getDeckHeader($deckNumber)
    {
        $html = '
            <span class="front">Front</span>
            <span class="rear">Rear</span>
        ';
        if ($deckNumber == 0) {
            $html .= '
                <span class="driver"><img src="' . getImage('assets/templates/basic/images/icon/wheel.svg') . '" alt="icon"></span>
                <span class="lower">Door</span>
            ';
        } else {
            $html .= '<span class="driver">Deck :  ' . ($deckNumber + 1) . '</span>';
        }
        return $html;
    }

    public function getSeats($deckNumber, $seatNumber)
    {
        $this->deckNumber = $deckNumber;
        $this->seatNumber = $seatNumber;
        $seats = [
            'left' => $this->leftSeats(),
            'right' => $this->rightSeats(),
        ];
        return (object) $seats;
    }

    protected function leftSeats()
    {
        // Not used anymore – numbering handled in Blade
        return '';
    }

    protected function rightSeats()
    {
        // Not used anymore – numbering handled in Blade
        return '';
    }

    public function generateSeats($loopIndex, $deckNumber = null, $seatNumber = null, $globalLabel = null)
    {
        $deckNumber = $deckNumber ?? $this->deckNumber;
        $seatNumber = $seatNumber ?? $this->seatNumber;

        $label = $globalLabel ?? ($this->seatNumber . $loopIndex);
        $disabled = str_contains($label, '<del>') ? 'disabled-seat' : '';
        
        return "<div>
                    <span class='seat ".$disabled."' data-seat='" . ($deckNumber . '-' . $label) . "'>
                        $label
                        <span></span>
                    </span>
                </div>";
    }

    public function getTotalRow($seat)
    {
        $rowItem = $this->sitLayouts->left + $this->sitLayouts->right;
        $totalRow = floor($seat / $rowItem);
        $this->totalRow = $totalRow;
        return $this->totalRow;
    }

    public function getLastRowSit($seat)
    {
        $rowItem = $this->sitLayouts->left + $this->sitLayouts->right;
        $lastRowSeat = $seat - $this->getTotalRow($seat) * $rowItem;
        return $lastRowSeat;
    }
}

