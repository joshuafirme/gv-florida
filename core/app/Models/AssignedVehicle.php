<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Traits\GlobalStatus;

class AssignedVehicle extends Model
{

    use GlobalStatus;
    
    protected $guarded = ['id'];

    public function trip(){
        return $this->belongsTo(Trip::class);
    }

    public function vehicle(){
        return $this->belongsTo(Vehicle::class);
    }
}
