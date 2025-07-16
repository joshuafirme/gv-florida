<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Traits\GlobalStatus;

class Schedule extends Model
{
    use GlobalStatus;
    protected $guarded = ['id'];
}
