<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\Kasir\CashierShiftController;
use App\Http\Controllers\App\Kasir\SaleController as KasirSaleController;
use App\Http\Controllers\App\Owner\BranchController;
use App\Http\Controllers\App\Owner\SaleController as OwnerSaleController;
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
use App\Http\Controllers\App\Owner\ShiftController;

Route::prefix('app')
    ->name('app.')
    ->middleware(['auth', 'role:Owner|Kasir', 'business.active', 'branch.access'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/business-inactive', function () {
            return view('app.business-inactive');
        })->name('business-inactive')->withoutMiddleware(['business.active']);

        // REGISTER KASIR ROUTES FIRST — before Owner wildcard routes like /kasir/{kasir}
        // so that specific paths (pos, sales, shifts) match before the wildcard.
        Route::middleware(['role:Kasir'])->group(function () {
            Route::prefix('kasir')->name('kasir.')->group(function () {
                Route::prefix('shifts')->name('shifts.')->group(function () {
                    Route::get('/open', [CashierShiftController::class, 'openForm'])->name('open');
                    Route::post('/open', [CashierShiftController::class, 'openStore'])->name('open.store');
                    Route::get('/close', [CashierShiftController::class, 'closeForm'])->name('close');
                    Route::post('/close', [CashierShiftController::class, 'closeStore'])->name('close.store');
                });

                Route::middleware(['shift.open'])->group(function () {
                    Route::get('/pos', [KasirSaleController::class, 'pos'])->name('pos');
                    Route::prefix('sales')->name('sales.')->group(function () {
                        Route::post('/checkout', [KasirSaleController::class, 'checkout'])->name('checkout');
                        Route::get('/receipt/{sale}', [KasirSaleController::class, 'receipt'])->name('receipt');
                        Route::get('/', [KasirSaleController::class, 'history'])->name('index');
                    });
                });
            });
        });

        Route::middleware(['role:Owner'])->group(function () {
            Route::resource('branches', BranchController::class);
            Route::get('branches/{branch}/settings', [BranchSettingController::class, 'edit'])->name('branches.settings.edit');
            Route::put('branches/{branch}/settings', [BranchSettingController::class, 'update'])->name('branches.settings.update');

            Route::resource('kasir', UserController::class);

            Route::resource('raw-materials', RawMaterialController::class);
            Route::resource('products', ProductController::class);
            Route::get('products/{product}/print-barcode', [ProductController::class, 'printBarcodeForm'])->name('products.print-barcode.form');
            Route::get('products/{product}/print-barcode/print', [ProductController::class, 'printBarcode'])->name('products.print-barcode');
            Route::get('products/{product}/recipes-json', [ProductionController::class, 'getRecipes'])->name('products.recipes.json');
            Route::get('products/{product}/recipes', [RecipeController::class, 'index'])->name('products.recipes.index');
            Route::post('products/{product}/recipes', [RecipeController::class, 'store'])->name('products.recipes.store');
            Route::post('products/{product}/recipes/{recipe}/toggle', [RecipeController::class, 'toggle'])->name('products.recipes.toggle');
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

            Route::prefix('sales')->name('owner.sales.')->group(function () {
                Route::get('/', [OwnerSaleController::class, 'index'])->name('index');
                Route::get('{sale}', [OwnerSaleController::class, 'show'])->name('show');
            });

            Route::prefix('shifts')->name('owner.shifts.')->group(function () {
                Route::get('/', [ShiftController::class, 'index'])->name('index');
            });
        });
    });
