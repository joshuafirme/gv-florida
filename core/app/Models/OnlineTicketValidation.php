<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineTicketValidation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'validated_at' => 'datetime',
        'discount_authorized_at' => 'datetime',
        'original_fare' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_fare' => 'decimal:2',
    ];

    public function slipSeriesNumber()
    {
        return $this->belongsTo(SlipSeriesNumber::class);
    }

    public function bookedTicket()
    {
        return $this->belongsTo(BookedTicket::class);
    }

    public function deposit()
    {
        return $this->belongsTo(Deposit::class);
    }

    public function validator()
    {
        return $this->belongsTo(Admin::class, 'validated_by_admin_id');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function discountAppliedBy()
    {
        return $this->belongsTo(Admin::class, 'discount_applied_by_admin_id');
    }

    public function discountAuthorizedBy()
    {
        return $this->belongsTo(Admin::class, 'discount_authorized_by_admin_id');
    }
}
