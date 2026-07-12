<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_distribution_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_distribution_id')->constrained()->cascadeOnDelete();
            $table->enum('item_type', ['raw_material', 'product']);
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_distribution_items');
    }
};
