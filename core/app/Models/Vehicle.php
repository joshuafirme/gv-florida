<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GlobalStatus;

class Vehicle extends Model
{
    use GlobalStatus;
    protected $guarded = ['id'];

    public function fleetType()
    {
        return $this->belongsTo(FleetType::class);
    }
}
