<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\RawMaterialBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya tempat menghitung utang ke supplier & memproses retur pembelian.
 *
 * Aturan ini dulunya disalin-tempel di 7 tempat berbeda (Purchase model,
 * StockService, DebtController, DashboardController, ReportController x2,
 * SupplierDebtExport) dengan hasil berbeda-beda. BUSINESS-RULES.md bagian 5
 * melarang duplikasi seperti ini.
 *
 * FORMULA FINAL (BUSINESS-RULES.md bagian 5):
 *
 *   outstanding = total_amount
 *               - SUM(purchase_payments.amount)
 *               - SUM(purchase_returns.total_amount)
 *
 * outstanding boleh NEGATIF -> status 'credit' (supplier owes kita).
 */
class PurchaseService
{
    /**
     * Hitung outstanding tanpa query tambahan bila data sudah di-eager-load.
     */
    public function outstanding(Purchase $purchase): float
    {
        return (float) $purchase->outstanding_amount;
    }

    /**
     * Tentukan payment_status dari formula final lalu simpan ke purchase.
     * Dipanggil setiap ada pembayaran maupun retur baru.
     */
    public function recalculatePaymentStatus(Purchase $purchase): string
    {
        $paid = (float) $purchase->payments()->sum('amount');
        $returned = (float) $purchase->returns()->sum('total_amount');
        $outstanding = (float) $purchase->total_amount - $paid - $returned;

        $status = match (true) {
            $outstanding < 0 => 'credit',
            $outstanding == 0.0 => 'paid',
            $paid > 0 => 'partial',
            default => 'unpaid',
        };

        if ($purchase->payment_status !== $status) {
            $purchase->update(['payment_status' => $status]);
        }

        return $status;
    }

    /**
     * Catat pembayaran cicilan/pelunasan utang ke supplier.
     * Menolak pembayaran yang melebihi sisa utang (overpayment).
     */
    public function recordPayment(Purchase $purchase, float $amount, string $paidAt, string $method): PurchasePayment
    {
        $remaining = max(0.0, $this->outstanding($purchase));

        if ($amount - $remaining > 0.005) {
            throw ValidationException::withMessages([
                'amount' => "Pembayaran melebihi sisa utang. Sisa utang saat ini: {$remaining}.",
            ]);
        }

        return PurchasePayment::create([
            'purchase_id' => $purchase->id,
            'amount' => $amount,
            'paid_at' => $paidAt,
            'method' => $method,
        ]);
    }

    /**
     * Catat retur pembelian ke supplier.
     *
     * Validasi ketat:
     * - purchase_item milik pembelian ini;
     * - batch milik bahan baku yang sama & cabang pembelian;
     * - kuantitas retur tidak melebihi sisa yang belum diretur;
     * - stok batch mencukupi.
     *
     * Harga TIDAK pernah diambil dari request — selalu dari PurchaseItem agar
     * nilai retur tidak bisa dimanipulasi.
     */
    public function recordReturn(Purchase $purchase, array $data, int $userId): PurchaseReturn
    {
        return DB::transaction(function () use ($purchase, $data, $userId) {
            $return = PurchaseReturn::create([
                'purchase_id' => $purchase->id,
                'business_id' => $purchase->business_id,
                'branch_id' => $purchase->branch_id,
                'user_id' => $userId,
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'total_amount' => 0,
            ]);

            $totalReturn = 0.0;

            foreach ($data['items'] as $idx => $row) {
                $purchaseItem = PurchaseItem::where('id', $row['purchase_item_id'])
                    ->where('purchase_id', $purchase->id)
                    ->first();

                if (! $purchaseItem) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.purchase_item_id" => 'Baris pembelian tidak ditemukan pada pembelian ini.',
                    ]);
                }

                // Batch harus milik bahan baku yang sama DAN cabang pembelian.
                $batch = RawMaterialBatch::where('id', $row['raw_material_batch_id'])
                    ->where('raw_material_id', $purchaseItem->raw_material_id)
                    ->where('branch_id', $purchase->branch_id)
                    ->first();

                if (! $batch) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.raw_material_batch_id" => 'Batch tidak valid untuk bahan baku/cabang pembelian ini.',
                    ]);
                }

                $qty = (float) $row['quantity'];

                // Kuantitas retur tidak boleh melebihi sisa yang belum diretur.
                $returnable = $purchaseItem->returnableQuantity();
                if ($qty - $returnable > 0.005) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.quantity" => "Kuantitas retur melebihi sisa yang dapat diretur ({$returnable}).",
                    ]);
                }

                // Stok batch juga harus cukup.
                if ($qty - (float) $batch->quantity_remaining > 0.005) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.quantity" => "Stok batch {$batch->batch_no} tidak mencukupi.",
                    ]);
                }

                // Harga SELALU dari baris pembelian, bukan dari request.
                $unitPrice = (float) $purchaseItem->unit_price;
                $subtotal = $qty * $unitPrice;
                $totalReturn += $subtotal;

                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'raw_material_batch_id' => $batch->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                // Kurangi stok batch (lockForUpdate di dalam StockService).
                app(StockService::class)->decreaseRawMaterialStockFromBatch(
                    batchId: $batch->id,
                    quantity: $qty,
                    referenceType: 'purchase_return',
                    referenceId: $return->id,
                    userId: $userId,
                );
            }

            $return->update(['total_amount' => $totalReturn]);

            // Status utang diperbarui setelah retur tercatat.
            $this->recalculatePaymentStatus($purchase->refresh());

            return $return->refresh();
        });
    }
}