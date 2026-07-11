<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketCancellation extends Model
{
    protected $fillable = [
        'booked_ticket_id',
        'slip_series_number_id',
        'processed_by_admin_id',
        'authorized_by_admin_id',
        'original_fare',
        'reason',
        'remarks',
    ];

    protected $casts = [
        'original_fare' => 'decimal:2',
    ];

    public function bookedTicket()
    {
        return $this->belongsTo(BookedTicket::class);
    }

    public function slipSeriesNumber()
    {
        return $this->belongsTo(SlipSeriesNumber::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(Admin::class, 'processed_by_admin_id');
    }

    public function authorizedBy()
    {
        return $this->belongsTo(Admin::class, 'authorized_by_admin_id');
    }
}
