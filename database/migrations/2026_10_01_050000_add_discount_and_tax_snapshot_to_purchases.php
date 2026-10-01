<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot diskon & pajak pada pembelian.
 *
 * AGENTS.md bagian 6 mewajibkan nilai diskon/tax/harga disimpan sebagai
 * snapshot di tabel transaksi supaya riwayat lama tidak berubah saat
 * setting cabang diubah. Sisi penjualan sudah punya ini (lihat migration
 * create_sales_table), sisi pembelian belum sama sekali sehingga pembelian
 * yang terkena pajak tidak bisa dicatat benar.
 *
 * Default 0 / null menjaga perilaku lama: pembelian tanpa diskon & tanpa
 * pajak menghasilkan total yang sama seperti sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->default(0)->after('purchase_date');
            $table->enum('discount_type', ['nominal', 'percent'])->nullable()->after('subtotal');
            $table->decimal('discount_value', 15, 2)->nullable()->after('discount_type');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_value');
            $table->decimal('tax_percentage_applied', 5, 2)->nullable()->after('discount_amount');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_percentage_applied');
        });

        // Backfill: subtotal & total lama identik karena diskon/pajak belum ada.
        DB::statement('UPDATE purchases SET subtotal = total_amount WHERE subtotal = 0');
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'discount_type', 'discount_value', 'discount_amount', 'tax_percentage_applied', 'tax_amount']);
        });
    }
};