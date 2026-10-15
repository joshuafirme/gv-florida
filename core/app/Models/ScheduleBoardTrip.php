<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleBoardTrip extends Model
{
    protected $fillable = ['counter_id', 'trip_id', 'sort_order'];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
