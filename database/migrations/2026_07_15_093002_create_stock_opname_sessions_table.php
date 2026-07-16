<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->enum('item_type', ['raw_material', 'product']);
            $table->string('title')->nullable();
            $table->enum('status', ['draft', 'confirmed'])->default('draft');
            $table->date('opname_date');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->foreignId('session_id')->nullable()->constrained('stock_opname_sessions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropConstrainedForeignId('session_id');
        });
        Schema::dropIfExists('stock_opname_sessions');
    }
};
