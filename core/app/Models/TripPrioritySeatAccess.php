<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripPrioritySeatAccess extends Model
{
    protected $guarded = ['id'];

    protected $attributes = [
        'online_enabled' => false,
        'walk_in_enabled' => true,
    ];

    protected $casts = [
        'journey_date' => 'date',
        'online_enabled' => 'boolean',
        'walk_in_enabled' => 'boolean',
        'authorized_at' => 'datetime',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }

    public function authorizedBy()
    {
        return $this->belongsTo(Admin::class, 'authorized_by_admin_id');
    }
}
