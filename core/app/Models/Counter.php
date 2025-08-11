<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GlobalStatus;

class Counter extends Model
{
    use GlobalStatus;

    protected $guarded = ['id'];

    public function scopeRouteStoppages($query, $array)
    {
        return $query->whereIn('id', $array)
            ->orderByRaw("field(id," . implode(',', $array) . ")")->get();
    }

    public function trips()
    {
        return $this->hasMany(Trip::class, 'start_from');
    }
}
