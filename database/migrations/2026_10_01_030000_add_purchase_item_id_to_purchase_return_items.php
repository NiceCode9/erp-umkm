<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan purchase_item_id ke purchase_return_items (meniru sale_return_items
 * yang sudah punya sale_item_id).
 *
 * PurchaseController::returnStore sudah memvalidasi items.*.purchase_item_id
 * sejak dulu, TAPI column-nya tidak pernah ada sehingga nilainya dibuang. Tanpa
 * relasi ini mustahil memvalidasi "retur tidak melebihi yang dibeli" maupun
 * melacak retur kembali ke baris pembelian asalnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->foreignId('purchase_item_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Backfill heuristik: cocokkan lewat bahan baku yang sama. Batch retur
        // selalu berasal dari pembelian yang sama, jadi ini aman untuk data lama.
        DB::statement(
            'UPDATE purchase_return_items SET purchase_item_id = ('
            .'  SELECT purchase_items.id FROM purchase_items'
            .'   JOIN raw_material_batches ON raw_material_batches.id = purchase_return_items.raw_material_batch_id'
            .'  WHERE purchase_items.raw_material_id = raw_material_batches.raw_material_id'
            .'    AND purchase_items.purchase_id = ('
            .'      SELECT purchase_id FROM purchase_returns WHERE purchase_returns.id = purchase_return_items.purchase_return_id'
            .'    )'
            .'  LIMIT 1'
            .') WHERE purchase_item_id IS NULL'
        );
    }

    public function down(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_item_id');
        });
    }
};