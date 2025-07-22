<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Role
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
    if (!$request->auth('admin')->user()->isPermitted($role)) {
            if ($this->isView($role)) {
                return redirect("/unauthorize?page=$role");
            }
            return redirect()->back()->with("danger", "You are not authorize to do <b>" . $role . "</b> function.");
        }

        return $next($request);
    }

    public function isView($role)
    {
        $strngs = explode('_', $role);
        foreach ($strngs as $s) {
            if ($s == 'view') {
                return true;
            }
        }
        return false;
    }
}
