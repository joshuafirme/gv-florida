<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GlobalStatus;
use Carbon\Carbon;

class Trip extends Model
{
    use GlobalStatus;

    protected $guarded = ['id'];

    protected $casts = [
        'day_off' => 'array',
        'online_booking_enabled' => 'boolean',
        'kiosk_booking_enabled' => 'boolean',
    ];

    public function ticketPrice()
    {
        return $this->hasOne(TicketPrice::class, 'fleet_type_id', 'fleet_type_id')
            ->where('vehicle_route_id', $this->vehicle_route_id);
    }

    public function fleetType()
    {
        return $this->belongsTo(FleetType::class);
    }

    public function route()
    {
        return $this->belongsTo(VehicleRoute::class, 'vehicle_route_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function startFrom()
    {
        return $this->belongsTo(Counter::class, 'start_from', 'id');
    }

    public function endTo()
    {
        return $this->belongsTo(Counter::class, 'end_to', 'id');
    }

    public function assignedVehicle()
    {
        return $this->hasOne(AssignedVehicle::class);
    }

    public function bookedTickets()
    {
        return $this->hasMany(BookedTicket::class)->whereIn('status', [1, 2]);
    }

    public function adminSeatLocks()
    {
        return $this->hasMany(AdminSeatLock::class);
    }

    public function channelAvailabilities()
    {
        return $this->hasMany(TripChannelAvailability::class);
    }

    //scope
    public function scopeActive($query)
    {
        return $query->where('status', Status::ENABLE);
    }

    public function scopeForBookingChannel($query, $kioskId = null, $journeyDate = null)
    {
        $channel = $kioskId ? TripChannelAvailability::KIOSK : TripChannelAvailability::ONLINE;
        $defaultColumn = $kioskId ? 'kiosk_booking_enabled' : 'online_booking_enabled';

        if (!$journeyDate) {
            return $query->where($defaultColumn, true);
        }

        $date = Carbon::parse($journeyDate)->format('Y-m-d');

        return $query->where(function ($availabilityQuery) use ($channel, $defaultColumn, $date) {
            $availabilityQuery->whereHas('channelAvailabilities', function ($override) use ($channel, $date) {
                $override->where('channel', $channel)
                    ->whereDate('journey_date', $date)
                    ->where('is_enabled', true);
            })->orWhere(function ($defaultQuery) use ($channel, $defaultColumn, $date) {
                $defaultQuery->where($defaultColumn, true)
                    ->whereDoesntHave('channelAvailabilities', function ($override) use ($channel, $date) {
                        $override->where('channel', $channel)
                            ->whereDate('journey_date', $date);
                    });
            });
        });
    }

    public function bookingEnabledFor($kioskId = null, $journeyDate = null): bool
    {
        $default = $kioskId
            ? (bool) $this->kiosk_booking_enabled
            : (bool) $this->online_booking_enabled;

        if (!$journeyDate || !$this->exists) {
            return $default;
        }

        $channel = $kioskId ? TripChannelAvailability::KIOSK : TripChannelAvailability::ONLINE;
        $date = Carbon::parse($journeyDate)->format('Y-m-d');
        $override = $this->channelAvailabilities()
            ->where('channel', $channel)
            ->whereDate('journey_date', $date)
            ->first();

        return $override ? (bool) $override->is_enabled : $default;
    }
}
