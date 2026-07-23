<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSeatLock extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'date_of_journey' => 'date',
        'is_active' => 'boolean',
        'locked_at' => 'datetime',
        'unlocked_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function lockedBy()
    {
        return $this->belongsTo(Admin::class, 'locked_by_admin_id');
    }

    public function lockAuthorizedBy()
    {
        return $this->belongsTo(Admin::class, 'lock_authorized_by_admin_id');
    }

    public function unlockedBy()
    {
        return $this->belongsTo(Admin::class, 'unlocked_by_admin_id');
    }

    public function unlockAuthorizedBy()
    {
        return $this->belongsTo(Admin::class, 'unlock_authorized_by_admin_id');
    }
}
