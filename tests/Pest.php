<?php

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\RawMaterialBatch;
use App\Models\Recipe;
use App\Models\RecipeItem;
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

function makeOwner(?Business $business = null): User
{
    foreach (['Superadmin', 'Owner', 'Kasir'] as $role) {
        Role::findOrCreate($role, 'web');
    }

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

    return compact('product', 'recipe', 'batch');
}
