<?php

namespace App\Models;

use App\Services\TransactionReasonFormatter;
use App\Support\BusinessTime;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CashierTransactionEvent extends Model
{
    public const STATUS_REBOOKED = 'Rebooked';
    public const STATUS_SEAT_CHANGED = 'Seat Changed';

    public const BOOKING_TRANSACTION_STATUSES = [
        'Sold',
        self::STATUS_REBOOKED,
        self::STATUS_SEAT_CHANGED,
        'Upgraded',
        'Downgraded',
        'Transferred',
        'Management Transferred',
        'Cancelled',
        'Voided',
        'Refunded',
        'Discount Override',
        'Validated',
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

    public function scopeReportableForDate(
        Builder $query,
        CarbonInterface|string $date
    ): Builder {
        $businessDate = BusinessTime::parse($date)->startOfDay();

        return $query
            ->whereBetween($this->qualifyColumn('processed_at'), [
                $businessDate,
                $businessDate->copy()->endOfDay(),
            ])
            ->whereDate(
                $this->qualifyColumn('journey_date'),
                '>=',
                $businessDate->toDateString()
            );
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

    public function getReportReasonAttribute(): string
    {
        return app(TransactionReasonFormatter::class)->format($this);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'Discount Override'
            ? 'Discount Applied'
            : (string) $this->status;
    }

    public function getKmPostLabelAttribute(): ?string
    {
        $kmPost = trim((string) $this->km_post);

        if ($kmPost === '') {
            return null;
        }

        return str_starts_with(strtoupper($kmPost), 'KM ')
            ? $kmPost
            : 'KM ' . $kmPost;
    }

    public function hasReportPaymentMovement(): bool
    {
        if (abs((float) $this->amount) >= 0.005) {
            return true;
        }

        return !in_array($this->status, [self::STATUS_REBOOKED, self::STATUS_SEAT_CHANGED, 'Validated'], true);
    }
}
