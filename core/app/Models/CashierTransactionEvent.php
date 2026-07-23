<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierTransactionEvent extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'processed_at' => 'datetime',
        'journey_date' => 'date',
        'base_fare' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'snapshot' => 'array',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
