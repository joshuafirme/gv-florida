<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlipSeriesNumber extends Model
{
    use HasFactory;

    protected $fillable = ['seat', 'booked_ticket_id'];
}
