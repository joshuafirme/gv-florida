<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;

class BookedTicket extends Model
{

    protected $casts = [
        'source_destination' => 'array',
        'seats' => 'array'
    ];

    protected $appends = ['photo'];

    public function getPhotoAttribute()
    {
        return $this->where('status', Status::DISABLE);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
    public function pickup()
    {
        return $this->belongsTo(Counter::class, 'pickup_point');
    }
    public function drop()
    {
        return $this->belongsTo(Counter::class, 'dropping_point');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //scope
    public function scopePending($query)
    {
        $query->where('status', Status::BOOKED_PENDING);
    }

    public function scopeBooked($query)
    {
        $query->where('status', Status::BOOKED_APPROVED);
    }

    public function scopeRejected($query)
    {
        $query->where('status', Status::BOOKED_REJECTED);
    }
}
