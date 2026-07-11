<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'deposit_id',
        'percentage',
        'amount',
        'description',
        'passenger_manifest',
        'authorization_method',
        'authorized_by_admin_id',
        'authorized_by_name',
        'authorization_reference',
        'authorized_at',
    ];

    protected $casts = [
        'passenger_manifest' => 'array',
        'authorized_at' => 'datetime',
    ];
}
