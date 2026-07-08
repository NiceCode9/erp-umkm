<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\RawMaterial;
use App\Models\RawMaterialBatch;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::first();
        if (!$business) {
            $this->command->warn('No business found. Run UserSeeder first.');
            return;
        }

        $branches = Branch::where('business_id', $business->id)->where('is_active', true)->get();
        if ($branches->isEmpty()) {
            $this->command->warn('No active branches found.');
            return;
        }

        $rawMaterials = RawMaterial::where('business_id', $business->id)->get();
        if ($rawMaterials->isEmpty()) {
            $this->command->warn('No raw materials found. Run MasterDataSeeder first.');
            return;
        }

        $batchCount = 0;

        foreach ($rawMaterials as $rm) {
            foreach ($branches as $branch) {
                // Create 1-2 batches per material per branch with varying dates
                $batches = match ($rm->name) {
                    'Tepung Terigu' => [
                        ['batch_no' => 'TP-001', 'qty' => 25, 'price' => 12000, 'exp' => now()->addDays(60)],
                        ['batch_no' => 'TP-002', 'qty' => 15, 'price' => 12500, 'exp' => now()->addDays(90)],
                    ],
                    'Gula Pasir' => [
                        ['batch_no' => 'GP-001', 'qty' => 20, 'price' => 15000, 'exp' => now()->addDays(120)],
                    ],
                    'Telur Ayam' => [
                        ['batch_no' => 'TL-001', 'qty' => 10, 'price' => 28000, 'exp' => now()->addDays(14)],
                        ['batch_no' => 'TL-002', 'qty' => 8, 'price' => 28500, 'exp' => now()->addDays(21)],
                    ],
                    'Mentega' => [
                        ['batch_no' => 'MT-001', 'qty' => 12, 'price' => 35000, 'exp' => now()->addDays(45)],
                    ],
                    'Ragi Instan' => [
                        ['batch_no' => 'RG-001', 'qty' => 50, 'price' => 2000, 'exp' => now()->addDays(180)],
                        ['batch_no' => 'RG-002', 'qty' => 30, 'price' => 2200, 'exp' => now()->addDays(240)],
                    ],
                    'Susu Bubuk' => [
                        ['batch_no' => 'SB-001', 'qty' => 8, 'price' => 45000, 'exp' => now()->addDays(30)],
                    ],
                    'Garam' => [
                        ['batch_no' => 'GR-001', 'qty' => 5, 'price' => 5000, 'exp' => now()->addDays(365)],
                    ],
                    'Coklat Bubuk' => [
                        ['batch_no' => 'CB-001', 'qty' => 6, 'price' => 55000, 'exp' => now()->addDays(90)],
                    ],
                    default => [
                        ['batch_no' => 'GEN-001', 'qty' => 10, 'price' => 10000, 'exp' => now()->addDays(30)],
                    ],
                };

                foreach ($batches as $b) {
                    RawMaterialBatch::firstOrCreate(
                        [
                            'raw_material_id' => $rm->id,
                            'branch_id' => $branch->id,
                            'batch_no' => $b['batch_no'] . '-' . $branch->id,
                        ],
                        [
                            'raw_material_id' => $rm->id,
                            'branch_id' => $branch->id,
                            'batch_no' => $b['batch_no'] . '-' . $branch->id,
                            'quantity_remaining' => $b['qty'],
                            'purchase_price' => $b['price'],
                            'expired_date' => $b['exp'],
                            'received_at' => now()->subDays(rand(1, 10)),
                        ]
                    );
                    $batchCount++;
                }
            }
        }

        $this->command->info("✓ {$batchCount} batches seeded across {$branches->count()} branches");
    }
}
