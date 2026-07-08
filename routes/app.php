<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\Owner\BranchController;
use App\Http\Controllers\App\Owner\UserController;
use App\Http\Controllers\App\Owner\RawMaterialController;
use App\Http\Controllers\App\Owner\ProductController;
use App\Http\Controllers\App\Owner\SupplierController;
use App\Http\Controllers\App\Owner\CustomerController;
use App\Http\Controllers\App\Owner\BranchSettingController;
use App\Http\Controllers\App\Owner\PurchaseController;
use App\Http\Controllers\App\Owner\DebtController;
use App\Http\Controllers\App\Owner\StockMovementController;
use App\Http\Controllers\App\Owner\RecipeController;
use App\Http\Controllers\App\Owner\ProductionController;

Route::prefix('app')
    ->name('app.')
    ->middleware(['auth', 'role:Owner|Kasir', 'business.active', 'branch.access'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/business-inactive', function () {
            return view('app.business-inactive');
        })->name('business-inactive')->withoutMiddleware(['business.active']);

        Route::middleware(['role:Owner'])->group(function () {
            Route::resource('branches', BranchController::class);
            Route::get('branches/{branch}/settings', [BranchSettingController::class, 'edit'])->name('branches.settings.edit');
            Route::put('branches/{branch}/settings', [BranchSettingController::class, 'update'])->name('branches.settings.update');

            Route::resource('kasir', UserController::class);

            Route::resource('raw-materials', RawMaterialController::class);
            Route::resource('products', ProductController::class);
            Route::get('products/{product}/recipes', [RecipeController::class, 'index'])->name('products.recipes.index');
            Route::post('products/{product}/recipes', [RecipeController::class, 'store'])->name('products.recipes.store');
            Route::put('products/{product}/recipes/{recipe}', [RecipeController::class, 'update'])->name('products.recipes.update');
            Route::delete('products/{product}/recipes/{recipe}', [RecipeController::class, 'destroy'])->name('products.recipes.destroy');
            Route::resource('suppliers', SupplierController::class);
            Route::resource('customers', CustomerController::class);

            Route::resource('purchases', PurchaseController::class)->except(['edit', 'update', 'destroy']);
            Route::get('purchases/{purchase}/pay', [PurchaseController::class, 'payForm'])->name('purchases.pay');
            Route::post('purchases/{purchase}/pay', [PurchaseController::class, 'payStore'])->name('purchases.pay.store');
            Route::get('purchases/{purchase}/return', [PurchaseController::class, 'returnForm'])->name('purchases.return.form');
            Route::post('purchases/{purchase}/return', [PurchaseController::class, 'returnStore'])->name('purchases.return.store');

            Route::get('debts', [DebtController::class, 'index'])->name('debts.index');

            Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');

            Route::resource('production', ProductionController::class)->only(['index', 'create', 'store', 'show']);
        });
    });
