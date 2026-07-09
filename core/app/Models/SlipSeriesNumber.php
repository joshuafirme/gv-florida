<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlipSeriesNumber extends Model
{
    use HasFactory;

    protected $fillable = ['seat', 'booked_ticket_id'];

    public function bookedTicket()
    {
        return $this->belongsTo(BookedTicket::class);
    }

    public function refund()
    {
        return $this->hasOne(TicketRefund::class);
    }
}
