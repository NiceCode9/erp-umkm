<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Superadmin\DashboardController;
use App\Http\Controllers\Superadmin\BusinessController;

Route::prefix('superadmin')
    ->name('superadmin.')
    ->middleware(['auth', 'role:Superadmin'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        Route::resource('businesses', BusinessController::class);
        Route::post('businesses/{business}/activate', [BusinessController::class, 'activate'])->name('businesses.activate');
        Route::post('businesses/{business}/deactivate', [BusinessController::class, 'deactivate'])->name('businesses.deactivate');
    });
