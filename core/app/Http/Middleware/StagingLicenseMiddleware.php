<?php

namespace App\Http\Middleware;

use Closure;
use Laramin\Utility\Utility;
use Laramin\Utility\VugiChugi;

class StagingLicenseMiddleware extends Utility
{
    public function handle($request, Closure $next)
    {
        if (!app()->environment('local')) {
            return parent::handle($request, $next);
        }

        $activationPaths = [VugiChugi::acRouter(), VugiChugi::acRouterSbm()];

        if (in_array(trim($request->path(), '/'), $activationPaths, true)) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
