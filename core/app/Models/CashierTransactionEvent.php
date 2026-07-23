<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierTransactionEvent extends Model
{
    public const BOOKING_TRANSACTION_STATUSES = [
        'Sold',
        'Rebooked',
        'Cancelled',
        'Voided',
        'Refunded',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'processed_at' => 'datetime',
        'journey_date' => 'date',
        'base_fare' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'surcharge_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'snapshot' => 'array',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function scopeBookingTransactions($query)
    {
        return $query->whereIn('status', self::BOOKING_TRANSACTION_STATUSES);
    }
}
