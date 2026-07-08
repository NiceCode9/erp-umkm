<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('recipe_yield_quantity', 12, 2)->default(1)->after('selling_price');
            $table->decimal('minimum_stock', 12, 2)->default(0)->after('recipe_yield_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['recipe_yield_quantity', 'minimum_stock']);
        });
    }
};
