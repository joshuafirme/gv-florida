<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketPriceByStoppage extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'source_destination' => 'array'
    ];
}
