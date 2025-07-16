<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GlobalStatus;

class VehicleRoute extends Model
{
    use GlobalStatus;
    protected $guarded = ['id'];

    protected $casts = [
        'stoppages' => 'array'
    ];

    public function startFrom(){
        return $this->belongsTo(Counter::class, 'start_from', 'id');
    }


    public function endTo(){
        return $this->belongsTo(Counter::class, 'end_to', 'id');
    }

    //scope
    public function scopeActive($query){
      return  $query->where('status', Status::ENABLE);
    }
}
