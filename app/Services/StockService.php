<?php

namespace App\Services;

use App\Models\ProductBatch;
use App\Models\ProductRecipe;
use App\Models\ProductionConsumption;
use App\Models\RawMaterial;
use App\Models\RawMaterialBatch;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function increaseRawMaterialStock(
        int $rawMaterialId, int $branchId, int $businessId,
        string $batchNo, float $quantity, float $purchasePrice,
        ?string $expiredDate, string $referenceType, int $referenceId, int $userId,
    ): RawMaterialBatch {
        return DB::transaction(function () use (
            $rawMaterialId, $branchId, $businessId, $batchNo,
            $quantity, $purchasePrice, $expiredDate,
            $referenceType, $referenceId, $userId
        ) {
            $batch = RawMaterialBatch::create([
                'raw_material_id' => $rawMaterialId,
                'branch_id' => $branchId,
                'batch_no' => $batchNo,
                'quantity_remaining' => $quantity,
                'purchase_price' => $purchasePrice,
                'expired_date' => $expiredDate,
                'received_at' => now(),
            ]);

            $this->recordMovement($businessId, $branchId, 'raw_material', $rawMaterialId,
                $batch->id, 'in', $quantity, $referenceType, $referenceId, $userId);

            return $batch;
        });
    }

    public function decreaseRawMaterialStockFromBatch(
        int $batchId, float $quantity,
        string $referenceType, int $referenceId, int $userId,
    ): RawMaterialBatch {
        return DB::transaction(function () use ($batchId, $quantity, $referenceType, $referenceId, $userId) {
            $batch = RawMaterialBatch::findOrFail($batchId);
            if ($batch->quantity_remaining < $quantity) {
                throw new \InvalidArgumentException(
                    "Stok batch {$batch->batch_no} tidak mencukupi. " .
                    "Sisa: {$batch->quantity_remaining}, diminta: {$quantity}"
                );
            }
            $batch->decrement('quantity_remaining', $quantity);

            $this->recordMovement($batch->rawMaterial->business_id, $batch->branch_id,
                'raw_material', $batch->raw_material_id, $batch->id,
                'out', $quantity, $referenceType, $referenceId, $userId);

            return $batch->fresh();
        });
    }

    /**
     * Consume raw materials for production using yield-based formula & FEFO.
     * qty_per_unit_efektif = qty_per_batch / recipe_yield_quantity
     * total_kebutuhan = qty_per_unit_efektif × quantity_target
     *
     * @throws \InvalidArgumentException when stock is insufficient
     */
    public function consumeRawMaterialsForProduction(
        int $productId, int $branchId, int $businessId,
        float $quantityTarget, int $productionOrderId, int $userId,
    ): Collection {
        $product = \App\Models\Product::findOrFail($productId);
        $yieldQty = (float) ($product->recipe_yield_quantity ?: 1);

        $recipes = ProductRecipe::where('product_id', $productId)->with('rawMaterial')->get();

        if ($recipes->isEmpty()) {
            throw new \InvalidArgumentException('Produk ini belum memiliki resep (BOM).');
        }

        $shortages = [];

        return DB::transaction(function () use (
            $recipes, $branchId, $businessId, $quantityTarget,
            $productionOrderId, $userId, &$shortages, $yieldQty
        ) {
            $consumptions = collect();

            foreach ($recipes as $recipe) {
                // yield-based formula
                $qtyPerUnitEfektif = $recipe->qty_per_batch / $yieldQty;
                $totalNeeded = $qtyPerUnitEfektif * $quantityTarget;

                $convertedNeeded = $this->convertUnit(
                    $totalNeeded, $recipe->unit, $recipe->rawMaterial->base_unit
                );

                // Check total available stock first
                $totalAvailable = (float) RawMaterialBatch::where('raw_material_id', $recipe->raw_material_id)
                    ->where('branch_id', $branchId)
                    ->where('quantity_remaining', '>', 0)
                    ->sum('quantity_remaining');

                if ($totalAvailable < $convertedNeeded) {
                    $shortages[] = (object) [
                        'raw_material' => $recipe->rawMaterial->name,
                        'unit' => $recipe->rawMaterial->base_unit,
                        'needed' => $convertedNeeded,
                        'available' => $totalAvailable,
                        'shortage' => $convertedNeeded - $totalAvailable,
                    ];
                    continue;
                }

                // FEFO: get batches ordered by expired_date ASC (NULLS LAST)
                $batches = RawMaterialBatch::where('raw_material_id', $recipe->raw_material_id)
                    ->where('branch_id', $branchId)
                    ->where('quantity_remaining', '>', 0)
                    ->orderByRaw('COALESCE(expired_date, \'9999-12-31\') ASC')
                    ->get();

                $remaining = $convertedNeeded;

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;

                    $deductQty = min($remaining, (float) $batch->quantity_remaining);
                    $batch->decrement('quantity_remaining', $deductQty);

                    $consumption = ProductionConsumption::create([
                        'production_order_id' => $productionOrderId,
                        'raw_material_batch_id' => $batch->id,
                        'quantity_deducted' => $deductQty,
                    ]);
                    $consumptions->push($consumption);

                    $this->recordMovement($businessId, $branchId, 'raw_material',
                        $recipe->raw_material_id, $batch->id,
                        'out', $deductQty, 'production', $productionOrderId, $userId);

                    $remaining -= $deductQty;
                }

                // Safety check (shouldn't happen if we did the total check above)
                if ($remaining > 0) {
                    $shortages[] = (object) [
                        'raw_material' => $recipe->rawMaterial->name,
                        'unit' => $recipe->rawMaterial->base_unit,
                        'needed' => $convertedNeeded,
                        'available' => $convertedNeeded - $remaining,
                        'shortage' => $remaining,
                    ];
                }
            }

            if (!empty($shortages)) {
                $msgs = collect($shortages)->map(fn ($s) =>
                    "{$s->raw_material}: butuh {$s->needed} {$s->unit}, tersedia {$s->available} {$s->unit} (kurang {$s->shortage})"
                )->implode("\n");
                throw new \InvalidArgumentException("Stok bahan baku tidak mencukupi:\n{$msgs}");
            }

            return $consumptions;
        });
    }

    /**
     * Add product stock from production result into product_batches.
     */
    public function addProductStockFromProduction(
        int $productId, int $branchId, int $businessId,
        float $quantity, int $productionOrderId, int $userId,
        ?string $expiredDate = null,
    ): void {
        DB::transaction(function () use (
            $productId, $branchId, $businessId, $quantity,
            $productionOrderId, $userId, $expiredDate
        ) {
            $order = \App\Models\ProductionOrder::findOrFail($productionOrderId);

            $batch = ProductBatch::create([
                'product_id' => $productId,
                'branch_id' => $branchId,
                'batch_no' => $order->production_code ?? 'PROD-' . $order->id,
                'quantity_remaining' => $quantity,
                'production_cost' => 0,
                'production_code' => $order->production_code,
                'expired_date' => $expiredDate,
                'produced_at' => now(),
            ]);

            $this->recordMovement($businessId, $branchId, 'product', $productId,
                $batch->id, 'in', $quantity, 'production', $productionOrderId, $userId);
        });
    }

    /**
     * Check stock availability for a production order (without consuming).
     * Uses yield-based formula: qty_per_unit_efektif = qty_per_batch / recipe_yield_quantity
     * Returns array of shortage objects, empty if sufficient.
     */
    public function checkProductionStockAvailability(
        int $productId, int $branchId, float $quantityTarget
    ): array {
        $product = \App\Models\Product::findOrFail($productId);
        $yieldQty = (float) ($product->recipe_yield_quantity ?: 1);

        $recipes = ProductRecipe::where('product_id', $productId)->with('rawMaterial')->get();
        $shortages = [];

        foreach ($recipes as $recipe) {
            $qtyPerUnitEfektif = $recipe->qty_per_batch / $yieldQty;
            $totalNeeded = $qtyPerUnitEfektif * $quantityTarget;
            $convertedNeeded = $this->convertUnit(
                $totalNeeded, $recipe->unit, $recipe->rawMaterial->base_unit
            );

            $totalAvailable = (float) RawMaterialBatch::where('raw_material_id', $recipe->raw_material_id)
                ->where('branch_id', $branchId)
                ->where('quantity_remaining', '>', 0)
                ->sum('quantity_remaining');

            if ($totalAvailable < $convertedNeeded) {
                $shortages[] = (object) [
                    'name' => $recipe->rawMaterial->name,
                    'unit' => $recipe->rawMaterial->base_unit,
                    'needed' => $convertedNeeded,
                    'available' => $totalAvailable,
                    'shortage' => $convertedNeeded - $totalAvailable,
                ];
            }
        }

        return $shortages;
    }

    /**
     * Simple unit conversion (g↔kg, ml↔liter).
     */
    private function convertUnit(float $qty, string $fromUnit, string $toUnit): float
    {
        if ($fromUnit === $toUnit) return $qty;

        $conversions = [
            'g' => 1, 'kg' => 1000,
            'ml' => 1, 'liter' => 1000, 'l' => 1000,
        ];

        if (isset($conversions[$fromUnit]) && isset($conversions[$toUnit])) {
            $fromG = $qty * ($conversions[$fromUnit] === 1 ? 1 : $conversions[$fromUnit]);
            return $fromG / ($conversions[$toUnit] === 1 ? 1 : $conversions[$toUnit]);
        }

        return $qty;
    }

    private function recordMovement(
        int $businessId, int $branchId, string $itemType, int $itemId,
        ?int $batchId, string $movementType, float $quantity,
        string $referenceType, int $referenceId, int $userId,
    ): void {
        StockMovement::create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'item_type' => $itemType,
            'item_id' => $itemId,
            'batch_id' => $batchId,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $userId,
        ]);
    }

    public function getRawMaterialStock(int $rawMaterialId, int $branchId): float
    {
        return (float) RawMaterialBatch::where('raw_material_id', $rawMaterialId)
            ->where('branch_id', $branchId)
            ->where('quantity_remaining', '>', 0)
            ->sum('quantity_remaining');
    }

    public function getProductStock(int $productId, int $branchId): float
    {
        return (float) ProductBatch::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('quantity_remaining', '>', 0)
            ->sum('quantity_remaining');
    }

    /**
     * Generate production code: PRD-YYYYMMDD-XXXX (sequential per day).
     */
    public function generateProductionCode(): string
    {
        $date = now()->format('Ymd');
        $prefix = "PRD-{$date}-";

        $last = \App\Models\ProductionOrder::where('production_code', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $seq = $last ? (int) substr($last->production_code, -4) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
