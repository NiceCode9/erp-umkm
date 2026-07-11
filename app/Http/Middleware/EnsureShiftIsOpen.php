<?php

namespace App\Http\Middleware;

use App\Models\CashierShift;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShiftIsOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user->hasRole('Kasir')) {
            return $next($request);
        }

        $activeShift = CashierShift::where('user_id', $user->id)
            ->where('business_id', $user->business_id)
            ->whereNull('closed_at')
            ->latest()
            ->first();

        if (!$activeShift) {
            if ($request->routeIs('app.kasir.shifts.*')) {
                return $next($request);
            }
            return redirect()->route('app.kasir.shifts.open');
        }

        if ($request->routeIs('app.kasir.shifts.open')) {
            return redirect()->route('app.kasir.pos');
        }

        $request->merge(['active_shift' => $activeShift]);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
