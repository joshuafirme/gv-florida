<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassengerNotification extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookedTicket()
    {
        return $this->belongsTo(BookedTicket::class);
    }

    public function sentByAdmin()
    {
        return $this->belongsTo(Admin::class, 'sent_by_admin_id');
    }
}
