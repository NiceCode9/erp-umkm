<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->enum('item_type', ['raw_material', 'product']);
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('batch_id');
            $table->decimal('system_quantity', 12, 2);
            $table->decimal('actual_quantity', 12, 2);
            $table->decimal('difference', 12, 2);
            $table->text('reason');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
