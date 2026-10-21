<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GlobalStatus;

class Counter extends Model
{
    use GlobalStatus;

    protected $guarded = ['id'];

    public function scopeRouteStoppages($query, $array)
    {
        $ids = array_values(array_map('strval', (array) $array));
        $counters = $query->whereIn('id', array_unique($ids))
            ->active()
            ->get()
            ->keyBy(fn ($counter) => (string) $counter->id);

        return collect($ids)
            ->map(fn ($id) => $counters->get($id))
            ->filter()
            ->values();
    }

    public function scopeActive($query)
    {
        return $query->where('status', Status::ENABLE);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class, 'start_from');
    }
}
