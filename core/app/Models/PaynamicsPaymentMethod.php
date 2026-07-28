<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaynamicsPaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function channels()
    {
        return $this->hasMany(PaynamicsPaymentChannel::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
