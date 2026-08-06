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

    public function bookedTicket()
    {
        return $this->belongsTo(BookedTicket::class);
    }

    public function slipSeriesNumber()
    {
        return $this->belongsTo(SlipSeriesNumber::class);
    }

    public function scopeBookingTransactions($query)
    {
        return $query->whereIn('status', self::BOOKING_TRANSACTION_STATUSES);
    }

    public function getProcessedByLabelAttribute(): string
    {
        if ($this->admin?->name) {
            return $this->admin->name;
        }

        if ($this->admin?->username) {
            return $this->admin->username;
        }

        $snapshot = $this->snapshot ?: [];

        if (!empty($snapshot['processed_by'])) {
            return (string) $snapshot['processed_by'];
        }

        return match ($this->source) {
            'Kiosk' => 'Kiosk',
            'Online' => 'Online',
            default => 'Counter',
        };
    }
}
