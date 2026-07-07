<?php

namespace App\Http\Controllers\Superadmin;

use App\Models\Business;
use Illuminate\View\View;

class DashboardController
{
    public function index(): View
    {
        $businesses = Business::all();
        
        return view('superadmin.dashboard', [
            'businesses' => $businesses,
            'total_businesses' => $businesses->count(),
            'active_businesses' => $businesses->where('is_active', true)->count(),
            'inactive_businesses' => $businesses->where('is_active', false)->count(),
        ]);
    }
}
