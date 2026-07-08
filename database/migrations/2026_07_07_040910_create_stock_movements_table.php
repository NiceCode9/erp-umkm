<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->enum('item_type', ['raw_material', 'product']);
            $table->unsignedBigInteger('item_id');
            $table->foreignId('batch_id')->nullable()->constrained('raw_material_batches')->nullOnDelete();
            $table->enum('movement_type', ['in', 'out']);
            $table->decimal('quantity', 12, 2);
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
