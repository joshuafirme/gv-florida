<?php

namespace App\Models;

use App\Constants\Status;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BookedTicket extends Model
{

    protected $casts = [
        'source_destination' => 'array',
        'seats' => 'array',
        'passenger_manifest' => 'array',
    ];

    protected $appends = ['photo'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $last = self::max('series_number') ?? 99999;
            $model->series_number = $last + 1;
        });

        static::saved(function ($model) {
            $model->broadcastScheduleBoardUpdate();
        });

        static::deleted(function ($model) {
            $model->broadcastScheduleBoardUpdate();
        });
    }

    public function broadcastScheduleBoardUpdate(): void
    {
        $dateOfJourney = null;

        if ($this->date_of_journey) {
            try {
                $dateOfJourney = Carbon::parse($this->date_of_journey)->format('Y-m-d');
            } catch (\Throwable $exception) {
                $dateOfJourney = null;
            }
        }

        $callback = function () use ($dateOfJourney) {
            app(\App\Services\ScheduleBoardBroadcaster::class)->passengerTransaction([
                'ticket_id' => $this->id,
                'trip_id' => $this->trip_id,
                'counter_id' => $this->pickup_point,
                'date_of_journey' => $dateOfJourney,
                'status' => $this->status,
                'seat_count' => count($this->seats ?? []),
            ]);
        };

        try {
            if (\DB::transactionLevel() > 0) {
                \DB::afterCommit($callback);
                return;
            }

            $callback();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Check for conflicting tickets that overlap with this ticket's seats.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getConflicts()
    {
        return self::where('trip_id', $this->trip_id)
            ->where('id', '!=', $this->id)
            ->whereDate('date_of_journey', Carbon::parse($this->date_of_journey)->format('Y-m-d'))
            ->where(function ($query) {
                $query->where('status', Status::BOOKED_APPROVED)
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('status', Status::BOOKED_PENDING)
                            ->whereHas('deposit', function ($depositQuery) {
                                $depositQuery->where('created_at', '>=', Carbon::now()->subMinutes(15));
                            });
                    });
            })
            ->where('pickup_point', $this->pickup_point)
            ->where('dropping_point', $this->dropping_point)
            ->where(function ($query) {
                $seats = is_string($this->seats) ? json_decode($this->seats, true) : $this->seats;

                if (!empty($seats) && is_array($seats)) {
                    foreach ($seats as $seat) {
                        $query->orWhereJsonContains('seats', $seat);
                    }
                }
            })
            ->get();
    }

    public function deposit()
    {
        return $this->hasOne(Deposit::class);
    }

    public function slipSeriesNumbers()
    {
        return $this->hasMany(SlipSeriesNumber::class, 'booked_ticket_id');
    }

    public function activeSlipSeriesNumbers()
    {
        return $this->hasMany(SlipSeriesNumber::class, 'booked_ticket_id')
            ->whereDoesntHave('refund')
            ->whereDoesntHave('cancellation');
    }

    public function refunds()
    {
        return $this->hasMany(TicketRefund::class);
    }

    public function cancellations()
    {
        return $this->hasMany(TicketCancellation::class);
    }

    public function approvedBy()
    {
        return $this->hasOne(Admin::class, 'id', 'approved_by');
    }

    public function kiosk()
    {
        return $this->hasOne(Kiosk::class, 'id', 'kiosk_id');
    }

    public function getPhotoAttribute()
    {
        return $this->where('status', Status::DISABLE);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
    public function pickup()
    {
        return $this->belongsTo(Counter::class, 'pickup_point');
    }
    public function drop()
    {
        return $this->belongsTo(Counter::class, 'dropping_point');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //scope
    public function scopePending($query)
    {
        $query->where('status', Status::BOOKED_PENDING);
    }

    public function scopeBooked($query)
    {
        $query->where('status', Status::BOOKED_APPROVED);
    }

    public function scopeRejected($query)
    {
        $query->where('status', Status::BOOKED_REJECTED);
    }

    public function scopeRefunded($query)
    {
        $query->where('status', Status::BOOKED_REFUNDED);
    }

    public function scopeCancelled($query)
    {
        $query->where('status', Status::BOOKED_CANCELLED);
    }
}
