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

    /**
     * UPDATED: sitLayouts Method
     * This method now parses seat layouts for both 2-column (e.g., "2x2")
     * and 3-column (e.g., "2x2x2") configurations.
     */
    public function sitLayouts()
    {
        $seatLayout = explode('x', str_replace(' ', '', $this->fleet->seat_layout));
        $layout['left'] = $seatLayout[0] ?? 0;
        $layout['center'] = 0; // Default center to 0
        $layout['right'] = 0;  // Default right to 0

        if (count($seatLayout) == 2) {
            // Handles 2-column layout (e.g., "2x2")
            $layout['right'] = $seatLayout[1] ?? 0;
        } elseif (count($seatLayout) == 3) {
            // Handles 3-column layout (e.g., "2x1x2")
            $layout['center'] = $seatLayout[1] ?? 0;
            $layout['right'] = $seatLayout[2] ?? 0;
        }

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
            'center' => $this->centerSeats(), // Added for consistency, though not used in current logic
            'right' => $this->rightSeats(),
        ];
        return (object) $seats;
    }

    protected function leftSeats()
    {
        // Not used anymore – numbering handled in Blade
        return '';
    }

    // Added for structural completeness
    protected function centerSeats()
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
                    <span class='seat " . $disabled . "' data-seat='" . ($deckNumber . '-' . $label) . "'>
                        $label
                        <span></span>
                    </span>
                </div>";
    }

    /**
     * UPDATED: getTotalRow Method
     * The row item calculation now includes the center seats to correctly
     * determine the total number of rows.
     */
    public function getTotalRow($seat)
    {
        $rowItem = $this->sitLayouts->left + $this->sitLayouts->center + $this->sitLayouts->right;
        if ($rowItem == 0) {
            return 0; // Prevent division by zero error
        }
        $totalRow = floor($seat / $rowItem);
        $this->totalRow = $totalRow;
        return $this->totalRow;
    }

    /**
     * UPDATED: getLastRowSit Method
     * The row item calculation now includes the center seats to correctly
     * determine the number of seats in the final row.
     */
    public function getLastRowSit($seat)
    {
        $rowItem = $this->sitLayouts->left + $this->sitLayouts->center + $this->sitLayouts->right;
        if ($rowItem == 0) {
            return $seat; // Prevent issues if layout is invalid
        }
        $lastRowSeat = $seat - $this->getTotalRow($seat) * $rowItem;
        return $lastRowSeat;
    }
}
