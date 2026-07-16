<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Superadmin\DashboardController;
use App\Http\Controllers\Superadmin\BusinessController;

Route::prefix('superadmin')
    ->name('superadmin.')
    ->middleware(['auth', 'role:Superadmin'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        Route::resource('businesses', BusinessController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);
        Route::post('businesses/{business}/activate', [BusinessController::class, 'activate'])->name('businesses.activate');
        Route::post('businesses/{business}/deactivate', [BusinessController::class, 'deactivate'])->name('businesses.deactivate');

        Route::prefix('businesses/{business}')->name('businesses.')->group(function () {
            // Branches
            Route::get('branches/create', [BusinessController::class, 'createBranch'])->name('branches.create');
            Route::post('branches', [BusinessController::class, 'storeBranch'])->name('branches.store');

            // Owners
            Route::get('owners/create', [BusinessController::class, 'createOwner'])->name('owners.create');
            Route::post('owners', [BusinessController::class, 'storeOwner'])->name('owners.store');

            // Kasir
            Route::get('kasir/create', [BusinessController::class, 'createKasir'])->name('kasir.create');
            Route::post('kasir', [BusinessController::class, 'storeKasir'])->name('kasir.store');
        });
    });
