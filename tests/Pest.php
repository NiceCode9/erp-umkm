<?php

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RawMaterial;
use App\Models\RawMaterialBatch;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Domain Test Helpers
|--------------------------------------------------------------------------
|
| Project ini belum punya factory untuk Branch/Product/Recipe/RawMaterial, jadi
| helper di bawah dipakai bersama oleh test yang butuh setup produksi.
|
*/

/**
 * Jalankan RolePermissionSeeder sungguhan supaya test ikut memvalidasi isi
 * seeder (regression guard: pernah Superadmin hanya dapat 2 permission).
 *
 * Sengaja memanggil run() langsung, bukan $this->seed(), karena helper ini
 * juga dipanggil dari dalam helper lain (bukan closure test) sehingga tidak
 * punya $this.
 */
function seedPermissions(): void
{
    (new \Database\Seeders\RolePermissionSeeder)->run();

    app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
}

function makeOwner(?Business $business = null): User
{
    seedPermissions();

    $business ??= Business::factory()->create(['is_active' => true]);

    $owner = User::factory()->create([
        'business_id' => $business->id,
        'is_active' => true,
    ]);
    $owner->assignRole('Owner');

    return $owner;
}

function makeBranchFor(Business $business, string $name = 'Cabang Utama'): Branch
{
    return Branch::create([
        'business_id' => $business->id,
        'name' => $name,
        'address' => 'Alamat cabang uji coba',
        'is_active' => true,
    ]);
}

function makeRawMaterial(
    Business $business,
    string $name = 'Tepung Terigu',
    ?string $halalCertExpiredDate = null,
    string $baseUnit = 'kg',
): RawMaterial {
    return RawMaterial::create([
        'business_id' => $business->id,
        'name' => $name,
        'base_unit' => $baseUnit,
        'minimum_stock' => 0,
        'halal_cert_expired_date' => $halalCertExpiredDate,
    ]);
}

/**
 * Siapkan satu skenario produksi lengkap: produk, resep, item resep, dan batch
 * bahan baku dengan stok tersedia. Return array asosiatif.
 */
function makeProductionSetup(
    Business $business,
    Branch $branch,
    RawMaterial $rawMaterial,
    float $stockQuantity = 100.0,
    float $qtyPerBatch = 1.0,
): array {
    $product = Product::create([
        'business_id' => $business->id,
        'name' => 'Roti Tawar',
        'sku' => 'ROT-001',
        'base_unit' => 'pcs',
        'selling_price' => 15000,
    ]);

    $recipe = Recipe::create([
        'product_id' => $product->id,
        'name' => 'Resep Roti Tawar',
        'yield_quantity' => 10,
        'is_active' => true,
    ]);

    RecipeItem::create([
        'recipe_id' => $recipe->id,
        'raw_material_id' => $rawMaterial->id,
        'qty_per_batch' => $qtyPerBatch,
        'unit' => $rawMaterial->base_unit,
    ]);

    $batch = RawMaterialBatch::create([
        'raw_material_id' => $rawMaterial->id,
        'branch_id' => $branch->id,
        'batch_no' => 'BATCH-001',
        'quantity_remaining' => $stockQuantity,
        'purchase_price' => 20000,
        'expired_date' => now()->addYear()->format('Y-m-d'),
        'received_at' => now()->format('Y-m-d'),
    ]);

    return [
        'product' => $product,
        'recipe' => $recipe,
        'batch' => $batch,
    ];
}

function makeSupplier(Business $business, string $name = 'Supplier Uji'): Supplier
{
    return Supplier::create([
        'business_id' => $business->id,
        'name' => $name,
        'phone' => '08123456789',
        'address' => 'Alamat supplier uji',
    ]);
}

/**
 * Buat satu pembelian lengkap: Purchase + PurchaseItem + RawMaterialBatch.
 *
 * Dibuat langsung lewat model (bukan lewat HTTP) supaya test bisa menyiapkan
 * fixture tanpa bergantung pada form. Test yang perlu menguji
 * PurchaseController::store() melakukan POST-nya sendiri di dalam closure.
 *
 * @return array{purchase: Purchase, item: PurchaseItem, batch: RawMaterialBatch}
 */
function makePurchase(
    Business $business,
    Branch $branch,
    RawMaterial $rawMaterial,
    float $quantity = 10.0,
    float $unitPrice = 10000.0,
    string $invoiceNo = 'INV-001',
    ?string $expiredDate = null,
    ?float $discountAmount = 0.0,
): array {
    $supplier = makeSupplier($business, "Supplier {$invoiceNo}");

    $subtotal = round($quantity * $unitPrice, 2);
    $total = round($subtotal - $discountAmount, 2);

    $purchase = Purchase::create([
        'business_id' => $business->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'user_id' => 1,
        'invoice_no' => $invoiceNo,
        'purchase_date' => now()->format('Y-m-d'),
        'subtotal' => $subtotal,
        'discount_type' => $discountAmount > 0 ? 'nominal' : null,
        'discount_value' => $discountAmount > 0 ? $discountAmount : null,
        'discount_amount' => $discountAmount,
        'tax_percentage_applied' => null,
        'tax_amount' => 0,
        'total_amount' => $total,
        'payment_status' => 'unpaid',
    ]);

    $item = PurchaseItem::create([
        'purchase_id' => $purchase->id,
        'raw_material_id' => $rawMaterial->id,
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'subtotal' => $subtotal,
        'batch_no' => "BATCH-{$invoiceNo}",
        'expired_date' => $expiredDate,
    ]);

    $batch = RawMaterialBatch::create([
        'business_id' => $business->id,
        'raw_material_id' => $rawMaterial->id,
        'branch_id' => $branch->id,
        'batch_no' => "BATCH-{$invoiceNo}",
        'quantity_remaining' => $quantity,
        'purchase_price' => $unitPrice,
        'expired_date' => $expiredDate,
        'received_at' => now()->format('Y-m-d'),
    ]);

    return compact('purchase', 'item', 'batch');
}

function makeSuperadmin(): User
{
    seedPermissions();

    $user = User::factory()->create([
        'business_id' => null,
        'is_active' => true,
    ]);
    $user->assignRole('Superadmin');

    return $user;
}

function makeKasir(Business $business, ?Branch $branch = null, string $name = 'Kasir Uji'): User
{
    seedPermissions();

    $branch ??= makeBranchFor($business);

    $kasir = User::factory()->create([
        'business_id' => $business->id,
        'branch_id' => $branch->id,
        'is_active' => true,
    ]);
    $kasir->assignRole('Kasir');

    return $kasir;
}
