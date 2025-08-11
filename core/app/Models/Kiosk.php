<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kiosk extends Model
{
    use HasFactory, GlobalStatus;

    protected $fillable = [
        'uid',
        'name',
        'counter_id',
        'status'
    ];

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }
}
