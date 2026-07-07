<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        
        if ($user->hasRole('Owner')) {
            return view('app.dashboard-owner');
        }
        
        return view('app.dashboard-kasir');
    }
}
