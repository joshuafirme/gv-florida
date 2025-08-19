<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GlobalStatus;

class FleetType extends Model
{
    use GlobalStatus;

    protected $guarded = ['id'];

    protected $casts = [
        'deck_seats' => 'object',
        'facilities' => 'array',
        'prefixes' => 'object',
        'disabled_seats' => 'object',
        'cr_seat_range' => 'object',
        'last_row' => 'object'
    ];

    public function vehicles(){
        return $this->hasMany(Vehicle::class);
    }

    public function activeVehicles(){
        return $this->hasMany(Vehicle::class)->where('status', Status::ENABLE);
    }

    //scope active
    public function scopeActive($query){
        return $query->where('status', Status::ENABLE);
    }
}
