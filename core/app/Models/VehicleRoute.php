<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;
use App\Traits\GlobalStatus;

class VehicleRoute extends Model
{
    use GlobalStatus;
    protected $guarded = ['id'];

    protected $casts = [
        'stoppages' => 'array',
        'route_stops' => 'array',
    ];

    public function stopDefinitions(): array
    {
        $routeStops = collect($this->route_stops ?: [])
            ->map(function ($stop, $index) {
                $counterId = $stop['counter_id'] ?? null;

                if (!$counterId) {
                    return null;
                }

                return [
                    'counter_id' => (string) $counterId,
                    'km_post' => is_numeric($stop['km_post'] ?? null) ? (float) $stop['km_post'] : 0,
                    'sequence' => $index + 1,
                ];
            })
            ->filter()
            ->values();

        if ($routeStops->isNotEmpty()) {
            return $routeStops->all();
        }

        $stoppages = array_values($this->stoppages ?: []);
        $kmPosts = Counter::query()
            ->whereIn('id', array_unique($stoppages))
            ->pluck('km_post', 'id');

        return collect($stoppages)
            ->map(fn ($counterId, $index) => [
                'counter_id' => (string) $counterId,
                'km_post' => is_numeric($kmPosts[$counterId] ?? null) ? (float) $kmPosts[$counterId] : 0,
                'sequence' => $index + 1,
            ])
            ->all();
    }

    public function startFrom(){
        return $this->belongsTo(Counter::class, 'start_from', 'id');
    }


    public function endTo(){
        return $this->belongsTo(Counter::class, 'end_to', 'id');
    }

    //scope
    public function scopeActive($query){
      return  $query->where('status', Status::ENABLE);
    }
}
