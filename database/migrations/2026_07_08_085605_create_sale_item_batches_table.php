<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_item_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_item_id');
            $table->unsignedBigInteger('product_batch_id');
            $table->decimal('quantity', 12, 2);
            $table->timestamps();

            // FK disabled until sale_items table exists (Fase 5)
            // $table->foreign('sale_item_id')->references('id')->on('sale_items')->cascadeOnDelete();
            // $table->foreign('product_batch_id')->references('id')->on('product_batches')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_item_batches');
    }
};
