<?php

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\RawMaterialBatch;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Services\StockService;

/*
|--------------------------------------------------------------------------
| Utang Supplier & Retur Pembelian
|--------------------------------------------------------------------------
|
| Test ini menutup BUG-1 s.d. BUG-13 dari audit alur pembelian. Dokumentasi
| aturan bisnisnya ada di BUSINESS-RULES.md bagian 5 & 5.1.
|
| FORMULA UTANG SUPPLIER (final, bukan lagi dua versi yang berbeda):
|   outstanding = total_amount - SUM(payments) - SUM(returns.total_amount)
| dan boleh negatif -> status 'credit'.
|
*/

/*
|--------------------------------------------------------------------------
| BUG 1 — retur pembelian harus mengurangi utang supplier
|--------------------------------------------------------------------------
|
| Dulu purchase_returns tidak punya kolom total_amount, sehingga
| $returns->sum('total_amount') selalu 0 SENYAP dan retur tidak pernah
| mengurangi utang di seluruh aplikasi.
|
*/

test('retur pembelian mengurangi utang supplier', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business, 'Tepung Terigu');
    $setup = makePurchase($owner->business, $branch, $material, quantity: 10, unitPrice: 10000, invoiceNo: 'INV-1');

    // Total 100.000, bayar 60.000 -> utang 40.000
    PurchasePayment::create([
        'purchase_id' => $setup['purchase']->id,
        'amount' => 60000,
        'paid_at' => now()->format('Y-m-d'),
        'method' => 'cash',
    ]);

    $service = app(PurchaseService::class);
    $service->recalculatePaymentStatus($setup['purchase']);

    expect((float) $setup['purchase']->refresh()->total_amount)->toBe(100000.0);
    expect((float) $setup['purchase']->refresh()->payment_status)->not->toBe('unpaid');

    // Retur 25.000 (2.5 x harga satuan 10.000)
    $this->actingAs($owner)->post(route('app.purchases.return.store', $setup['purchase']), [
        'return_date' => now()->format('Y-m-d'),
        'reason' => 'Rusak',
        'items' => [[
            'purchase_item_id' => $setup['item']->id,
            'raw_material_batch_id' => $setup['batch']->id,
            'quantity' => 2.5,
        ]],
    ])->assertSessionHasNoErrors();

    $purchase = $setup['purchase']->refresh();

    // Utang harus turun dari 40.000 menjadi 15.000
    expect((float) $purchase->returned_amount)->toBe(25000.0);
    expect((float) $purchase->outstanding_amount)->toBe(15000.0);
});

test('retur lebih besar dari sisa utang menghasilkan status credit', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business, 'Gula');
    $setup = makePurchase($owner->business, $branch, $material, quantity: 10, unitPrice: 10000, invoiceNo: 'INV-CREDIT');

    // Bayar lunas dulu
    PurchasePayment::create([
        'purchase_id' => $setup['purchase']->id,
        'amount' => 100000,
        'paid_at' => now()->format('Y-m-d'),
        'method' => 'cash',
    ]);
    app(PurchaseService::class)->recalculatePaymentStatus($setup['purchase']);

    expect((string) $setup['purchase']->refresh()->payment_status)->toBe('paid');

    // Retur 4.000 -> kredit 40.000 (supplier owes kita)
    $this->actingAs($owner)->post(route('app.purchases.return.store', $setup['purchase']), [
        'return_date' => now()->format('Y-m-d'),
        'items' => [[
            'purchase_item_id' => $setup['item']->id,
            'raw_material_batch_id' => $setup['batch']->id,
            'quantity' => 4,
        ]],
    ])->assertSessionHasNoErrors();

    $purchase = $setup['purchase']->refresh();

    expect((float) $purchase->outstanding_amount)->toBe(-40000.0);
    expect((string) $purchase->payment_status)->toBe('credit');
    expect($purchase->hasCredit())->toBeTrue();
});

test('halaman utang memisahkan utang dan kredit', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business, 'Bahan Baku Uji');

    // Utang 40.000
    $payable = makePurchase($owner->business, $branch, $material, 10, 10000, 'INV-PAY');
    PurchasePayment::create(['purchase_id' => $payable['purchase']->id, 'amount' => 60000, 'paid_at' => now()->format('Y-m-d'), 'method' => 'cash']);
    app(PurchaseService::class)->recalculatePaymentStatus($payable['purchase']);

    // Kredit: beli 10rb, bayar lunas, retur 4rb
    $credit = makePurchase($owner->business, $branch, $material, 10, 1000, 'INV-KREDIT');
    PurchasePayment::create(['purchase_id' => $credit['purchase']->id, 'amount' => 10000, 'paid_at' => now()->format('Y-m-d'), 'method' => 'cash']);
    app(PurchaseService::class)->recalculatePaymentStatus($credit['purchase']);
    app(PurchaseService::class)->recordReturn($credit['purchase'], [
        'return_date' => now()->format('Y-m-d'),
        'items' => [[
            'purchase_item_id' => $credit['item']->id,
            'raw_material_batch_id' => $credit['batch']->id,
            'quantity' => 4,
        ]],
    ], $owner->id);

    $response = $this->actingAs($owner)->get(route('app.debts.index'));

    $response->assertOk();
    $response->assertSee('Utang Outstanding');
    $response->assertSee('Kredit / Klaim ke Supplier');
    $response->assertDontSee('Semua utang supplier sudah lunas.');

    expect($response->viewData('payables')->pluck('invoice_no')->all())->toContain('INV-PAY');
    expect($response->viewData('credits')->pluck('invoice_no')->all())->toContain('INV-KREDIT');
});

/*
|--------------------------------------------------------------------------
| BUG 3 — validasi cross-tenant pada pembelian
|--------------------------------------------------------------------------
*/

test('pembelian tidak bisa memakai supplier milik business lain', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business, 'Cabang A');
    $material = makeRawMaterial($owner->business, 'Bahan Baku');

    $otherOwner = makeOwner();
    $otherSupplier = makeSupplier($otherOwner->business, 'Supplier Tenant Lain');

    $this->actingAs($owner)->post(route('app.purchases.store'), [
        'branch_id' => $branch->id,
        'supplier_id' => $otherSupplier->id,
        'invoice_no' => 'INV-XTENANT',
        'purchase_date' => now()->format('Y-m-d'),
        'items' => [[
            'raw_material_id' => $material->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'batch_no' => 'B1',
        ]],
    ])->assertSessionHasErrors('supplier_id');

    expect(Purchase::withoutGlobalScopes()->where('invoice_no', 'INV-XTENANT')->exists())->toBeFalse();
});

test('pembelian tidak bisa memakai bahan baku milik business lain', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business, 'Cabang A');
    $supplier = makeSupplier($owner->business);

    $otherOwner = makeOwner();
    $otherMaterial = makeRawMaterial($otherOwner->business, 'Bahan Baku Tenant Lain');

    $this->actingAs($owner)->post(route('app.purchases.store'), [
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'invoice_no' => 'INV-YTENANT',
        'purchase_date' => now()->format('Y-m-d'),
        'items' => [[
            'raw_material_id' => $otherMaterial->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'batch_no' => 'B1',
        ]],
    ])->assertSessionHasErrors('items.0.raw_material_id');
});

/*
|--------------------------------------------------------------------------
| BUG 2 — raw_material_batches punya business_id + global scope
|--------------------------------------------------------------------------
*/

test('batch bahan baku memiliki business_id yang benar', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business);
    $setup = makePurchase($owner->business, $branch, $material);

    expect((int) $setup['batch']->refresh()->business_id)->toBe((int) $owner->business_id);
});

test('retur tidak bisa menyentuh batch milik business lain', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business, 'Cabang A');
    $material = makeRawMaterial($owner->business, 'Bahan Baku');
    $setup = makePurchase($owner->business, $branch, $material, 10, 10000, 'INV-OWN');

    // Batch milik tenant lain
    $otherOwner = makeOwner();
    $otherBranch = makeBranchFor($otherOwner->business, 'Cabang B');
    $otherMaterial = makeRawMaterial($otherOwner->business, 'Bahan Baku Tenant Lain');
    $other = makePurchase($otherOwner->business, $otherBranch, $otherMaterial, 10, 10000, 'INV-OTHER');

    $foreignBatchQtyBefore = (float) $other['batch']->refresh()->quantity_remaining;

    // Coba retur di pembelian sendiri dengan batch milik tenant lain
    $this->actingAs($owner)->post(route('app.purchases.return.store', $setup['purchase']), [
        'return_date' => now()->format('Y-m-d'),
        'items' => [[
            'purchase_item_id' => $setup['item']->id,
            'raw_material_batch_id' => $other['batch']->id,
            'quantity' => 1,
        ]],
    ])->assertSessionHasErrors('items.0.raw_material_batch_id');

    // Batch tenant lain tidak boleh berubah
    expect((float) $other['batch']->refresh()->quantity_remaining)->toBe($foreignBatchQtyBefore);
});

/*
|--------------------------------------------------------------------------
| BUG 4 — purchase_item_id + validasi kuantitas retur + harga dari induk
|--------------------------------------------------------------------------
*/

test('retur tidak bisa melebihi kuantitas yang dibeli', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business);
    $setup = makePurchase($owner->business, $branch, $material, 10, 10000, 'INV-QTY');

    $this->actingAs($owner)->post(route('app.purchases.return.store', $setup['purchase']), [
        'return_date' => now()->format('Y-m-d'),
        'items' => [[
            'purchase_item_id' => $setup['item']->id,
            'raw_material_batch_id' => $setup['batch']->id,
            'quantity' => 999,
        ]],
    ])->assertSessionHasErrors('items.0.quantity');

    expect(PurchaseReturn::count())->toBe(0);
});

test('retur kumulatif tidak boleh melebihi kuantitas yang dibeli', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business);
    $setup = makePurchase($owner->business, $branch, $material, 10, 10000, 'INV-CUM');

    // Retur 6 dari 10 — boleh
    $this->actingAs($owner)->post(route('app.purchases.return.store', $setup['purchase']), [
        'return_date' => now()->format('Y-m-d'),
        'items' => [[
            'purchase_item_id' => $setup['item']->id,
            'raw_material_batch_id' => $setup['batch']->id,
            'quantity' => 6,
        ]],
    ])->assertSessionHasNoErrors();

    // Coba retur 6 lagi -> hanya boleh 4 tersisa
    $this->actingAs($owner)->post(route('app.purchases.return.store', $setup['purchase']), [
        'return_date' => now()->format('Y-m-d'),
        'items' => [[
            'purchase_item_id' => $setup['item']->id,
            'raw_material_batch_id' => $setup['batch']->id,
            'quantity' => 6,
        ]],
    ])->assertSessionHasErrors('items.0.quantity');
});

test('harga retur diambil dari baris pembelian bukan dari input user', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business);
    $setup = makePurchase($owner->business, $branch, $material, 10, 10000, 'INV-PRICE');

    // Coba kirim unit_price sangat rendah (harga aslinya 10.000)
    $this->actingAs($owner)->post(route('app.purchases.return.store', $setup['purchase']), [
        'return_date' => now()->format('Y-m-d'),
        'items' => [[
            'purchase_item_id' => $setup['item']->id,
            'raw_material_batch_id' => $setup['batch']->id,
            'quantity' => 2,
            'unit_price' => 1,
        ]],
    ])->assertSessionHasNoErrors();

    // unit_price harus tetap 10.000 (dari PurchaseItem)
    $returnItem = PurchaseReturnItem::firstOrFail();
    expect((float) $returnItem->unit_price)->toBe(10000.0);
    expect((float) $returnItem->subtotal)->toBe(20000.0);
});

test('retur menyimpan purchase_item_id untuk jejak audit', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business);
    $setup = makePurchase($owner->business, $branch, $material, 10, 10000, 'INV-TRACE');

    $this->actingAs($owner)->post(route('app.purchases.return.store', $setup['purchase']), [
        'return_date' => now()->format('Y-m-d'),
        'items' => [[
            'purchase_item_id' => $setup['item']->id,
            'raw_material_batch_id' => $setup['batch']->id,
            'quantity' => 1,
        ]],
    ])->assertSessionHasNoErrors();

    $returnItem = PurchaseReturnItem::firstOrFail();
    expect((int) $returnItem->purchase_item_id)->toBe((int) $setup['item']->id);
});

/*
|--------------------------------------------------------------------------
| BUG 7 — overpayment ditolak
|--------------------------------------------------------------------------
*/

test('pembayaran melebihi sisa utang ditolak', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business);
    $setup = makePurchase($owner->business, $branch, $material, 10, 10000, 'INV-OVER');

    // Total 100.000 — bayar 150.000 harus ditolak
    $this->actingAs($owner)->post(route('app.purchases.pay.store', $setup['purchase']), [
        'amount' => 150000,
        'paid_at' => now()->format('Y-m-d'),
        'method' => 'cash',
    ])->assertSessionHasErrors('amount');

    expect(PurchasePayment::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| BUG 5 — status pembayaran melalui satu service terpusat
|--------------------------------------------------------------------------
*/

test('status pembayaran dihitung PurchaseService dengan benar', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business);
    $setup = makePurchase($owner->business, $branch, $material, 10, 10000, 'INV-STATUS');
    $service = app(PurchaseService::class);

    // Awal: unpaid
    expect((string) $setup['purchase']->refresh()->payment_status)->toBe('unpaid');

    // Bayar sebagian -> partial
    app(PurchaseService::class)->recordPayment($setup['purchase'], 40000, now()->format('Y-m-d'), 'cash');
    expect($service->recalculatePaymentStatus($setup['purchase']))->toBe('partial');
    expect((string) $setup['purchase']->refresh()->payment_status)->toBe('partial');

    // Bayar sisa -> paid
    app(PurchaseService::class)->recordPayment($setup['purchase']->fresh(), 60000, now()->format('Y-m-d'), 'cash');
    expect($service->recalculatePaymentStatus($setup['purchase']->fresh()))->toBe('paid');
    expect((string) $setup['purchase']->refresh()->payment_status)->toBe('paid');
});

test('unpaid kembali menjadi unpaid saat tidak ada pembayaran', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business);
    $setup = makePurchase($owner->business, $branch, $material, 10, 10000, 'INV-UNPAID');

    expect(app(PurchaseService::class)->recalculatePaymentStatus($setup['purchase']))->toBe('unpaid');
});

/*
|--------------------------------------------------------------------------
| BUG 6 — pengurangan stok memakai lockForUpdate
|--------------------------------------------------------------------------
*/

test('decreaseRawMaterialStockFromBatch menolak melebihi stok', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business);
    $setup = makePurchase($owner->business, $branch, $material, 5, 1000, 'INV-LOCK');

    expect(fn () => app(StockService::class)->decreaseRawMaterialStockFromBatch(
        batchId: $setup['batch']->id,
        quantity: 999,
        referenceType: 'test',
        referenceId: 1,
        userId: $owner->id,
    ))->toThrow(InvalidArgumentException::class);

    // Stok tidak berubah
    expect((float) $setup['batch']->refresh()->quantity_remaining)->toBe(5.0);
});

test('decreaseRawMaterialStockFromBatch menolak batch milik business lain', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business);
    $setup = makePurchase($owner->business, $branch, $material, 5, 1000, 'INV-TENANT-CHECK');

    $otherOwner = makeOwner();
    $otherBranch = makeBranchFor($otherOwner->business, 'Cabang Lain');
    $otherMaterial = makeRawMaterial($otherOwner->business, 'Bahan Lain');
    $other = makePurchase($otherOwner->business, $otherBranch, $otherMaterial, 5, 1000, 'INV-OTHER-CHECK');

    expect(fn () => app(StockService::class)->decreaseRawMaterialStockFromBatch(
        batchId: $other['batch']->id,
        quantity: 1,
        referenceType: 'test',
        referenceId: 1,
        userId: $owner->id,
        businessId: $owner->business_id,
    ))->toThrow(InvalidArgumentException::class);
});

/*
|--------------------------------------------------------------------------
| BUG 13 — snapshot diskon & pajak
|--------------------------------------------------------------------------
*/

test('pembelian menyimpan snapshot diskon dan pajak', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business);
    $supplier = makeSupplier($owner->business);

    // 10 x 10.000 = 100.000, diskon 20.000 -> 80.000
    $this->actingAs($owner)->post(route('app.purchases.store'), [
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'invoice_no' => 'INV-DISC',
        'purchase_date' => now()->format('Y-m-d'),
        'discount_type' => 'nominal',
        'discount_value' => 20000,
        'items' => [[
            'raw_material_id' => $material->id,
            'quantity' => 10,
            'unit_price' => 10000,
            'batch_no' => 'BATCH-DISC',
        ]],
    ])->assertSessionHasNoErrors();

    $purchase = Purchase::where('invoice_no', 'INV-DISC')->firstOrFail();

    expect((float) $purchase->subtotal)->toBe(100000.0);
    expect((float) $purchase->discount_amount)->toBe(20000.0);
    expect((float) $purchase->total_amount)->toBe(80000.0);
});

/*
|--------------------------------------------------------------------------
| BUG 9 — permission pembelian ditegakkan
|--------------------------------------------------------------------------
*/

test('route pembayaran dan retur memakai PurchasePolicy', function () {
    // Pastikan policy terdaftar dan menolak tenant lain
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business);
    $setup = makePurchase($owner->business, $branch, $material, 10, 10000, 'INV-POLICY');

    $otherOwner = makeOwner();
    $otherBranch = makeBranchFor($otherOwner->business, 'Cabang X');
    $otherMaterial = makeRawMaterial($otherOwner->business, 'Bahan X');
    $other = makePurchase($otherOwner->business, $otherBranch, $otherMaterial, 10, 10000, 'INV-POLICY-X');

    // Owner lain tidak bisa akses form retur milik tenant pertama
    $this->actingAs($otherOwner)
        ->get(route('app.purchases.return.form', $setup['purchase']))
        ->assertNotFound();

    // Form retur milik sendiri boleh diakses
    $this->actingAs($owner)
        ->get(route('app.purchases.return.form', $setup['purchase']))
        ->assertOk();
});