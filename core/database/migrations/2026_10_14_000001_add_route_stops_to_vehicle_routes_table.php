<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_routes', function (Blueprint $table) {
            $table->json('route_stops')->nullable()->after('stoppages');
        });

        $kmPosts = DB::table('counters')->pluck('km_post', 'id');

        DB::table('vehicle_routes')
            ->select(['id', 'stoppages'])
            ->orderBy('id')
            ->chunkById(100, function ($routes) use ($kmPosts) {
                foreach ($routes as $route) {
                    $stoppages = json_decode((string) $route->stoppages, true);

                    if (!is_array($stoppages)) {
                        continue;
                    }

                    $routeStops = collect(array_values($stoppages))
                        ->map(fn ($counterId, $index) => [
                            'counter_id' => (string) $counterId,
                            'km_post' => is_numeric($kmPosts[$counterId] ?? null)
                                ? (float) $kmPosts[$counterId]
                                : 0,
                            'sequence' => $index + 1,
                        ])
                        ->all();

                    DB::table('vehicle_routes')
                        ->where('id', $route->id)
                        ->update(['route_stops' => json_encode($routeStops)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('vehicle_routes', function (Blueprint $table) {
            $table->dropColumn('route_stops');
        });
    }
};
