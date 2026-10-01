<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan status 'credit' ke purchases.payment_status.
 *
 * Formula final utang supplier:
 *   outstanding = total_amount - SUM(payments) - SUM(returns.total_amount)
 *
 * Nilai outstanding BOLEH negatif ketika retur melebihi sisa utang (mis. beli
 * Rp1.000.000, bayar lunas, retur Rp400.000 -> outstanding -Rp400.000). Status
 * 'paid' tidak lagi cukup untuk kasus itu karena kolomnya non-negatif secara
 * konsep, dan kalau di-clamp ke 0 maka kredit Rp400.000 itu hilang dari
 * semua laporan. 'credit' = supplier yang owes kita.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'credit'])
                ->default('unpaid')
                ->change();
        });
    }

    public function down(): void
    {
        // Kembalikan baris 'credit' menjadi 'paid' lebih dulu supaya tidak
        // ada nilai di luar daftar enum lama.
        DB::table('purchases')->where('payment_status', 'credit')->update(['payment_status' => 'paid']);

        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid')->change();
        });
    }
};