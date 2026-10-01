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
use App\Http\Controllers\App\Owner\SaleReturnController;
use App\Http\Controllers\App\Owner\ReceivableController;
use App\Http\Controllers\App\Owner\ShipmentController;
use App\Http\Controllers\App\Kasir\ReceivableController as KasirReceivableController;
use App\Http\Controllers\App\Kasir\ShipmentController as KasirShipmentController;

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

                    Route::prefix('receivables')->name('receivables.')->group(function () {
                        Route::get('/', [KasirReceivableController::class, 'index'])->name('index');
                        Route::get('{sale}/pay', [KasirReceivableController::class, 'payForm'])->name('pay');
                        Route::post('{sale}/pay', [KasirReceivableController::class, 'payStore'])->name('pay.store');
                    });

                    Route::prefix('shipments')->name('shipments.')->group(function () {
                        Route::get('/', [KasirShipmentController::class, 'index'])->name('index');
                        Route::get('create-from-sale/{sale}', [KasirShipmentController::class, 'createFromSale'])->name('create-from-sale');
                        Route::post('/', [KasirShipmentController::class, 'store'])->name('store');
                        Route::get('{shipment}', [KasirShipmentController::class, 'show'])->name('show');
                    });
                });
            });
        });

        Route::middleware(['role:Owner'])->group(function () {
            Route::get('branches', [BranchController::class, 'index'])->name('branches.index')->middleware('can:view-branches');

            // Provisioning cabang & akun Kasir HANYA lewat Superadmin di
            // /superadmin/businesses/{business}/... (lihat PERMISSIONS.md, keputusan final).
            // Route di bawah sengaja dipelihara agar akses langsung Owner memberi 403
            // yang jelas, bukan 404 yang membingungkan. Jangan diubah menjadi form create.
            Route::get('branches/create', fn () => abort(403, 'Penambahan cabang hanya dapat dilakukan oleh Superadmin.'))->name('branches.create');
            Route::post('branches', fn () => abort(403, 'Penambahan cabang hanya dapat dilakukan oleh Superadmin.'))->name('branches.store');
            Route::delete('branches/{branch}', fn () => abort(403, 'Penghapusan cabang hanya dapat dilakukan oleh Superadmin.'))->name('branches.destroy');

            Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit')->middleware('can:edit-branches');
            Route::put('branches/{branch}', [BranchController::class, 'update'])->name('branches.update')->middleware('can:edit-branches');
            Route::get('branches/{branch}/settings', [BranchSettingController::class, 'edit'])->name('branches.settings.edit');
            Route::put('branches/{branch}/settings', [BranchSettingController::class, 'update'])->name('branches.settings.update');

            // 'show' sengaja TIDAK didaftarkan: tidak ada halaman detail Kasir
            // dan UserController tidak memiliki method show(). Mendaftarkannya
            // membuat GET /app/kasir/{kasir} berakhir 500.
            Route::resource('kasir', UserController::class)->only(['index', 'edit', 'update']);
            Route::get('kasir/create', fn () => abort(403, 'Pembuatan akun Kasir hanya dapat dilakukan oleh Superadmin.'))->name('kasir.create');
            Route::post('kasir', fn () => abort(403, 'Pembuatan akun Kasir hanya dapat dilakukan oleh Superadmin.'))->name('kasir.store');
            Route::get('kasir/{kasir}/reset-password', [UserController::class, 'resetPasswordForm'])->name('kasir.reset-password.form')->middleware('can:reset-kasir-password');
            Route::post('kasir/{kasir}/reset-password', [UserController::class, 'resetPassword'])->name('kasir.reset-password')->middleware('can:reset-kasir-password');

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

            // Pembelian & utang supplier.
            // Gate per-OBJEK (pay, returnPurchase) memakai PurchasePolicy agar
            // pengecekan business_id ikut jalan. Gate level-FITUR memakai nama
            // permission langsung, karena `can:xyz` tanpa argumen model tidak
            // bisa menemukan policy (Laravel hanya menebak policy dari argumen).
            Route::resource('purchases', PurchaseController::class)->except(['edit', 'update', 'destroy'])
                ->middleware(['can:view-purchases']);
            Route::get('purchases/create', [PurchaseController::class, 'create'])->name('purchases.create')->middleware('can:manage-purchases');
            Route::get('purchases/{purchase}/pay', [PurchaseController::class, 'payForm'])->name('purchases.pay')->middleware('can:pay,purchase');
            Route::post('purchases/{purchase}/pay', [PurchaseController::class, 'payStore'])->name('purchases.pay.store')->middleware('can:pay,purchase');
            Route::get('purchases/{purchase}/return', [PurchaseController::class, 'returnForm'])->name('purchases.return.form')->middleware('can:returnPurchase,purchase');
            Route::post('purchases/{purchase}/return', [PurchaseController::class, 'returnStore'])->name('purchases.return.store')->middleware('can:returnPurchase,purchase');

            Route::get('debts', [DebtController::class, 'index'])->name('debts.index')->middleware('can:manage-supplier-debts');

            Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');

            Route::resource('production', ProductionController::class)->only(['index', 'create', 'store', 'show']);

            Route::prefix('sales')->name('owner.sales.')->group(function () {
                Route::get('/', [OwnerSaleController::class, 'index'])->name('index');
                Route::get('{sale}', [OwnerSaleController::class, 'show'])->name('show');
            });

            Route::prefix('receivables')->name('owner.receivables.')->group(function () {
                Route::get('/', [ReceivableController::class, 'index'])->name('index');
                Route::get('{sale}/pay', [ReceivableController::class, 'payForm'])->name('pay');
                Route::post('{sale}/pay', [ReceivableController::class, 'payStore'])->name('pay.store');
            });

            Route::prefix('sales/{sale}/return')->name('owner.sales.return.')->group(function () {
                Route::get('/', [SaleReturnController::class, 'create'])->name('create');
                Route::post('/', [SaleReturnController::class, 'store'])->name('store');
            });

            Route::get('sale-items-for-shipment/{sale}', [ShipmentController::class, 'saleItems'])->name('sale-items-for-shipment');

            Route::prefix('shipments')->name('owner.shipments.')->group(function () {
                Route::get('/', [ShipmentController::class, 'index'])->name('index');
                Route::get('/create', [ShipmentController::class, 'create'])->name('create');
                Route::post('/', [ShipmentController::class, 'store'])->name('store');
                Route::get('{shipment}', [ShipmentController::class, 'show'])->name('show');
            Route::post('{shipment}/update-status', [ShipmentController::class, 'updateStatus'])->name('update-status');
        });

        Route::prefix('stock-distributions')->name('owner.stock-distributions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\App\Owner\StockDistributionController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\App\Owner\StockDistributionController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\App\Owner\StockDistributionController::class, 'store'])->name('store');
            Route::get('{stock_distribution}', [\App\Http\Controllers\App\Owner\StockDistributionController::class, 'show'])->name('show');
            Route::post('{stock_distribution}/ship', [\App\Http\Controllers\App\Owner\StockDistributionController::class, 'ship'])->name('ship');
            Route::post('{stock_distribution}/receive', [\App\Http\Controllers\App\Owner\StockDistributionController::class, 'receive'])->name('receive');
        });

        Route::prefix('stock-opnames')->name('stock-opnames.')->group(function () {
            Route::get('/', [\App\Http\Controllers\App\Owner\StockOpnameController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\App\Owner\StockOpnameController::class, 'create'])->name('create');
            Route::post('/session', [\App\Http\Controllers\App\Owner\StockOpnameController::class, 'storeSession'])->name('session.store');
            Route::get('/worksheet/{session}', [\App\Http\Controllers\App\Owner\StockOpnameController::class, 'worksheet'])->name('worksheet');
            Route::post('/worksheet/{session}/save', [\App\Http\Controllers\App\Owner\StockOpnameController::class, 'saveWorksheet'])->name('worksheet.save');
            Route::post('/worksheet/{session}/confirm', [\App\Http\Controllers\App\Owner\StockOpnameController::class, 'confirm'])->name('confirm');
        });

        Route::prefix('reports')->name('owner.reports.')->group(function () {
            Route::get('sales', [\App\Http\Controllers\App\Owner\ReportController::class, 'sales'])->name('sales');
            Route::get('shifts', [\App\Http\Controllers\App\Owner\ReportController::class, 'shifts'])->name('shifts');
            Route::get('debts', [\App\Http\Controllers\App\Owner\ReportController::class, 'debts'])->name('debts');
            Route::get('stock', [\App\Http\Controllers\App\Owner\ReportController::class, 'stock'])->name('stock');
            Route::get('production', [\App\Http\Controllers\App\Owner\ReportController::class, 'production'])->name('production');
            Route::get('{type}/export/{format}', [\App\Http\Controllers\App\Owner\ReportController::class, 'export'])->name('export');
        });

        }); // end role:Owner

    }); // end app prefix
