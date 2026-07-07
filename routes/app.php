<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\DashboardController;

Route::prefix('app')
    ->name('app.')
    ->middleware(['auth', 'role:Owner|Kasir', 'business.active', 'branch.access'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        Route::get('/business-inactive', function () {
            return view('app.business-inactive');
        })->name('business-inactive')->withoutMiddleware(['business.active']);
    });
