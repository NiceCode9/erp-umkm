<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_item_batches', function (Blueprint $table) {
            $table->foreign('sale_item_id')->references('id')->on('sale_items')->cascadeOnDelete();
            $table->foreign('product_batch_id')->references('id')->on('product_batches')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_item_batches', function (Blueprint $table) {
            $table->dropForeign(['sale_item_id']);
            $table->dropForeign(['product_batch_id']);
        });
    }
};
