<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaynamicsPaymentChannel extends Model
{
    protected $fillable = [
        'paynamics_payment_method_id',
        'name',
        'code',
        'icon_url',
        'is_enabled',
        'online_enabled',
        'kiosk_enabled',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'online_enabled' => 'boolean',
        'kiosk_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function paymentMethod()
    {
        return $this->belongsTo(PaynamicsPaymentMethod::class, 'paynamics_payment_method_id');
    }
}
