<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan business_id ke raw_material_batches.
 *
 * Alasan: table ini memegang SELURUH stok bahan baku, tapi tidak punya
 * business_id maupun global scope — melanggar AGENTS.md bagian 2. Akibatnya
 * tidak ada yang membatasi PurchaseController::returnStore dari menyentuh
 * batch milik tenant lain (rule 'exists:raw_material_batches,id' lolos).
 *
 * Tiga langkah wajib: tambah nullable -> backfill -> NOT NULL. Foreign key
 * tidak boleh ditambahkan ke kolom yang masih berisi NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_material_batches', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Backfill dari bahan induknya. SQL ini portabel MySQL + SQLite.
        DB::statement(
            'UPDATE raw_material_batches SET business_id = ('
            .'  SELECT raw_materials.business_id FROM raw_materials'
            .'  WHERE raw_materials.id = raw_material_batches.raw_material_id'
            .') WHERE business_id IS NULL'
        );

        Schema::table('raw_material_batches', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable(false)->change();
        });

        Schema::table('raw_material_batches', function (Blueprint $table) {
            $table->index('business_id', 'idx_raw_material_batches_business_id');
        });
    }

    public function down(): void
    {
        Schema::table('raw_material_batches', function (Blueprint $table) {
            $table->dropIndex('idx_raw_material_batches_business_id');
            $table->dropConstrainedForeignId('business_id');
        });
    }
};