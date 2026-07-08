<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_distribution_item_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_distribution_item_id');
            $table->unsignedBigInteger('product_batch_id')->nullable();
            $table->unsignedBigInteger('raw_material_batch_id')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->timestamps();

            // FK constraints disabled until parent tables exist (Fase 7)
            // $table->foreign('stock_distribution_item_id')->references('id')->on('stock_distribution_items')->cascadeOnDelete();
            // $table->foreign('product_batch_id')->references('id')->on('product_batches')->cascadeOnDelete();
            // $table->foreign('raw_material_batch_id')->references('id')->on('raw_material_batches')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_distribution_item_batches');
    }
};
