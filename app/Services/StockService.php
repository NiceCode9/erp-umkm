<?php

namespace App\Services;

use App\Models\ProductBatch;
use App\Models\ProductionConsumption;
use App\Models\RawMaterial;
use App\Models\RawMaterialBatch;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\PurchasePayment;
use App\Models\StockDistributionItemBatch;
use App\Models\StockDistributionItem;
use App\Models\StockDistribution;
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
     * Consume raw materials for production using multi-recipe FEFO.
     * total_kebutuhan = qty_per_batch × batch_multiplier
     *
     * @throws \InvalidArgumentException when stock is insufficient
     */
    public function consumeRawMaterialsForProduction(
        int $recipeId, int $branchId, int $businessId,
        float $batchMultiplier, int $productionOrderId, int $userId,
    ): Collection {
        $recipe = \App\Models\Recipe::with('items.rawMaterial')->findOrFail($recipeId);
        $items = $recipe->items;

        if ($items->isEmpty()) {
            throw new \InvalidArgumentException('Resep ini belum memiliki bahan baku.');
        }

        $shortages = [];

        return DB::transaction(function () use (
            $items, $branchId, $businessId, $batchMultiplier,
            $productionOrderId, $userId, &$shortages
        ) {
            $consumptions = collect();

            foreach ($items as $item) {
                // formula: total_kebutuhan = qty_per_batch × batch_multiplier
                $totalNeeded = $item->qty_per_batch * $batchMultiplier;

                $convertedNeeded = $this->convertUnit(
                    $totalNeeded, $item->unit, $item->rawMaterial->base_unit
                );

                // Check total available stock first
                $totalAvailable = (float) RawMaterialBatch::where('raw_material_id', $item->raw_material_id)
                    ->where('branch_id', $branchId)
                    ->where('quantity_remaining', '>', 0)
                    ->sum('quantity_remaining');

                if ($totalAvailable < $convertedNeeded) {
                    $shortages[] = (object) [
                        'raw_material' => $item->rawMaterial->name,
                        'unit' => $item->rawMaterial->base_unit,
                        'needed' => $convertedNeeded,
                        'available' => $totalAvailable,
                        'shortage' => $convertedNeeded - $totalAvailable,
                    ];
                    continue;
                }

                // FEFO: get batches ordered by expired_date ASC (NULLS LAST)
                $batches = RawMaterialBatch::where('raw_material_id', $item->raw_material_id)
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
                        $item->raw_material_id, $batch->id,
                        'out', $deductQty, 'production', $productionOrderId, $userId);

                    $remaining -= $deductQty;
                }

                if ($remaining > 0) {
                    $shortages[] = (object) [
                        'raw_material' => $item->rawMaterial->name,
                        'unit' => $item->rawMaterial->base_unit,
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
                'production_order_id' => $productionOrderId,
                'batch_no' => $order->production_code ?? 'PROD-' . $order->id,
                'quantity_remaining' => $quantity,
                'production_cost' => 0,
                'production_code' => $order->production_code,
                'expired_date' => $expiredDate,
                'produced_at' => now(),
            ]);

            $this->recordMovement($businessId, $branchId, 'product', $productId,
                $batch->id, 'in', $quantity, 'production', $productionOrderId, $userId, 'product');
        });
    }

    /**
     * Check stock availability for a production order (without consuming).
     * total_kebutuhan = qty_per_batch × batch_multiplier
     */
    public function checkProductionStockAvailability(
        int $recipeId, int $branchId, float $batchMultiplier
    ): array {
        $recipe = \App\Models\Recipe::with('items.rawMaterial')->findOrFail($recipeId);
        $shortages = [];

        foreach ($recipe->items as $item) {
            $totalNeeded = $item->qty_per_batch * $batchMultiplier;
            $convertedNeeded = $this->convertUnit(
                $totalNeeded, $item->unit, $item->rawMaterial->base_unit
            );

            $totalAvailable = (float) RawMaterialBatch::where('raw_material_id', $item->raw_material_id)
                ->where('branch_id', $branchId)
                ->where('quantity_remaining', '>', 0)
                ->sum('quantity_remaining');

            if ($totalAvailable < $convertedNeeded) {
                $shortages[] = (object) [
                    'name' => $item->rawMaterial->name,
                    'unit' => $item->rawMaterial->base_unit,
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
        ?string $batchType = null,
    ): void {
        StockMovement::create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'item_type' => $itemType,
            'item_id' => $itemId,
            'batch_id' => $batchId,
            'batch_type' => $batchType ?? $itemType,
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

    /**
     * Adjust stock from opname. Checks item_type to resolve batch correctly.
     */
    public function adjustStockFromOpname(
        int $branchId, int $businessId,
        string $itemType, int $itemId, int $batchId,
        float $systemQty, float $actualQty,
        string $reason, int $userId,
    ): StockOpname {
        return DB::transaction(function () use (
            $branchId, $businessId, $itemType, $itemId, $batchId,
            $systemQty, $actualQty, $reason, $userId
        ) {
            $diff = $actualQty - $systemQty;

            if ($itemType === 'raw_material') {
                RawMaterialBatch::where('id', $batchId)->update(['quantity_remaining' => $actualQty]);
            } else {
                ProductBatch::where('id', $batchId)->update(['quantity_remaining' => $actualQty]);
            }

            if ($diff != 0) {
                StockMovement::create([
                    'business_id' => $businessId,
                    'branch_id' => $branchId,
                    'item_type' => $itemType,
                    'item_id' => $itemId,
                    'batch_id' => $batchId,
                    'batch_type' => $itemType,
                    'movement_type' => $diff > 0 ? 'in' : 'out',
                    'quantity' => abs($diff),
                    'reference_type' => 'stock_opname',
                    'reference_id' => 0,
                    'created_by' => $userId,
                ]);
            }

            return StockOpname::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'item_type' => $itemType,
                'item_id' => $itemId,
                'batch_id' => $batchId,
                'system_quantity' => $systemQty,
                'actual_quantity' => $actualQty,
                'difference' => $diff,
                'reason' => $reason,
                'user_id' => $userId,
            ]);
        });
    }
}

    /**
     * Generate invoice number: INV-YYYYMMDD-XXXX (sequential per day).
     */
    public function generateInvoiceNo(): string
    {
        $date = now()->format('Ymd');
        $prefix = "INV-{$date}-";

        $last = Sale::where('invoice_no', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $seq = $last ? (int) substr($last->invoice_no, -4) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Consume product stock for a sale item using FEFO.
     * Returns collection of SaleItemBatch records.
     *
     * @throws \InvalidArgumentException when stock is insufficient
     */
    public function consumeProductStockForSale(
        int $productId, int $branchId, int $businessId,
        float $quantityNeeded, int $saleItemId, int $userId,
    ): Collection {
        $totalAvailable = (float) ProductBatch::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('quantity_remaining', '>', 0)
            ->sum('quantity_remaining');

        if ($totalAvailable < $quantityNeeded) {
            throw new \InvalidArgumentException(
                "Stok produk tidak mencukupi. Tersedia: {$totalAvailable}, diminta: {$quantityNeeded}"
            );
        }

        $batches = ProductBatch::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('quantity_remaining', '>', 0)
            ->orderByRaw('COALESCE(expired_date, \'9999-12-31\') ASC')
            ->get();

        $remaining = $quantityNeeded;
        $deductions = collect();

        return DB::transaction(function () use (
            $batches, &$remaining, $saleItemId, $quantityNeeded,
            $businessId, $branchId, $productId, $userId, $deductions
        ) {
            foreach ($batches as $batch) {
                if ($remaining <= 0) break;

                $deductQty = min($remaining, (float) $batch->quantity_remaining);
                $batch->decrement('quantity_remaining', $deductQty);

                $sib = SaleItemBatch::create([
                    'sale_item_id' => $saleItemId,
                    'product_batch_id' => $batch->id,
                    'quantity' => $deductQty,
                ]);
                $deductions->push($sib);

                $this->recordMovement(
                    businessId: $businessId,
                    branchId: $branchId,
                    itemType: 'product',
                    itemId: $productId,
                    batchId: $batch->id,
                    movementType: 'out',
                    quantity: $deductQty,
                    referenceType: 'sale',
                    referenceId: $saleItemId,
                    userId: $userId,
                    batchType: 'product',
                );

                $remaining -= $deductQty;
            }

            return $deductions;
        });
    }

    /**
     * Check product stock availability for a sale.
     */
    public function checkProductStockAvailability(int $productId, int $branchId): float
    {
        return (float) ProductBatch::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('quantity_remaining', '>', 0)
            ->sum('quantity_remaining');
    }

    /**
     * Return product stock from a sale return — restock to ORIGINAL batch
     * using sale_item_batches data (preserves expired_date for FEFO accuracy).
     *
     * @return Collection of SaleItemBatch records that were restored
     */
    public function returnProductStockFromSale(
        int $saleItemId, float $quantityToReturn, int $saleReturnItemId, int $userId,
    ): Collection {
        $sibRecords = SaleItemBatch::where('sale_item_id', $saleItemId)
            ->where('quantity', '>', 0)
            ->orderBy('id')
            ->get();

        if ($sibRecords->isEmpty()) {
            throw new \InvalidArgumentException('Tidak ada data batch untuk item penjualan ini.');
        }

        $totalAvailable = (float) $sibRecords->sum('quantity');
        if ($quantityToReturn > $totalAvailable) {
            throw new \InvalidArgumentException(
                "Qty retur ({$quantityToReturn}) melebihi qty yang tercatat di batch ({$totalAvailable})."
            );
        }

        $item = $sibRecords->first()->saleItem;
        $sale = $item->sale;
        $remaining = $quantityToReturn;
        $restored = collect();

        return DB::transaction(function () use (
            $sibRecords, &$remaining, $saleReturnItemId,
            $sale, $item, $userId, $restored
        ) {
            foreach ($sibRecords as $sib) {
                if ($remaining <= 0) break;

                $batch = ProductBatch::findOrFail($sib->product_batch_id);
                $restoreQty = min($remaining, (float) $sib->quantity);

                $batch->increment('quantity_remaining', $restoreQty);

                $this->recordMovement(
                    businessId: $sale->business_id,
                    branchId: $sale->branch_id,
                    itemType: 'product',
                    itemId: $item->product_id,
                    batchId: $batch->id,
                    movementType: 'in',
                    quantity: $restoreQty,
                    referenceType: 'sale_return',
                    referenceId: $saleReturnItemId,
                    userId: $userId,
                    batchType: 'product',
                );

                $restored->push((object) [
                    'batch_id' => $batch->id,
                    'batch_no' => $batch->batch_no,
                    'expired_date' => $batch->expired_date,
                    'quantity' => $restoreQty,
                ]);

                $remaining -= $restoreQty;
            }

            return $restored;
        });
    }

    /**
     * Recalculate sale payment_status based on formula:
     * outstanding = total_amount - SUM(sale_payments) - SUM(sale_returns.total_amount)
     */
    public function recalculateSalePaymentStatus(Sale $sale): string
    {
        $totalPaid = (float) SalePayment::where('sale_id', $sale->id)->sum('amount');
        $totalReturned = (float) SaleReturn::where('sale_id', $sale->id)->sum('total_amount');
        $outstanding = (float) $sale->total_amount - $totalPaid - $totalReturned;

        $status = $outstanding <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');

        Sale::withoutGlobalScopes()->where('id', $sale->id)->update(['payment_status' => $status]);

        return $status;
    }

    /**
     * Recalculate purchase payment_status (mirror of sale logic for consistency).
     */
    public function recalculatePurchasePaymentStatus(\App\Models\Purchase $purchase): string
    {
        $totalPaid = (float) PurchasePayment::where('purchase_id', $purchase->id)->sum('amount');
        $outstanding = (float) $purchase->total_amount - $totalPaid;

        $status = $outstanding <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');

        $purchase->update(['payment_status' => $status]);

        return $status;
    }

    /**
     * Execute stock distribution (change status to shipped):
     * deduct stock at origin using FEFO, record batch usage.
     *
     * @throws \InvalidArgumentException
     */
    public function distributeStockForShip(int $distributionId, int $userId): StockDistribution
    {
        $distribution = StockDistribution::with('items')->findOrFail($distributionId);

        if ($distribution->status !== 'pending') {
            throw new \InvalidArgumentException('Distribusi sudah pernah dikirim.');
        }

        return DB::transaction(function () use ($distribution, $userId) {
            foreach ($distribution->items as $item) {
                if ($item->item_type === 'raw_material') {
                    $this->distributeRawMaterialItem($item, $userId);
                } elseif ($item->item_type === 'product') {
                    $this->distributeProductItem($item, $userId);
                }
            }

            $distribution->update([
                'status' => 'shipped',
                'shipped_at' => now(),
            ]);

            return $distribution;
        });
    }

    private function distributeRawMaterialItem(StockDistributionItem $item, int $userId): void
    {
        $needed = (float) $item->quantity;
        $branchId = $item->distribution->origin_branch_id;
        $businessId = $item->distribution->business_id;

        $totalAvailable = (float) RawMaterialBatch::where('raw_material_id', $item->item_id)
            ->where('branch_id', $branchId)
            ->where('quantity_remaining', '>', 0)
            ->sum('quantity_remaining');

        if ($totalAvailable < $needed) {
            $name = $item->rawMaterial?->name ?? "bahan baku #{$item->item_id}";
            throw new \InvalidArgumentException(
                "Stok {$name} di cabang asal tidak mencukupi. Tersedia: {$totalAvailable}, diminta: {$needed}"
            );
        }

        $batches = RawMaterialBatch::where('raw_material_id', $item->item_id)
            ->where('branch_id', $branchId)
            ->where('quantity_remaining', '>', 0)
            ->orderByRaw('COALESCE(expired_date, \'9999-12-31\') ASC')
            ->get();

        $remaining = $needed;
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $deductQty = min($remaining, (float) $batch->quantity_remaining);
            $batch->decrement('quantity_remaining', $deductQty);

            StockDistributionItemBatch::create([
                'stock_distribution_item_id' => $item->id,
                'raw_material_batch_id' => $batch->id,
                'product_batch_id' => null,
                'quantity' => $deductQty,
            ]);

            $this->recordMovement(
                businessId: $businessId,
                branchId: $branchId,
                itemType: 'raw_material',
                itemId: $item->item_id,
                batchId: $batch->id,
                movementType: 'out',
                quantity: $deductQty,
                referenceType: 'stock_distribution',
                referenceId: $item->distribution->id,
                userId: $userId,
                batchType: 'raw_material',
            );

            $remaining -= $deductQty;
        }
    }

    private function distributeProductItem(StockDistributionItem $item, int $userId): void
    {
        $needed = (float) $item->quantity;
        $branchId = $item->distribution->origin_branch_id;
        $businessId = $item->distribution->business_id;

        $totalAvailable = (float) ProductBatch::where('product_id', $item->item_id)
            ->where('branch_id', $branchId)
            ->where('quantity_remaining', '>', 0)
            ->sum('quantity_remaining');

        if ($totalAvailable < $needed) {
            $name = $item->product?->name ?? "produk #{$item->item_id}";
            throw new \InvalidArgumentException(
                "Stok {$name} di cabang asal tidak mencukupi. Tersedia: {$totalAvailable}, diminta: {$needed}"
            );
        }

        $batches = ProductBatch::where('product_id', $item->item_id)
            ->where('branch_id', $branchId)
            ->where('quantity_remaining', '>', 0)
            ->orderByRaw('COALESCE(expired_date, \'9999-12-31\') ASC')
            ->get();

        $remaining = $needed;
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $deductQty = min($remaining, (float) $batch->quantity_remaining);
            $batch->decrement('quantity_remaining', $deductQty);

            StockDistributionItemBatch::create([
                'stock_distribution_item_id' => $item->id,
                'product_batch_id' => $batch->id,
                'raw_material_batch_id' => null,
                'quantity' => $deductQty,
            ]);

            $this->recordMovement(
                businessId: $businessId,
                branchId: $branchId,
                itemType: 'product',
                itemId: $item->item_id,
                batchId: $batch->id,
                movementType: 'out',
                quantity: $deductQty,
                referenceType: 'stock_distribution',
                referenceId: $item->distribution->id,
                userId: $userId,
                batchType: 'product',
            );

            $remaining -= $deductQty;
        }
    }

    /**
     * Complete stock distribution (change status to received):
     * create NEW batches at destination with same expired_date & metadata.
     */
    public function distributeStockForReceive(int $distributionId, int $userId): StockDistribution
    {
        $distribution = StockDistribution::with(['items.batchRecords'])->findOrFail($distributionId);

        if ($distribution->status !== 'shipped') {
            throw new \InvalidArgumentException('Distribusi harus dalam status dikirim.');
        }

        return DB::transaction(function () use ($distribution, $userId) {
            foreach ($distribution->items as $item) {
                foreach ($item->batchRecords as $record) {
                    if ($item->item_type === 'raw_material') {
                        $sourceBatch = RawMaterialBatch::findOrFail($record->raw_material_batch_id);

                        $newBatch = RawMaterialBatch::create([
                            'raw_material_id' => $item->item_id,
                            'branch_id' => $distribution->destination_branch_id,
                            'batch_no' => $sourceBatch->batch_no . '-DST',
                            'quantity_remaining' => (float) $record->quantity,
                            'purchase_price' => $sourceBatch->purchase_price,
                            'expired_date' => $sourceBatch->expired_date,
                            'received_at' => now(),
                        ]);

                        $this->recordMovement(
                            businessId: $distribution->business_id,
                            branchId: $distribution->destination_branch_id,
                            itemType: 'raw_material',
                            itemId: $item->item_id,
                            batchId: $newBatch->id,
                            movementType: 'in',
                            quantity: (float) $record->quantity,
                            referenceType: 'stock_distribution',
                            referenceId: $distribution->id,
                            userId: $userId,
                            batchType: 'raw_material',
                        );
                    } else {
                        $sourceBatch = ProductBatch::findOrFail($record->product_batch_id);

                        $newBatch = ProductBatch::create([
                            'product_id' => $item->item_id,
                            'branch_id' => $distribution->destination_branch_id,
                            'batch_no' => $sourceBatch->batch_no . '-DST',
                            'quantity_remaining' => (float) $record->quantity,
                            'production_cost' => $sourceBatch->production_cost,
                            'production_code' => $sourceBatch->production_code,
                            'expired_date' => $sourceBatch->expired_date,
                            'produced_at' => $sourceBatch->produced_at,
                        ]);

                        $this->recordMovement(
                            businessId: $distribution->business_id,
                            branchId: $distribution->destination_branch_id,
                            itemType: 'product',
                            itemId: $item->item_id,
                            batchId: $newBatch->id,
                            movementType: 'in',
                            quantity: (float) $record->quantity,
                            referenceType: 'stock_distribution',
                            referenceId: $distribution->id,
                            userId: $userId,
                            batchType: 'product',
                        );
                    }
                }
            }

            $distribution->update([
                'status' => 'received',
                'received_at' => now(),
            ]);

            return $distribution;
        });
    }

    /**
     * Check total stock for an item (raw_material or product) at a branch,
     * excluding stock that is already in transit (shipped distributions).
     */
    public function checkDistributionStockAvailability(string $itemType, int $itemId, int $branchId): float
    {
        if ($itemType === 'raw_material') {
            return (float) RawMaterialBatch::where('raw_material_id', $itemId)
                ->where('branch_id', $branchId)
                ->where('quantity_remaining', '>', 0)
                ->sum('quantity_remaining');
        }

        return (float) ProductBatch::where('product_id', $itemId)
            ->where('branch_id', $branchId)
            ->where('quantity_remaining', '>', 0)
            ->sum('quantity_remaining');
    }
}
