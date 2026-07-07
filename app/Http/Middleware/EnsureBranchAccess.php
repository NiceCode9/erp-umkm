<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !$user->hasRole('Kasir')) {
            return $next($request);
        }

        if (!$user->branch_id) {
            abort(403, 'Kasir harus terikat ke cabang tertentu.');
        }

        return $next($request);
    }
}
