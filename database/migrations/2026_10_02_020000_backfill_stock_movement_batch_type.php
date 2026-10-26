<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE stock_movements SET batch_type = item_type WHERE batch_type IS NULL");

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('batch_type')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('batch_type')->nullable()->change();
        });
    }
};