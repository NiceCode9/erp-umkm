<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_recipes');
    }

    public function down(): void
    {
        // Not restoring - replaced by recipes + recipe_items
    }
};
