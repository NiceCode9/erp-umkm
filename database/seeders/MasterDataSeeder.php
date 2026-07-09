<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::first();
        if (!$business) { $this->command->warn('No business found.'); return; }

        $this->seedSuppliers($business);
        $this->seedCustomers($business);
        $this->seedRawMaterials($business);
        $this->seedProducts($business);
    }

    private function seedSuppliers(Business $business): void
    {
        $items = [
            ['name' => 'PT Tepung Makmur', 'phone' => '021-5551001', 'address' => 'Jl. Industri No. 10, Jakarta'],
            ['name' => 'CV Gula Manis', 'phone' => '022-5552002', 'address' => 'Jl. Raya Bandung No. 45, Bandung'],
            ['name' => 'UD Telur Segar', 'phone' => '024-5553003', 'address' => 'Jl. Semarang Indah No. 78, Semarang'],
            ['name' => 'PT Mentega Asri', 'phone' => '031-5554004', 'address' => 'Jl. Surabaya No. 12, Surabaya'],
        ];
        foreach ($items as $data) {
            Supplier::firstOrCreate(
                ['business_id' => $business->id, 'name' => $data['name']],
                [...$data, 'business_id' => $business->id]
            );
        }
        $this->command->info('✓ 4 suppliers seeded');
    }

    private function seedCustomers(Business $business): void
    {
        $items = [
            ['name' => 'Ahmad Fauzi', 'phone' => '081234567891', 'address' => 'Jl. Merdeka No. 1, Jakarta'],
            ['name' => 'Siti Rahmawati', 'phone' => '081234567892', 'address' => 'Jl. Sudirman No. 20, Bandung'],
            ['name' => 'Bambang Susilo', 'phone' => '081234567893', 'address' => 'Jl. Diponegoro No. 5, Semarang'],
            ['name' => 'Dewi Sartika', 'phone' => '081234567894', 'address' => 'Jl. Gajah Mada No. 8, Surabaya'],
        ];
        foreach ($items as $data) {
            Customer::firstOrCreate(
                ['business_id' => $business->id, 'name' => $data['name']],
                [...$data, 'business_id' => $business->id]
            );
        }
        $this->command->info('✓ 4 customers seeded');
    }

    private function seedRawMaterials(Business $business): void
    {
        $items = [
            ['name' => 'Tepung Terigu', 'base_unit' => 'kg', 'minimum_stock' => 10],
            ['name' => 'Gula Pasir', 'base_unit' => 'kg', 'minimum_stock' => 5],
            ['name' => 'Telur Ayam', 'base_unit' => 'kg', 'minimum_stock' => 2],
            ['name' => 'Mentega', 'base_unit' => 'kg', 'minimum_stock' => 3],
            ['name' => 'Ragi Instan', 'base_unit' => 'sachet', 'minimum_stock' => 10],
            ['name' => 'Susu Bubuk', 'base_unit' => 'kg', 'minimum_stock' => 2],
            ['name' => 'Garam', 'base_unit' => 'kg', 'minimum_stock' => 1],
            ['name' => 'Coklat Bubuk', 'base_unit' => 'kg', 'minimum_stock' => 1],
        ];
        foreach ($items as $data) {
            RawMaterial::firstOrCreate(
                ['business_id' => $business->id, 'name' => $data['name']],
                [...$data, 'business_id' => $business->id]
            );
        }
        $this->command->info('✓ 8 raw materials seeded');
    }

    private function seedProducts(Business $business): void
    {
        $tepung = RawMaterial::where('business_id', $business->id)->where('name', 'Tepung Terigu')->first();
        $gula = RawMaterial::where('business_id', $business->id)->where('name', 'Gula Pasir')->first();
        $telur = RawMaterial::where('business_id', $business->id)->where('name', 'Telur Ayam')->first();
        $mentega = RawMaterial::where('business_id', $business->id)->where('name', 'Mentega')->first();
        $ragi = RawMaterial::where('business_id', $business->id)->where('name', 'Ragi Instan')->first();
        $susu = RawMaterial::where('business_id', $business->id)->where('name', 'Susu Bubuk')->first();
        $garam = RawMaterial::where('business_id', $business->id)->where('name', 'Garam')->first();
        $coklat = RawMaterial::where('business_id', $business->id)->where('name', 'Coklat Bubuk')->first();

        $productDefs = [
            [
                'name' => 'Roti Tawar', 'sku' => 'RT-001', 'base_unit' => 'biji', 'selling_price' => 15000,
                'units' => [['unit_name' => 'isi 12', 'conversion_to_base' => 12, 'price_override' => 150000]],
                'recipes' => [
                    [
                        'name' => 'Resep 12 Roti', 'yield' => 12,
                        'items' => [
                            [$tepung->id, 500, 'g'], [$gula->id, 100, 'g'], [$telur->id, 0.2, 'kg'],
                            [$mentega->id, 50, 'g'], [$ragi->id, 2, 'sachet'], [$susu->id, 50, 'g'], [$garam->id, 5, 'g'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Roti Coklat', 'sku' => 'RC-001', 'base_unit' => 'biji', 'selling_price' => 8000,
                'units' => [['unit_name' => 'isi 20', 'conversion_to_base' => 20, 'price_override' => 140000]],
                'recipes' => [
                    [
                        'name' => 'Resep 20 Roti Coklat', 'yield' => 20,
                        'items' => [
                            [$tepung->id, 800, 'g'], [$gula->id, 150, 'g'], [$telur->id, 0.3, 'kg'],
                            [$mentega->id, 80, 'g'], [$coklat->id, 100, 'g'], [$susu->id, 50, 'g'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Roti Keju', 'sku' => 'RK-001', 'base_unit' => 'biji', 'selling_price' => 10000,
                'units' => [['unit_name' => 'isi 20', 'conversion_to_base' => 20, 'price_override' => 170000]],
                'recipes' => [
                    [
                        'name' => 'Resep 20 Roti Keju', 'yield' => 20,
                        'items' => [
                            [$tepung->id, 800, 'g'], [$gula->id, 150, 'g'], [$telur->id, 0.3, 'kg'],
                            [$mentega->id, 80, 'g'], [$susu->id, 50, 'g'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Kue Bolu', 'sku' => 'KB-001', 'base_unit' => 'loyang', 'selling_price' => 45000,
                'units' => [['unit_name' => 'potong', 'conversion_to_base' => 12, 'price_override' => 5000]],
                'recipes' => [
                    [
                        'name' => 'Resep 1 Loyang Bolu', 'yield' => 1,
                        'items' => [
                            [$tepung->id, 250, 'g'], [$gula->id, 200, 'g'], [$telur->id, 0.3, 'kg'],
                            [$mentega->id, 100, 'g'], [$susu->id, 50, 'g'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Brownies', 'sku' => 'BR-001', 'base_unit' => 'loyang', 'selling_price' => 55000,
                'units' => [['unit_name' => 'potong', 'conversion_to_base' => 16, 'price_override' => 5000]],
                'recipes' => [
                    [
                        'name' => 'Resep 1 Loyang Brownies', 'yield' => 1,
                        'items' => [
                            [$tepung->id, 200, 'g'], [$gula->id, 250, 'g'], [$telur->id, 0.4, 'kg'],
                            [$mentega->id, 150, 'g'], [$coklat->id, 100, 'g'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Donat', 'sku' => 'DN-001', 'base_unit' => 'biji', 'selling_price' => 5000,
                'units' => [['unit_name' => 'isi 24', 'conversion_to_base' => 24, 'price_override' => 100000]],
                'recipes' => [
                    [
                        'name' => 'Resep 24 Donat', 'yield' => 24,
                        'items' => [
                            [$tepung->id, 600, 'g'], [$gula->id, 120, 'g'], [$telur->id, 0.2, 'kg'],
                            [$mentega->id, 60, 'g'], [$ragi->id, 3, 'sachet'], [$susu->id, 50, 'g'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($productDefs as $data) {
            $product = Product::firstOrCreate(
                ['business_id' => $business->id, 'sku' => $data['sku']],
                [
                    'business_id' => $business->id,
                    'name' => $data['name'],
                    'sku' => $data['sku'],
                    'base_unit' => $data['base_unit'],
                    'selling_price' => $data['selling_price'],
                ]
            );

            if ($product->wasRecentlyCreated && !empty($data['units'])) {
                foreach ($data['units'] as $unit) {
                    ProductUnit::create([
                        'product_id' => $product->id,
                        'unit_name' => $unit['unit_name'],
                        'conversion_to_base' => $unit['conversion_to_base'],
                        'price_override' => $unit['price_override'] ?? null,
                    ]);
                }
            }

            if ($product->wasRecentlyCreated && !empty($data['recipes'])) {
                foreach ($data['recipes'] as $rDef) {
                    $recipe = Recipe::create([
                        'product_id' => $product->id,
                        'name' => $rDef['name'],
                        'yield_quantity' => $rDef['yield'],
                        'is_active' => true,
                    ]);
                    foreach ($rDef['items'] as $item) {
                        $recipe->items()->create([
                            'raw_material_id' => $item[0],
                            'qty_per_batch' => $item[1],
                            'unit' => $item[2],
                        ]);
                    }
                }
            }
        }

        $this->command->info('✓ 6 products seeded with recipes');
    }
}
