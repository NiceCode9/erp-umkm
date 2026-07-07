<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureBusinessIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return $next($request);
        }

        if ($user->hasRole('Superadmin')) {
            return $next($request);
        }

        if (!$user->business || !$user->business->is_active) {
            return redirect()->route('app.business-inactive');
        }

        return $next($request);
    }
}
