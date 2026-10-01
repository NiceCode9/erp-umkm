<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan total_amount ke purchase_returns (meniru sisi penjualan yang
 * sudah punya migration sendiri: 2026_07_11_000001_add_total_amount_to_sale_returns).
 *
 * Tanpa kolom ini, empat tempat yang menghitung utang supplier memanggil
 * $returns->sum('total_amount') yang selalu menghasilkan 0 SENYAP (data_get
 * mengembalikan null untuk atribut yang tidak ada). Akibatnya retur pembelian
 * TIDAK PERNAH mengurangi utang supplier di mana pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->decimal('total_amount', 15, 2)->default(0)->after('reason');
        });

        // Backfill dari item-item retur yang sudah ada.
        DB::statement(
            'UPDATE purchase_returns SET total_amount = ('
            .'  SELECT COALESCE(SUM(purchase_return_items.subtotal), 0) FROM purchase_return_items'
            .'  WHERE purchase_return_items.purchase_return_id = purchase_returns.id'
            .')'
        );
    }

    public function down(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropColumn('total_amount');
        });
    }
};