<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\RawMaterial;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::first();

        if (!$business) {
            $this->command->warn('No business found. Run UserSeeder first.');
            return;
        }

        $this->seedSuppliers($business);
        $this->seedCustomers($business);
        $this->seedRawMaterials($business);
        $this->seedProducts($business);
    }

    private function seedSuppliers(Business $business): void
    {
        $suppliers = [
            ['name' => 'PT Tepung Makmur', 'phone' => '021-5551001', 'address' => 'Jl. Industri No. 10, Jakarta'],
            ['name' => 'CV Gula Manis', 'phone' => '022-5552002', 'address' => 'Jl. Raya Bandung No. 45, Bandung'],
            ['name' => 'UD Telur Segar', 'phone' => '024-5553003', 'address' => 'Jl. Semarang Indah No. 78, Semarang'],
            ['name' => 'PT Mentega Asri', 'phone' => '031-5554004', 'address' => 'Jl. Surabaya No. 12, Surabaya'],
        ];

        foreach ($suppliers as $data) {
            Supplier::firstOrCreate(
                ['business_id' => $business->id, 'name' => $data['name']],
                [...$data, 'business_id' => $business->id]
            );
        }

        $this->command->info('✓ ' . count($suppliers) . ' suppliers seeded');
    }

    private function seedCustomers(Business $business): void
    {
        $customers = [
            ['name' => 'Ahmad Fauzi', 'phone' => '081234567891', 'address' => 'Jl. Merdeka No. 1, Jakarta'],
            ['name' => 'Siti Rahmawati', 'phone' => '081234567892', 'address' => 'Jl. Sudirman No. 20, Bandung'],
            ['name' => 'Bambang Susilo', 'phone' => '081234567893', 'address' => 'Jl. Diponegoro No. 5, Semarang'],
            ['name' => 'Dewi Sartika', 'phone' => '081234567894', 'address' => 'Jl. Gajah Mada No. 8, Surabaya'],
        ];

        foreach ($customers as $data) {
            Customer::firstOrCreate(
                ['business_id' => $business->id, 'name' => $data['name']],
                [...$data, 'business_id' => $business->id]
            );
        }

        $this->command->info('✓ ' . count($customers) . ' customers seeded');
    }

    private function seedRawMaterials(Business $business): void
    {
        $materials = [
            ['name' => 'Tepung Terigu', 'base_unit' => 'kg', 'minimum_stock' => 10],
            ['name' => 'Gula Pasir', 'base_unit' => 'kg', 'minimum_stock' => 5],
            ['name' => 'Telur Ayam', 'base_unit' => 'kg', 'minimum_stock' => 2],
            ['name' => 'Mentega', 'base_unit' => 'kg', 'minimum_stock' => 3],
            ['name' => 'Ragi Instan', 'base_unit' => 'sachet', 'minimum_stock' => 10],
            ['name' => 'Susu Bubuk', 'base_unit' => 'kg', 'minimum_stock' => 2],
            ['name' => 'Garam', 'base_unit' => 'kg', 'minimum_stock' => 1],
            ['name' => 'Coklat Bubuk', 'base_unit' => 'kg', 'minimum_stock' => 1],
        ];

        foreach ($materials as $data) {
            RawMaterial::firstOrCreate(
                ['business_id' => $business->id, 'name' => $data['name']],
                [...$data, 'business_id' => $business->id]
            );
        }

        $this->command->info('✓ ' . count($materials) . ' raw materials seeded');
    }

    private function seedProducts(Business $business): void
    {
        $products = [
            [
                'name' => 'Roti Tawar',
                'sku' => 'RT-001',
                'base_unit' => 'biji',
                'selling_price' => 15000,
                'recipe_yield' => 12,
                'units' => [
                    ['unit_name' => 'isi 12', 'conversion_to_base' => 12, 'price_override' => 150000],
                ],
            ],
            [
                'name' => 'Roti Coklat',
                'sku' => 'RC-001',
                'recipe_yield' => 20,
                'base_unit' => 'biji',
                'selling_price' => 8000,
                'recipe_yield' => 20,
                'units' => [
                    ['unit_name' => 'isi 20', 'conversion_to_base' => 20, 'price_override' => 140000],
                ],
            ],
            [
                'name' => 'Roti Keju',
                'sku' => 'RK-001',
                'recipe_yield' => 20,
                'base_unit' => 'biji',
                'selling_price' => 10000,
                'recipe_yield' => 20,
                'units' => [
                    ['unit_name' => 'isi 20', 'conversion_to_base' => 20, 'price_override' => 170000],
                ],
            ],
            [
                'name' => 'Kue Bolu',
                'sku' => 'KB-001',
                'recipe_yield' => 1,
                'base_unit' => 'loyang',
                'selling_price' => 45000,
                'recipe_yield' => 1,
                'units' => [
                    ['unit_name' => 'potong', 'conversion_to_base' => 12, 'price_override' => 5000],
                ],
            ],
            [
                'name' => 'Brownies',
                'sku' => 'BR-001',
                'recipe_yield' => 1,
                'base_unit' => 'loyang',
                'selling_price' => 55000,
                'recipe_yield' => 1,
                'units' => [
                    ['unit_name' => 'potong', 'conversion_to_base' => 16, 'price_override' => 5000],
                ],
            ],
            [
                'name' => 'Donat',
                'sku' => 'DN-001',
                'recipe_yield' => 24,
                'base_unit' => 'biji',
                'selling_price' => 5000,
                'recipe_yield' => 24,
                'units' => [
                    ['unit_name' => 'isi 24', 'conversion_to_base' => 24, 'price_override' => 100000],
                ],
            ],
        ];

        foreach ($products as $data) {
            $product = Product::firstOrCreate(
                ['business_id' => $business->id, 'sku' => $data['sku']],
                [
                    'business_id' => $business->id,
                    'name' => $data['name'],
                    'sku' => $data['sku'],
                    'base_unit' => $data['base_unit'],
                    'selling_price' => $data['selling_price'],
                    'recipe_yield_quantity' => $data['recipe_yield'] ?? 1,
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
        }

        $this->command->info('✓ ' . count($products) . ' products seeded');
    }
}
