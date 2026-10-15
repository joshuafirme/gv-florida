<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleBoardSetting extends Model
{
    protected $fillable = [
        'counter_id',
        'show_all_trips',
        'departure_grace_minutes',
    ];

    protected $casts = [
        'show_all_trips' => 'boolean',
        'departure_grace_minutes' => 'integer',
    ];

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }
}
