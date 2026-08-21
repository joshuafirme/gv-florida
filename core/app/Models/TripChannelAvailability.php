<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripChannelAvailability extends Model
{
    public const ONLINE = 'online';
    public const KIOSK = 'kiosk';

    protected $guarded = ['id'];

    protected $casts = [
        'journey_date' => 'date',
        'is_enabled' => 'boolean',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(Admin::class, 'performed_by_admin_id');
    }

    public function authorizedBy()
    {
        return $this->belongsTo(Admin::class, 'authorized_by_admin_id');
    }
}
