<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TransferSeatHold extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'date_of_journey' => 'date',
        'expires_at' => 'datetime',
        'released_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function scopeActive(Builder $query, ?CarbonInterface $at = null): Builder
    {
        $currentTime = $at ?: now();

        return $query
            ->whereNull('released_at')
            ->whereNull('consumed_at')
            ->where('expires_at', '>', $currentTime);
    }

    public function slipSeriesNumber()
    {
        return $this->belongsTo(SlipSeriesNumber::class);
    }

    public function bookedTicket()
    {
        return $this->belongsTo(BookedTicket::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function heldBy()
    {
        return $this->belongsTo(Admin::class, 'held_by_admin_id');
    }
}
