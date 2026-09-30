<?php

use App\Models\ProductionOrder;
use App\Models\RawMaterial;
use App\Models\RawMaterialBatch;
use App\Models\RecipeItem;
use App\Services\StockService;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
|- Sertifikasi halal bahan baku: simpan tanggal & validasi
|--------------------------------------------------------------------------
*/

test('owner can create raw material with halal cert expiry date', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);

    $response = $this->actingAs($owner)->post(route('app.raw-materials.store'), [
        'name' => 'Susu UHT',
        'base_unit' => 'liter',
        'minimum_stock' => 10,
        'halal_cert_expired_date' => '2027-06-30',
    ]);

    $response->assertSessionHasNoErrors();

    $material = RawMaterial::where('name', 'Susu UHT')->firstOrFail();

    expect($material->business_id)->toBe($owner->business_id);
    expect($material->halal_cert_expired_date->format('Y-m-d'))->toBe('2027-06-30');
});

test('owner can create raw material without halal cert expiry date', function () {
    $owner = makeOwner();

    $response = $this->actingAs($owner)->post(route('app.raw-materials.store'), [
        'name' => 'Garam',
        'base_unit' => 'kg',
    ]);

    $response->assertSessionHasNoErrors();

    $material = RawMaterial::where('name', 'Garam')->firstOrFail();

    expect($material->halal_cert_expired_date)->toBeNull();
});

test('halal cert expiry date must be a valid date', function () {
    $owner = makeOwner();

    $this->actingAs($owner)
        ->post(route('app.raw-materials.store'), [
            'name' => 'Bahan Cacat',
            'base_unit' => 'kg',
            'halal_cert_expired_date' => 'bukan-tanggal',
        ])
        ->assertSessionHasErrors('halal_cert_expired_date');

    $this->assertDatabaseMissing('raw_materials', ['name' => 'Bahan Cacat']);
});

test('owner can update the halal cert expiry date to unblock production', function () {
    $owner = makeOwner();
    $material = makeRawMaterial($owner->business, 'Biji Kopi', now()->subDay()->format('Y-m-d'));

    expect($material->isHalalExpired())->toBeTrue();

    $this->actingAs($owner)
        ->put(route('app.raw-materials.update', $material), [
            'name' => $material->name,
            'base_unit' => $material->base_unit,
            'halal_cert_expired_date' => now()->addYear()->format('Y-m-d'),
        ])
        ->assertSessionHasNoErrors();

    expect($material->refresh()->isHalalExpired())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Pemblokiran produksi: bahan halal kedaluwarsa tidak boleh dipakai
|--------------------------------------------------------------------------
*/

test('production is blocked when a recipe material halal cert has expired', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business, 'Tepung Terigu', now()->subDays(5)->format('Y-m-d'));

    $setup = makeProductionSetup($owner->business, $branch, $material, stockQuantity: 100);

    $response = $this->actingAs($owner)->post(route('app.production.store'), [
        'product_id' => $setup['product']->id,
        'recipe_id' => $setup['recipe']->id,
        'branch_id' => $branch->id,
        'batch_multiplier' => 1,
    ]);

    $response->assertSessionHas('error');
    $this->assertStringContainsString('Tepung Terigu', session('error'));
    $this->assertStringContainsString('diblokir', session('error'));

    // Flash message di-render dengan {{ }} (escaped) + whitespace-pre-line,
    // jadi harus pakai newline — bukan tag <br> yang akan tampil literal.
    $this->assertStringNotContainsString('<br>', session('error'));
    $this->assertStringContainsString("\n", session('error'));

    // Tidak boleh ada production order yang tertinggal, dan stok tidak boleh tergerus.
    expect(ProductionOrder::count())->toBe(0);
    expect($setup['batch']->refresh()->quantity_remaining)->toEqual(100.0);
});

test('production is allowed when halal cert is still valid or not set', function (?string $halalDate) {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business, 'Tepung Terigu', $halalDate);

    $setup = makeProductionSetup($owner->business, $branch, $material, stockQuantity: 100);

    $this->actingAs($owner)->post(route('app.production.store'), [
        'product_id' => $setup['product']->id,
        'recipe_id' => $setup['recipe']->id,
        'branch_id' => $branch->id,
        'batch_multiplier' => 1,
    ])->assertSessionHasNoErrors();

    expect(ProductionOrder::count())->toBe(1);
    expect($setup['batch']->refresh()->quantity_remaining)->toEqual(99.0);
})->with([
    'tanpa tanggal halal' => [null],
    'tanggal halal masih berlaku' => [Carbon::now()->addMonths(6)->format('Y-m-d')],
]);

test('production is blocked even when only one of several materials is expired', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);

    $expired = makeRawMaterial($owner->business, 'Bahan Kedaluwarsa', now()->subDay()->format('Y-m-d'));
    $valid = makeRawMaterial($owner->business, 'Bahan Normal', null);

    $setup = makeProductionSetup($owner->business, $branch, $expired, stockQuantity: 100);

    // Tambahkan bahan kedua yang normal ke resep yang sama.
    RecipeItem::create([
        'recipe_id' => $setup['recipe']->id,
        'raw_material_id' => $valid->id,
        'qty_per_batch' => 1,
        'unit' => 'kg',
    ]);
    RawMaterialBatch::create([
        'raw_material_id' => $valid->id,
        'branch_id' => $branch->id,
        'batch_no' => 'BATCH-002',
        'quantity_remaining' => 50,
        'purchase_price' => 10000,
        'expired_date' => now()->addYear()->format('Y-m-d'),
        'received_at' => now()->format('Y-m-d'),
    ]);

    $this->actingAs($owner)->post(route('app.production.store'), [
        'product_id' => $setup['product']->id,
        'recipe_id' => $setup['recipe']->id,
        'branch_id' => $branch->id,
        'batch_multiplier' => 1,
    ])->assertSessionHas('error');

    expect(ProductionOrder::count())->toBe(0);
});

test('stock service rejects expired halal material even when called directly', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);
    $material = makeRawMaterial($owner->business, 'Tepung Terigu', now()->subDay()->format('Y-m-d'));

    $setup = makeProductionSetup($owner->business, $branch, $material, stockQuantity: 100);

    // Guard defensif: dipanggil langsung, tanpa lewat pre-check controller.
    expect(fn () => app(StockService::class)->consumeRawMaterialsForProduction(
        recipeId: $setup['recipe']->id,
        branchId: $branch->id,
        businessId: $owner->business_id,
        batchMultiplier: 1,
        productionOrderId: 1,
        userId: $owner->id,
    ))->toThrow(InvalidArgumentException::class, 'Tepung Terigu');

    // Stok harus utuh karena guard menolak sebelum transaksi berjalan.
    expect($setup['batch']->refresh()->quantity_remaining)->toEqual(100.0);
});

test('getExpiredHalalRawMaterialsInRecipe returns only expired materials', function () {
    $owner = makeOwner();
    $branch = makeBranchFor($owner->business);

    $expired = makeRawMaterial($owner->business, 'Bahan Expired', now()->subDay()->format('Y-m-d'));
    $valid = makeRawMaterial($owner->business, 'Bahan Valid', now()->addMonth()->format('Y-m-d'));

    $setup = makeProductionSetup($owner->business, $branch, $expired, stockQuantity: 100);
    RecipeItem::create([
        'recipe_id' => $setup['recipe']->id,
        'raw_material_id' => $valid->id,
        'qty_per_batch' => 1,
        'unit' => 'kg',
    ]);

    $result = app(StockService::class)->getExpiredHalalRawMaterialsInRecipe($setup['recipe']->id);

    expect($result)->toHaveCount(1);
    expect($result->first()->name)->toBe('Bahan Expired');
});

/*
|--------------------------------------------------------------------------
| Scope & helper model
|--------------------------------------------------------------------------
*/

test('halal scopes separate expiring soon from already expired', function () {
    $owner = makeOwner();

    makeRawMaterial($owner->business, 'Sudah Lewat', now()->subDays(10)->format('Y-m-d'));
    makeRawMaterial($owner->business, 'Segera Lewat', now()->addDays(20)->format('Y-m-d'));
    makeRawMaterial($owner->business, 'Masih Lama', now()->addDays(200)->format('Y-m-d'));
    makeRawMaterial($owner->business, 'Tanpa Tanggal', null);

    $businessId = $owner->business_id;

    $expiredNames = RawMaterial::where('business_id', $businessId)
        ->halalExpired()->pluck('name')->all();

    $expiringNames = RawMaterial::where('business_id', $businessId)
        ->halalExpiringWithin(30)->pluck('name')->all();

    expect($expiredNames)->toContain('Sudah Lewat');
    expect($expiredNames)->not->toContain('Segera Lewat');

    expect($expiringNames)->toContain('Segera Lewat');
    expect($expiringNames)->not->toContain('Sudah Lewat');
    expect($expiringNames)->not->toContain('Tanpa Tanggal');
});

/*
|--------------------------------------------------------------------------
| Dashboard Owner
|--------------------------------------------------------------------------
*/

test('owner dashboard lists raw materials expiring within 30 days and already expired', function () {
    $owner = makeOwner();
    makeBranchFor($owner->business);

    makeRawMaterial($owner->business, 'Bahan Segera Expired', now()->addDays(15)->format('Y-m-d'));
    makeRawMaterial($owner->business, 'Bahan Sudah Expired', now()->subDays(3)->format('Y-m-d'));

    $response = $this->actingAs($owner)->get(route('app.dashboard'));

    $response->assertOk();
    $response->assertSee('Sertifikasi Halal Bahan Baku Akan Expired');
    $response->assertSee('Bahan Segera Expired');
    $response->assertSee('Sertifikasi Halal Bahan Baku Sudah Expired');
    $response->assertSee('Bahan Sudah Expired');
});

test('owner dashboard hides expiring widget when there is no halal data', function () {
    $owner = makeOwner();
    makeBranchFor($owner->business);
    makeRawMaterial($owner->business, 'Bahan Biasa Saja', null);

    $this->actingAs($owner)
        ->get(route('app.dashboard'))
        ->assertOk()
        ->assertDontSee('Sertifikasi Halal Bahan Baku');
});

test('owner dashboard never leaks another tenant halal data', function () {
    $ownerA = makeOwner();
    makeBranchFor($ownerA->business, 'Cabang A');

    $ownerB = makeOwner();
    makeBranchFor($ownerB->business, 'Cabang B');

    makeRawMaterial($ownerA->business, 'Bahan Baku Milik A', now()->subDay()->format('Y-m-d'));

    $this->actingAs($ownerB)
        ->get(route('app.dashboard'))
        ->assertOk()
        ->assertDontSee('Bahan Baku Milik A');
});

test('raw material list shows halal status badge', function () {
    $owner = makeOwner();
    makeBranchFor($owner->business);
    makeRawMaterial($owner->business, 'Bahan Expired', now()->subDay()->format('Y-m-d'));
    makeRawMaterial($owner->business, 'Bahan Segera', now()->addDays(10)->format('Y-m-d'));

    $this->actingAs($owner)
        ->get(route('app.raw-materials.index'))
        ->assertOk()
        ->assertSee('Expired')
        ->assertSee('Akan Expired');
});
