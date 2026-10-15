<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ScheduleBoardAdvertisement extends Model
{
    protected $fillable = [
        'counter_id',
        'title',
        'image',
        'starts_at',
        'ends_at',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function scopeCurrentlyVisible(Builder $query): Builder
    {
        return $query->where('status', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
