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

    public function uiTone(): string
    {
        return match (true) {
            str_contains($this->event_type, 'refund') => 'warning',
            str_contains($this->event_type, 'cancel'), str_contains($this->event_type, 'denied') => 'danger',
            str_contains($this->event_type, 'rebook'), str_contains($this->event_type, 'schedule') => 'info',
            str_contains($this->event_type, 'validation'), str_contains($this->event_type, 'completed') => 'success',
            default => 'success',
        };
    }

    public function uiIcon(): string
    {
        return match (true) {
            str_contains($this->event_type, 'rebook') => 'la-history',
            str_contains($this->event_type, 'refund') => 'la-wallet',
            str_contains($this->event_type, 'cancel'), str_contains($this->event_type, 'denied') => 'la-times',
            str_contains($this->event_type, 'schedule') => 'la-clock',
            str_contains($this->event_type, 'validation') => 'la-check-double',
            default => 'la-check-circle',
        };
    }
}
