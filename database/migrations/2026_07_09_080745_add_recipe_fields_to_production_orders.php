<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('recipe_id')->nullable()->constrained()->after('user_id');
            $table->decimal('batch_multiplier', 12, 2)->default(1)->after('quantity_target');
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recipe_id');
            $table->dropColumn('batch_multiplier');
        });
    }
};
