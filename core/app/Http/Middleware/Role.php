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
        if (!$request->user('admin')->isPermitted($role)) {
            return redirect("/admin/unauthorize?page=$role");
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
