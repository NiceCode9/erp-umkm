<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        DB::statement(
            'UPDATE product_batches SET business_id = ('
            .'  SELECT products.business_id FROM products'
            .'  WHERE products.id = product_batches.product_id'
            .') WHERE business_id IS NULL'
        );

        Schema::table('product_batches', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable(false)->change();
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->index('business_id', 'idx_product_batches_business_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropIndex('idx_product_batches_business_id');
            $table->dropConstrainedForeignId('business_id');
        });
    }
};