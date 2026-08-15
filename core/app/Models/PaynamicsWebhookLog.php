<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaynamicsWebhookLog extends Model
{
    protected $fillable = [
        'deposit_id',
        'provider',
        'event_type',
        'request_id',
        'original_transaction_id',
        'pay_reference',
        'status',
        'http_status',
        'payload',
        'response',
        'headers',
        'error_message',
        'ip_address',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'headers' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function deposit()
    {
        return $this->belongsTo(Deposit::class);
    }
}
